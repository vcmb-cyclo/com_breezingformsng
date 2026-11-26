<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 *
 * Source history: admin/quickmode.class.php (git mv — Phase 7).
 */

namespace Vcmb\Component\BreezingformsNG\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomla\Filesystem\File;
use Vcmb\Component\BreezingformsNG\Administrator\Service\AreaAccess;
use Vcmb\Component\BreezingformsNG\Administrator\Service\FormCodeGuard;
use Vcmb\Component\BreezingformsNG\Administrator\Service\ItemName;
use Vcmb\Component\BreezingformsNG\Administrator\Service\QuickmodeThemePath;
use Vcmb\Component\BreezingformsNG\Site\Service\Submission\SofortOnlyFormDetector;
use Vcmb\Component\BreezingformsNG\Site\Service\Widget\WidgetOptions;

/**
 * @phpstan-type QuickModeTreeNode array{
 *     attributes: array{id: string},
 *     properties: array<string, mixed>,
 *     children?: list<array<string, mixed>>
 * }
 * @phpstan-type QuickModeElementData array<string, mixed>&array{options: array<string, mixed>}
 */
class QuickmodeModel extends BaseDatabaseModel
{
    private DatabaseInterface $db;

    /** @var list<string> The code columns of the last save whose change was not kept for lack of the code permission. */
    private array $ignoredCodeColumns = [];

    /**
     * The code columns of the stored elements of the form being saved, by element id,
     * when the user may not write code; null when the user may.
     *
     * @var array<int, array<string, mixed>>|null
     */
    private ?array $storedElementCode = null;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config = [])
    {
        parent::__construct($config);
        $this->db = $this->getDatabase();
    }

    /**
     * @param array<string, mixed> $dataObject
     * @param int                  $published Initial state for a new form; existing-form state follows QuickMode's normal rules.
     * @param bool                 $writeThemeFiles Whether to apply theme variable changes to theme files.
     */
    public function save(int $form, array $dataObject, int $published = 1, bool $writeThemeFiles = true): int
    {
        $this->assertQuickModeTreeNode($dataObject);

        if (!in_array($published, [0, 1], true)) {
            throw new \InvalidArgumentException('A form can only be created published or unpublished.');
        }

        // Validate the identity of the form before code guards, theme-file writes or persistence work.
        $mdata = $this->quickModeNodeProperties($dataObject);
        $formName = trim((string) ($mdata['name'] ?? ''));
        $formTitle = trim((string) ($mdata['title'] ?? ''));

        if ($formName === '') {
            $formName = self::normaliseFormName($formTitle);
        }

        $this->assertFormNameAllowed($form, $formName);

        if ($writeThemeFiles) {
            unset(
                $dataObject['properties']['themebootstrapUse3'],
                $dataObject['properties']['themebootstrap3builtin'],
                $dataObject['properties']['themebootstrap3classpfx'],
                $dataObject['properties']['themeusebootstraplegacy']
            );
        }

        $this->guardCode($form, $dataObject);

        $areas = new \stdClass();
        $areas->container    = [];
        $areas->container[0] = ['elements' => [], 'elementCount' => 0];

        $this->createAreasFromTree($dataObject, $areas, 1, $writeThemeFiles);

        // Inject submit-related fake elements from published scripts.
        $fakes = ['ff_validate_submit', 'ff_resetForm', 'ff_validate_prevpage', 'ff_validate_nextpage'];
        foreach ($fakes as $idx => $scriptName) {
            $query = $this->db->createQuery()
                ->select('id')
                ->from($this->db->quoteName('#__facileforms_scripts'))
                ->where($this->db->quoteName('published') . ' = 1')
                ->where($this->db->quoteName('name') . ' = :scriptName')
                ->order(['type', 'title', 'name', 'id DESC'])
                ->bind(':scriptName', $scriptName, ParameterType::STRING);
            $this->db->setQuery($query);
            $rows = $this->db->loadObjectList();
            if (!empty($rows)) {
                $n  = $idx === 0 ? '' : (string) ($idx + 1);
                $el = $this->getDefaultElement();
                $el['title']       = 'bfFakeTitle' . $n;
                $el['name']        = 'bfFakeName' . $n;
                $el['logging']     = 0;
                $el['script2cond'] = 1;
                $el['script2id']   = (int) $rows[0]->id;
                $areas->container[0]['elements'][] = $el;
            }
        }

        return $this->save2(
            $form,
            $formName,
            $formTitle,
            (string) ($mdata['description'] ?? ''),
            base64_encode((string) json_encode($dataObject)),
            $areas->container,
            count($dataObject['children'] ?? []),
            $published
        );
    }

    /**
     * One rule for every path (ItemName), applied to a new or renamed form only: a stored name is kept as it is.
     */
    private function assertFormNameAllowed(int $form, string $formName): void
    {
        $nameProblem = ItemName::problemOnSave($formName, $form > 0 ? $this->storedFormName($form) : null);
        if ($nameProblem !== null) {
            throw new \InvalidArgumentException(Text::_($nameProblem));
        }
    }

    private function storedFormName(int $form): ?string
    {
        $query = $this->db->createQuery()
            ->select($this->db->quoteName('name'))
            ->from($this->db->quoteName('#__facileforms_forms'))
            ->where($this->db->quoteName('id') . ' = :storedForm')
            ->bind(':storedForm', $form, ParameterType::INTEGER);
        $this->db->setQuery($query);
        $name = $this->db->loadResult();

        return $name === null ? null : (string) $name;
    }

    private static function normaliseFormName(string $title): string
    {
        $name = preg_replace('/[^A-Za-z0-9]+/', '_', trim($title));

        return strtolower(trim((string) $name, '_'));
    }

    /**
     * The code columns of the last save that were left as stored because the user
     * may edit forms but not code (the editor tree, the elements and the file path of a payment).
     *
     * @return list<string>
     */
    public function getIgnoredCodeColumns(): array
    {
        return $this->ignoredCodeColumns;
    }

    /**
     * A user without the code permission keeps the code of the elements, the file
     * path of a payment download and the script run after the submission as they
     * are stored; a new form or element starts without any. The tree is corrected
     * too: it is saved with the form and read back by the editor.
     *
     * @param array<string, mixed> $dataObject
     */
    private function guardCode(int $form, array &$dataObject): void
    {
        $this->ignoredCodeColumns = [];
        $this->storedElementCode  = null;

        if (AreaAccess::isAllowed($this->getCurrentUser(), AreaAccess::CODE)) {
            return;
        }

        $elements  = [];
        $filepaths = [];
        $stored    = null;

        if ($form > 0) {
            $db    = $this->db;
            $query = $db->createQuery()
                ->select($db->quoteName(array_merge(['id'], FormCodeGuard::ELEMENT_CODE_COLUMNS)))
                ->from($db->quoteName('#__facileforms_elements'))
                ->where($db->quoteName('form') . ' = :form')
                ->bind(':form', $form, ParameterType::INTEGER);

            foreach ($db->setQuery($query)->loadAssocList() ?: [] as $row) {
                $elements[(int) $row['id']] = $row;
            }

            $query = $db->createQuery()
                ->select($db->quoteName(['script2cond', 'script2code', 'template_areas']))
                ->from($db->quoteName('#__facileforms_forms'))
                ->where($db->quoteName('id') . ' = :form')
                ->bind(':form', $form, ParameterType::INTEGER);
            $row = $db->setQuery($query)->loadAssoc();

            if (is_array($row)) {
                $stored = $row;
                $areas  = json_decode((string) $row['template_areas'], true);

                foreach (is_array($areas[0]['elements'] ?? null) ? $areas[0]['elements'] : [] as $element) {
                    if (is_array($element) && isset($element['dbId'], $element['options']['filepath'])) {
                        $filepaths[(int) $element['dbId']] = (string) $element['options']['filepath'];
                    }
                }
            }
        }

        $guarded = (new FormCodeGuard())->applyToTree($dataObject, $elements, $stored, $filepaths, false);

        $dataObject               = $guarded['tree'];
        $this->assertQuickModeTreeNode($dataObject);
        $this->ignoredCodeColumns = $guarded['ignored'];
        $this->storedElementCode  = $elements;
    }

    public function getFormOptions(int $form): ?\stdClass
    {
        $query = $this->db->createQuery()
            ->select(['package', 'name', 'title', 'description', 'emailntf', 'emailadr', 'published', 'debug_mode', 'version', 'created', 'created_by', 'modified', 'modified_by'])
            ->from($this->db->quoteName('#__facileforms_forms'))
            ->where($this->db->quoteName('id') . ' = :form')
            ->bind(':form', $form, ParameterType::INTEGER);
        $this->db->setQuery($query);
        $list = $this->db->loadObjectList();

        return count($list) === 1 ? $list[0] : null;
    }

    public function getTemplateCode(int $form): string
    {
        $query = $this->db->createQuery()
            ->select('template_code')
            ->from($this->db->quoteName('#__facileforms_forms'))
            ->where($this->db->quoteName('id') . ' = :form')
            ->bind(':form', $form, ParameterType::INTEGER);
        $this->db->setQuery($query);
        $list = $this->db->loadObjectList();

        return count($list) === 1
            ? (string) base64_decode((string) ($list[0]->template_code ?? ''))
            : '';
    }

    /**
     * @return array<string, list<object>>
     */
    public function getElementScripts(): array
    {
        $result = [];

        foreach (['Element Validation' => 'validation', 'Element Action' => 'action', 'Element Init' => 'init'] as $type => $key) {
            $query = $this->db->createQuery()
                ->select(['id', 'package', 'name', 'title', 'description', 'type'])
                ->from($this->db->quoteName('#__facileforms_scripts'))
                ->where($this->db->quoteName('published') . ' = 1')
                ->where($this->db->quoteName('type') . ' = :type')
                ->bind(':type', $type, ParameterType::STRING);
            $this->db->setQuery($query);
            $result[$key] = $this->db->loadObjectList();
        }

        return $result;
    }

    /**
     * @return list<string>
     */
    public function getThemes(): array
    {
        return $this->scanThemeDir(JPATH_SITE . '/media/breezingforms/themes/');
    }

    /**
     * @return list<string>
     */
    public function getThemesBootstrap(): array
    {
        // "carte" ships with the component as a stylesheet (media/com_breezingformsng/css/theme-carte.css).
        return array_values(array_unique(['carte', ...$this->scanThemeDir(JPATH_SITE . '/media/breezingforms/themes-bootstrap5/')]));
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    /**
     * @return list<string>
     */
    private function scanThemeDir(string $folder): array
    {
        $themes = [];

        if (!is_dir($folder) || !is_readable($folder)) {
            return $themes;
        }

        foreach (new \DirectoryIterator($folder) as $file) {
            $filename = $file->getFilename();
            if (!$file->isDir() || in_array($filename, ['.', '..', 'images', 'img', '.csv', '.svn'], true)) {
                continue;
            }

            $themes[] = $filename;
        }

        return $themes;
    }

    /**
     * @phpstan-assert QuickModeTreeNode $dataObject
     */
    private function assertQuickModeTreeNode(mixed $dataObject): void
    {
        if (
            !is_array($dataObject)
            || !isset($dataObject['attributes'])
            || !is_array($dataObject['attributes'])
            || !isset($dataObject['attributes']['id'])
            || !is_string($dataObject['attributes']['id'])
            || !isset($dataObject['properties'])
            || !is_array($dataObject['properties'])
        ) {
            throw new \UnexpectedValueException('QuickMode tree node has an invalid shape.');
        }

        if (!array_key_exists('children', $dataObject)) {
            return;
        }

        if (!is_array($dataObject['children']) || !array_is_list($dataObject['children'])) {
            throw new \UnexpectedValueException('QuickMode tree children must be a list.');
        }

        foreach ($dataObject['children'] as $child) {
            $this->assertQuickModeTreeNode($child);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function quickModeNodeProperties(mixed $dataObject): array
    {
        $this->assertQuickModeTreeNode($dataObject);

        return $dataObject['properties'];
    }

    /**
     * @return QuickModeElementData
     */
    private function getDefaultElement(): array
    {
        return [
            'element'             => null,
            'bfType'              => '',
            'elementType'         => '',
            'options'             => [],
            'data1'               => '',
            'data2'               => '',
            'data3'               => '',
            'script1cond'         => 0,
            'script1id'           => 0,
            'script1code'         => '',
            'script1flag1'        => 0,
            'script1flag2'        => 0,
            'script2cond'         => 0,
            'script2id'           => 0,
            'script2code'         => '',
            'script2flag1'        => 0,
            'script2flag2'        => 0,
            'script2flag3'        => 0,
            'script2flag4'        => 0,
            'script2flag5'        => 0,
            'script3cond'         => 0,
            'script3id'           => 0,
            'script3code'         => '',
            'script3msg'          => '',
            'functionNameScript1' => '',
            'functionNameScript2' => '',
            'functionNameScript3' => '',
            'flag1'               => 0,
            'flag2'               => 0,
            'mailback'            => 0,
            'mailbackfile'        => '',
            'title'               => '',
            'name'                => '',
            'page'                => 1,
            'orderNumber'         => 0,
            'dbId'                => 0,
            'appElementOrderId'   => 0,
            'id'                  => 0,
            'logging'             => 1,
            'qId'                 => 0,
            'internalType'        => '',
        ];
    }

    private function createAreasFromTree(mixed $dataObject, \stdClass $areas, int $page = 1, bool $writeThemeFiles = true): void
    {
        $this->assertQuickModeTreeNode($dataObject);
        $element = $this->getDefaultElement();

        if (isset($dataObject['properties']['type'])) {
            $mdata = $this->quickModeNodeProperties($dataObject);

            if ($mdata['type'] === 'root') {
                if (
                    $writeThemeFiles && !empty($mdata['themebootstrap']) && is_string($mdata['themebootstrap'])
                    && !empty($mdata['themebootstrapvars'])
                    && isset($mdata['themebootstrapbefore'])
                    && $mdata['themebootstrapbefore'] === $mdata['themebootstrap']
                    && in_array($mdata['themebootstrap'], $this->getThemesBootstrap(), true)
                ) {
                    $varspath = QuickmodeThemePath::resolve(
                        JPATH_SITE . '/media/breezingforms/themes-bootstrap5',
                        $mdata['themebootstrap']
                    );
                    if ($varspath !== null) {
                        File::write($varspath, $mdata['themebootstrapvars']);
                    }
                }
            } elseif ($mdata['type'] === 'page') {
                $ex   = explode('bfQuickModePage', $dataObject['attributes']['id']);
                $page = (int) ($ex[1] ?? 1);
            } elseif ($mdata['type'] === 'element') {
                $element['internalType'] = $mdata['bfType'];

                switch ($mdata['bfType']) {
                    case 'bfTextfield':
                        $element['bfType']             = 'Text';
                        $element['options']['value']    = $mdata['value'];
                        $element['options']['placeholder'] = $mdata['placeholder'] ?? '';
                        $element['options']['maxLength'] = $mdata['maxLength'] ?? '';
                        $element['options']['size'] = $mdata['size'] ?? '';
                        $element['data1']              = $mdata['value'];
                        // Forms saved by earlier versions hold none of these keys.
                        $element['options']['password'] = $mdata['password'] ?? false;
                        $element['flag1']              = !empty($mdata['password']) ? 1 : 0;
                        $element['options']['mailback'] = $mdata['mailback'] ?? false;
                        $element['mailback']           = !empty($mdata['mailback']) ? 1 : 0;
                        $element['mailbackAsSender']   = !empty($mdata['mailbackAsSender']) ? 1 : 0;
                        $element['mailbackfile']       = $mdata['mailbackfile'] ?? '';
                        $widget                        = WidgetOptions::widget($mdata);
                        if ($widget !== '') {
                            $element['options']['widget']        = $widget;
                            $element['options']['widgetOptions'] = WidgetOptions::options($widget, $mdata);
                        }
                        break;
                    case 'bfTextarea':
                        $element['bfType']             = 'Textarea';
                        $element['options']['value']   = $mdata['value'];
                        $element['data1']              = $mdata['value'];
                        $element['options']['placeholder'] = $mdata['placeholder'] ?? '';
                        $element['options']['maxlength'] = $mdata['maxlength'] ?? 0;
                        $element['options']['showMaxlengthCounter'] = $mdata['showMaxlengthCounter'] ?? true;
                        $element['options']['width'] = $mdata['width'] ?? '';
                        $element['options']['height'] = $mdata['height'] ?? '';
                        break;
                    case 'bfSelect':
                        $element['bfType']             = 'Select List';
                        $element['options']['multiple'] = $mdata['multiple'];
                        $element['options']['options']  = $mdata['list'];
                        $element['options']['mailback'] = $mdata['mailback'];
                        $element['mailback']           = $mdata['mailback'] ? 1 : 0;
                        $element['data1']              = 1;
                        $element['data2']              = $mdata['list'];
                        $element['flag1']              = $mdata['multiple'] ? 1 : 0;
                        break;
                    case 'bfRadioGroup':
                        $element['bfType'] = 'Radio Group';
                        $element['data2']  = $mdata['group'];
                        break;
                    case 'bfCheckboxGroup':
                        $element['bfType'] = 'Checkbox Group';
                        $element['data2']  = $mdata['group'];
                        break;
                    case 'bfSignature':
                        $element['bfType'] = 'Signature';
                        break;
                    case 'bfCheckbox':
                        $element['bfType']                   = 'Checkbox';
                        if (WidgetOptions::widget($mdata) === 'consent') {
                            $mdata['checked'] = false;
                            $element['options'] = [
                                'widget'        => 'consent',
                                'widgetOptions' => WidgetOptions::options('consent', $mdata),
                            ] + (array) ($element['options'] ?? []);
                        }
                        $element['options']['checked']        = $mdata['checked'];
                        $element['flag1']                    = $mdata['checked'] ? 1 : 0;
                        $element['options']['value']         = $mdata['value'];
                        $element['data1']                    = $mdata['value'];
                        $element['mailbackAccept']           = $mdata['mailbackAccept'];
                        $element['mailbackAcceptConnectWith'] = $mdata['mailbackConnectWith'];
                        break;
                    case 'bfFile':
                        $element['bfType']                          = 'File Upload';
                        $element['options']['allowedFileExtensions'] = strtolower($mdata['allowedFileExtensions']);
                        $element['options']['timestamp']             = $mdata['timestamp'];
                        $element['options']['useUrl']                = $mdata['useUrl'] ?? false;
                        $element['options']['html5']                 = $mdata['html5'] ?? false;
                        $element['options']['useUrlDownloadDirectory'] = $mdata['useUrlDownloadDirectory'] ?? false;
                        $element['options']['resize_target_width']   = $mdata['resize_target_width'] ?? '';
                        $element['options']['resize_target_height']  = $mdata['resize_target_height'] ?? '';
                        $element['options']['resize_type']           = $mdata['resize_type'] ?? '';
                        $element['options']['resize_bgcolor']        = $mdata['resize_bgcolor'] ?? '';
                        $element['flag1']                           = $mdata['timestamp'] ? 1 : 0;
                        $element['options']['uploadDirectory']       = $mdata['uploadDirectory'];
                        $element['data1']                           = $mdata['uploadDirectory'];
                        $element['data2']                           = strtolower($mdata['allowedFileExtensions']);
                        $element['options']['attachToAdminMail']     = $mdata['attachToAdminMail'];
                        $element['options']['attachToUserMail']      = $mdata['attachToUserMail'];
                        if (WidgetOptions::hasDropZone($mdata)) {
                            $element['options']['dropZone']          = true;
                        }
                        break;
                    case 'bfSubmitButton':
                        $element['bfType']             = 'Regular Button';
                        $element['options']['value']    = $mdata['value'];
                        $element['options']['readonly'] = false;
                        $element['data1']              = $mdata['value'];
                        break;
                    case 'bfHidden':
                        $element['bfType'] = 'Hidden Input';
                        $element['data1']  = $mdata['value'];
                        break;
                    case 'Summarize':
                        $element['bfType'] = 'Summarize';
                        break;
                    case 'bfCaptcha':
                        $element['bfType'] = 'Captcha';
                        $element['width']  = isset($mdata['width']) ? (int) $mdata['width'] : 230;
                        break;
                    case 'bfNumberInput':
                        $element['bfType'] = 'Number Input';
                        $element['data1']  = $mdata['value'];
                        // Only a rating has a server-side check of its bounds.
                        if (WidgetOptions::widget($mdata) === 'rating') {
                            $element['options'] = [
                                'widget'        => 'rating',
                                'widgetOptions' => WidgetOptions::options('rating', $mdata),
                            ] + (array) ($element['options'] ?? []);
                        }
                        break;
                    case 'bfReCaptcha':
                        $element['bfType']   = 'ReCaptcha';
                        $element['pubkey']   = $mdata['pubkey'];
                        $element['privkey']  = $mdata['privkey'];
                        $element['theme']    = $mdata['theme'];
                        break;
                    case 'bfCalendar':
                    case 'bfCalendarResponsive':
                        $element['bfType'] = 'Calendar';
                        $element['data1']  = $mdata['value'];
                        break;
                    case 'bfPayPal':
                        $element['bfType']                              = 'PayPal';
                        $element['options']['testaccount']               = $mdata['testaccount'];
                        $element['options']['useIpn']                    = $mdata['useIpn'] ?? false;
                        $element['options']['downloadableFile']          = $mdata['downloadableFile'];
                        $element['options']['filepath']                  = $mdata['filepath'];
                        $element['options']['downloadTries']             = $mdata['downloadTries'];
                        $element['options']['business']                  = $mdata['business'];
                        $element['options']['token']                     = $mdata['token'];
                        $element['options']['testBusiness']              = $mdata['testBusiness'];
                        $element['options']['testToken']                 = $mdata['testToken'];
                        $element['options']['itemname']                  = $mdata['itemname'];
                        $element['options']['itemnumber']                = $mdata['itemnumber'];
                        $element['options']['amount']                    = $mdata['amount'];
                        $element['options']['tax']                       = $mdata['tax'];
                        $element['options']['thankYouPage']              = $mdata['thankYouPage'];
                        $element['options']['cancelURL']                 = $mdata['cancelURL'] ?? '';
                        $element['options']['locale']                    = $mdata['locale'];
                        $element['options']['currencyCode']              = $mdata['currencyCode'];
                        $element['options']['image']                     = $mdata['image'];
                        $element['options']['sendNotificationAfterPayment'] = $mdata['sendNotificationAfterPayment'];
                        $element['data1']                                = $mdata['image'];
                        break;
                    case 'bfStripe':
                        $element['bfType']                              = 'Stripe';
                        $element['options']['downloadableFile']          = $mdata['downloadableFile'];
                        $element['options']['filepath']                  = $mdata['filepath'];
                        $element['options']['downloadTries']             = $mdata['downloadTries'];
                        $element['options']['secretKey']                 = $mdata['secretKey'];
                        $element['options']['publishableKey']            = $mdata['publishableKey'];
                        $element['options']['webhookSecret']             = (string) ($mdata['webhookSecret'] ?? '');
                        $element['options']['itemname']                  = $mdata['itemname'];
                        $element['options']['amount']                    = $mdata['amount'];
                        $element['options']['amountSource']              = ($mdata['amountSource'] ?? 'fixed') === 'field' ? 'field' : 'fixed';
                        $element['options']['amountField']               = (string) ($mdata['amountField'] ?? '');
                        $element['options']['thankYouPage']              = $mdata['thankYouPage'];
                        $element['options']['thankYouPageMode']          = ($mdata['thankYouPageMode'] ?? (empty($mdata['thankYouPage']) ? 'bf' : 'url')) === 'url' ? 'url' : 'bf';
                        $element['options']['thankYouPageNumber']        = max(1, (int) ($mdata['thankYouPageNumber'] ?? 2));
                        $element['options']['currencyCode']              = $mdata['currencyCode'];
                        $element['options']['sendNotificationAfterPayment'] = $mdata['sendNotificationAfterPayment'];
                        $element['options']['image']                     = $mdata['image'];
                        $element['options']['emailfield']                = $mdata['emailfield'] ?? '';
                        $element['options']['klarna']                    = (bool) ($mdata['klarna'] ?? false);
                        $element['options']['ideal']                     = (bool) ($mdata['ideal'] ?? false);
                        $element['options']['paymentMethodsMode']        = ($mdata['paymentMethodsMode'] ?? 'manual') === 'auto' ? 'auto' : 'manual';
                        $element['data1']                                = $mdata['image'];
                        break;
                    default:
                        $element['bfType'] = 'Unknown';
                }

                $areas->container[0]['elementCount']++;

                $element['options']['hint'] = $mdata['hint'] ?? '';
                $element['title']               = $mdata['label'];
                $element['name']                = $mdata['bfName'];
                $element['orderNumber']         = $mdata['orderNumber'] != -1 ? $mdata['orderNumber'] : $areas->container[0]['elementCount'];
                $element['tabIndex']            = $mdata['tabIndex'];
                $element['logging']             = $mdata['logging'];
                $element['options']['readonly'] = $mdata['readonly'] ?? false;
                $element['flag2']               = !empty($mdata['readonly']) ? 1 : 0;
                // Validation
                $element['script3id']            = $mdata['validationId'];
                $element['script3code']          = $mdata['validationCode'];
                $element['script3msg']           = $mdata['validationMessage'];
                $element['functionNameScript3']  = $mdata['validationFunctionName'];
                $element['script3cond']          = $mdata['validationCondition'];
                // Init
                $element['script1id']            = $mdata['initId'];
                $element['script1code']          = $mdata['initCode'];
                $element['script1flag1']         = $mdata['initFormEntry'] ?? '';
                $element['script1flag2']         = $mdata['initPageEntry'] ?? '';
                $element['functionNameScript1']  = $mdata['initFunctionName'];
                $element['script1cond']          = $mdata['initCondition'];
                // Action
                $element['script2id']            = $mdata['actionId'];
                $element['script2code']          = $mdata['actionCode'] ?? '';
                $element['script2flag1']         = $mdata['actionClick'] ?? '';
                $element['script2flag2']         = $mdata['actionBlur'] ?? '';
                $element['script2flag3']         = $mdata['actionChange'] ?? '';
                $element['script2flag4']         = $mdata['actionFocus'] ?? '';
                $element['script2flag5']         = $mdata['actionSelect'] ?? '';
                $element['functionNameScript2']  = $mdata['actionFunctionName'];
                $element['script2cond']          = $mdata['actionCondition'] ?? '';
                $element['hideInMailback']       = $mdata['hideInMailback'] ?? false;
                $element['page']                 = $page;
                $element['dbId']                 = $mdata['dbId'];
                $element['qId']                  = $dataObject['attributes']['id'];

                $areas->container[0]['elements'][] = $element;
            }
        }

        if (!empty($dataObject['children'])) {
            foreach ($dataObject['children'] as $child) {
                $this->createAreasFromTree($child, $areas, $page, $writeThemeFiles);
            }
        }
    }

    private function updateDbId(mixed &$dataObject, mixed $id, int $dbId): void
    {
        if (!is_array($dataObject)
            || !isset($dataObject['attributes'], $dataObject['properties'])
            || !is_array($dataObject['attributes'])
            || !is_array($dataObject['properties'])
        ) {
            return;
        }

        if (($dataObject['properties']['type'] ?? null) === 'element'
            && ($dataObject['attributes']['id'] ?? null) === $id
        ) {
            $dataObject['properties']['dbId'] = $dbId;
            return;
        }

        if (isset($dataObject['children'])) {
            foreach ($dataObject['children'] as &$child) {
                $this->updateDbId($child, $id, $dbId);
            }
        }
    }

    /**
     * Whether a saved form whose only payment was the removed Sofort element
     * is about to be stored without any supported payment.
     */
    private function requiresReplacementPayment(int $form, string $templateAreas): bool
    {
        $query = $this->db->createQuery()
            ->select($this->db->quoteName('template_areas'))
            ->from($this->db->quoteName('#__facileforms_forms'))
            ->where($this->db->quoteName('id') . ' = :form')
            ->bind(':form', $form, ParameterType::INTEGER);
        $detector = new SofortOnlyFormDetector();

        return $detector->requiresReplacementPayment((string) $this->db->setQuery($query)->loadResult())
            && !$detector->hasPayment($templateAreas);
    }

    /**
     * @param array<int, array<string, mixed>> $areas
     */
    private function save2(int $form, string $formName, string $formTitle, string $formDesc, string $templateCode, array $areas, int $pages = 1, int $published = 1): int
    {
        $dataObject = json_decode(base64_decode($templateCode), true);
        $mdata      = is_array($dataObject) && is_array($dataObject['properties'] ?? null)
            ? $dataObject['properties']
            : [];
        $now        = (new \Joomla\CMS\Date\Date())->toSql();
        $userId     = (string) $this->getCurrentUser()->username;

        $existsQuery = $this->db->createQuery()
            ->select('id')
            ->from($this->db->quoteName('#__facileforms_forms'))
            ->where($this->db->quoteName('id') . ' = :form')
            ->bind(':form', $form, ParameterType::INTEGER);
        $this->db->setQuery($existsQuery);

        if (count($this->db->loadObjectList()) === 0) {
            if ($form > 0) {
                throw new \RuntimeException('The form to update does not exist.');
            }

            // INSERT new form
            $hasScriptCond = ($mdata['submittedScriptCondidtion'] ?? -1) != -1;
            $templateAreas = (string) json_encode($areas);
            $emailntf      = ($mdata['mailNotification'] ?? false) ? 2 : 0;
            $mailRecipient = (string) ($mdata['mailRecipient'] ?? '');

            $columns = [
                'package', 'template_code', 'template_areas', 'published', 'name', 'title', 'description',
                'class1', 'width', 'height', 'pages', 'emailntf', 'emailadr',
            ];
            $placeholders = [
                ':package', ':templateCode', ':templateAreas', ':published', ':name', ':title', ':description',
                ':class1', ':width', ':height', ':pages', ':emailntf', ':emailadr',
            ];

            if ($hasScriptCond) {
                $columns[]      = 'script2cond';
                $columns[]      = 'script2code';
                $placeholders[] = ':script2cond';
                $placeholders[] = ':script2code';
            }

            $columns[]      = 'created';
            $columns[]      = 'created_by';
            $columns[]      = 'modified';
            $columns[]      = 'modified_by';
            $placeholders[] = ':created';
            $placeholders[] = ':createdBy';
            $placeholders[] = ':modified';
            $placeholders[] = ':modifiedBy';

            $package    = 'QuickModeForms';
            $class1     = '';
            $width      = 400;
            $height     = 500;

            $query = $this->db->createQuery()
                ->insert($this->db->quoteName('#__facileforms_forms'))
                ->columns($this->db->quoteName($columns))
                ->values(implode(', ', $placeholders))
                ->bind(':package', $package, ParameterType::STRING)
                ->bind(':templateCode', $templateCode, ParameterType::STRING)
                ->bind(':templateAreas', $templateAreas, ParameterType::STRING)
                ->bind(':published', $published, ParameterType::INTEGER)
                ->bind(':name', $formName, ParameterType::STRING)
                ->bind(':title', $formTitle, ParameterType::STRING)
                ->bind(':description', $formDesc, ParameterType::STRING)
                ->bind(':class1', $class1, ParameterType::STRING)
                ->bind(':width', $width, ParameterType::INTEGER)
                ->bind(':height', $height, ParameterType::INTEGER)
                ->bind(':pages', $pages, ParameterType::INTEGER)
                ->bind(':emailntf', $emailntf, ParameterType::INTEGER)
                ->bind(':emailadr', $mailRecipient, ParameterType::STRING)
                ->bind(':created', $now, ParameterType::STRING)
                ->bind(':createdBy', $userId, ParameterType::STRING)
                ->bind(':modified', $now, ParameterType::STRING)
                ->bind(':modifiedBy', $userId, ParameterType::STRING);

            if ($hasScriptCond) {
                $submittedScriptCond = $mdata['submittedScriptCondidtion'];
                $submittedScriptCode = (string) $mdata['submittedScriptCode'];
                $query->bind(':script2cond', $submittedScriptCond, ParameterType::STRING)
                    ->bind(':script2code', $submittedScriptCode, ParameterType::STRING);
            }

            $this->db->setQuery($query)->execute();
            $form = (int) $this->db->insertid();
        } else {
            // UPDATE — split template_code into 60 KB chunks to avoid MySQL gone-away errors
            $chunks        = $this->chunkString($templateCode, 60000);
            $paymentRequired = $this->requiresReplacementPayment($form, (string) json_encode($areas));
            if ($paymentRequired) {
                $areas[0]['sofortPaymentRequired'] = true;
            }
            $templateAreas = (string) json_encode($areas);

            $emailntf  = 0;
            $recipient = trim((string) ($mdata['mailRecipient'] ?? ''));
            if (!empty($mdata['mailNotification']) && $recipient === '') {
                $emailntf = 1;
            } elseif (!empty($mdata['mailNotification']) && $recipient !== '') {
                $emailntf = 2;
            }
            $mailRecipient = (string) ($mdata['mailRecipient'] ?? '');

            $query = $this->db->createQuery()
                ->update($this->db->quoteName('#__facileforms_forms'))
                ->set($this->db->quoteName('template_code') . " = ''")
                ->set($this->db->quoteName('template_areas') . ' = :templateAreas')
                ->set($this->db->quoteName('name') . ' = :name')
                ->set($this->db->quoteName('title') . ' = :title')
                ->set($this->db->quoteName('description') . ' = :description')
                ->set($this->db->quoteName('pages') . ' = :pages')
                ->set($this->db->quoteName('emailntf') . ' = :emailntf')
                ->set($this->db->quoteName('emailadr') . ' = :emailadr')
                ->set($this->db->quoteName('modified') . ' = :modified')
                ->set($this->db->quoteName('modified_by') . ' = :modifiedBy')
                ->where($this->db->quoteName('id') . ' = :form')
                ->bind(':templateAreas', $templateAreas, ParameterType::STRING)
                ->bind(':name', $formName, ParameterType::STRING)
                ->bind(':title', $formTitle, ParameterType::STRING)
                ->bind(':description', $formDesc, ParameterType::STRING)
                ->bind(':pages', $pages, ParameterType::INTEGER)
                ->bind(':emailntf', $emailntf, ParameterType::INTEGER)
                ->bind(':emailadr', $mailRecipient, ParameterType::STRING)
                ->bind(':modified', $now, ParameterType::STRING)
                ->bind(':modifiedBy', $userId, ParameterType::STRING)
                ->bind(':form', $form, ParameterType::INTEGER);

            if ($paymentRequired) {
                // QuickMode drops the removed Sofort element on load: the form
                // would become free, so it stays unpublished until a payment is added.
                $query->set($this->db->quoteName('published') . ' = 0');
            }

            if (($mdata['submittedScriptCondidtion'] ?? -1) != -1) {
                $submittedScriptCond = $mdata['submittedScriptCondidtion'];
                $submittedScriptCode = (string) $mdata['submittedScriptCode'];
                $query->set($this->db->quoteName('script2cond') . ' = :script2cond')
                    ->set($this->db->quoteName('script2code') . ' = :script2code')
                    ->bind(':script2cond', $submittedScriptCond, ParameterType::STRING)
                    ->bind(':script2code', $submittedScriptCode, ParameterType::STRING);
            }

            $this->db->setQuery($query)->execute();

            foreach ($chunks as $chunk) {
                $appendQuery = $this->db->createQuery()
                    ->update($this->db->quoteName('#__facileforms_forms'))
                    ->set($this->db->quoteName('template_code') . " = CONCAT(" . $this->db->quoteName('template_code') . ', :chunk)')
                    ->where($this->db->quoteName('id') . ' = :form')
                    ->bind(':chunk', $chunk, ParameterType::STRING)
                    ->bind(':form', $form, ParameterType::INTEGER);
                $this->db->setQuery($appendQuery)->execute();
            }
        }

        // Sync elements
        $keepIds      = [];
        $elementCount = 0;

        // Elements this form owns: an id outside this set (another form's element) is never updated.
        $ownedQuery = $this->db->createQuery()
            ->select($this->db->quoteName('id'))
            ->from($this->db->quoteName('#__facileforms_elements'))
            ->where($this->db->quoteName('form') . ' = :form')
            ->bind(':form', $form, ParameterType::INTEGER);
        $ownedIds = array_map('intval', (array) $this->db->setQuery($ownedQuery)->loadColumn());

        foreach ($areas[0]['elements'] as $element) {
            $elementId = -1;
            $fields = $this->elementFields($element, $form, $elementCount);

            if ($element['dbId'] != 0) {
                // Fix ids of copied elements
                $elementName = (string) $element['name'];
                $checkQuery = $this->db->createQuery()
                    ->select('id')
                    ->from($this->db->quoteName('#__facileforms_elements'))
                    ->where($this->db->quoteName('name') . ' = :name')
                    ->where($this->db->quoteName('form') . ' = :form')
                    ->bind(':name', $elementName, ParameterType::STRING)
                    ->bind(':form', $form, ParameterType::INTEGER);
                $this->db->setQuery($checkQuery);

                $elementCheck = $this->db->loadObjectList();

                foreach ($elementCheck as $check) {
                    if ((int) $check->id !== (int) $element['dbId']) {
                        $element['dbId'] = (int) $check->id;
                        $areas[0]['elements'][$elementCount]['dbId'] = (int) $check->id;
                        $this->updateDbId($dataObject, $areas[0]['elements'][$elementCount]['qId'], (int) $check->id);
                        $fields = $this->elementFields($element, $form, $elementCount);
                    }
                }

                if (!in_array((int) $element['dbId'], $ownedIds, true)) {
                    // Not an element of this form: treated as a new element, the other form's row is untouched.
                    $element['dbId'] = 0;
                    $areas[0]['elements'][$elementCount]['dbId'] = 0;
                }
            }

            if ($element['dbId'] == 0) {
                $columns = array_keys($fields);
                $placeholders = array_map(static fn (string $col): string => ':' . $col, $columns);
                $query = $this->db->createQuery()
                    ->insert($this->db->quoteName('#__facileforms_elements'))
                    ->columns($this->db->quoteName($columns))
                    ->values(implode(', ', $placeholders));
                $this->bindFields($query, $fields);

                $bError = false;
                try {
                    $this->db->setQuery($query)->execute();
                } catch (\InvalidArgumentException $e) {
                    $bError = true;
                }

                if (!$bError) {
                    $elementId = (int) $this->db->insertid();
                    $areas[0]['elements'][$elementCount]['dbId'] = $elementId;
                    $this->updateDbId($dataObject, $areas[0]['elements'][$elementCount]['qId'], $elementId);
                }
            } else {
                $dbId = (int) $element['dbId'];

                if ($this->storedElementCode !== null) {
                    // The element found by its name may differ from the one of the tree: it keeps its own code.
                    $fields = (new FormCodeGuard())->applyToElement($fields, $this->storedElementCode[$dbId] ?? null, false)['data'];
                }

                $query = $this->db->createQuery()->update($this->db->quoteName('#__facileforms_elements'));
                foreach (array_keys($fields) as $col) {
                    $query->set($this->db->quoteName($col) . ' = :' . $col);
                }
                $query->where($this->db->quoteName('id') . ' = :dbId')
                    ->where($this->db->quoteName('form') . ' = :ownerForm')
                    ->bind(':dbId', $dbId, ParameterType::INTEGER)
                    ->bind(':ownerForm', $form, ParameterType::INTEGER);
                $this->bindFields($query, $fields);

                try {
                    $this->db->setQuery($query)->execute();
                } catch (\InvalidArgumentException $e) {
                    // ignore
                }

                $elementId = (int) $element['dbId'];
            }

            $keepIds[] = $elementId;
            $elementCount++;
        }

        // Delete elements not in the current save set
        $deleteQuery = $this->db->createQuery()
            ->delete($this->db->quoteName('#__facileforms_elements'))
            ->where($this->db->quoteName('form') . ' = :form')
            ->bind(':form', $form, ParameterType::INTEGER);
        if ($keepIds !== []) {
            $deleteQuery->whereNotIn($this->db->quoteName('id'), $keepIds, ParameterType::INTEGER);
        }
        $this->db->setQuery($deleteQuery)->execute();

        // Write final template_code (chunked)
        $finalCode     = base64_encode((string) json_encode($dataObject));
        $finalChunks   = $this->chunkString($finalCode, 60000);
        $templateAreas = (string) json_encode($areas);

        $finalQuery = $this->db->createQuery()
            ->update($this->db->quoteName('#__facileforms_forms'))
            ->set($this->db->quoteName('template_code') . " = ''")
            ->set($this->db->quoteName('template_code_processed') . " = 'QuickMode'")
            ->set($this->db->quoteName('template_areas') . ' = :templateAreas')
            ->where($this->db->quoteName('id') . ' = :form')
            ->bind(':templateAreas', $templateAreas, ParameterType::STRING)
            ->bind(':form', $form, ParameterType::INTEGER);
        $this->db->setQuery($finalQuery)->execute();

        foreach ($finalChunks as $chunk) {
            $appendQuery = $this->db->createQuery()
                ->update($this->db->quoteName('#__facileforms_forms'))
                ->set($this->db->quoteName('template_code') . " = CONCAT(" . $this->db->quoteName('template_code') . ', :chunk)')
                ->where($this->db->quoteName('id') . ' = :form')
                ->bind(':chunk', $chunk, ParameterType::STRING)
                ->bind(':form', $form, ParameterType::INTEGER);
            $this->db->setQuery($appendQuery)->execute();
        }

        return $form;
    }

    /**
     * Column => value map shared by the element INSERT and UPDATE branches
     * of save2().
     *
     * @param array<string, mixed> $element
     *
     * @return array<string, mixed>
     */
    private function elementFields(array $element, int $form, int $elementCount): array
    {
        return [
            'mailback'     => $element['mailback'],
            'mailbackfile' => $element['mailbackfile'],
            'form'         => $form,
            'page'         => $element['page'] ?? 1,
            'published'    => 1,
            'ordering'     => $element['orderNumber'],
            'name'         => $element['name'],
            'title'        => $element['title'],
            'type'         => $element['bfType'],
            'class1'       => '',
            'class2'       => '',
            'logging'      => (int) ($element['logging'] ?? 1),
            'posx'         => 0,
            'posxmode'     => 0,
            'posy'         => 40 * $elementCount,
            'posymode'     => 0,
            'width'        => 20,
            'widthmode'    => 0,
            'height'       => 20,
            'heightmode'   => 0,
            'flag1'        => $element['flag1'],
            'flag2'        => $element['flag2'],
            'data1'        => $element['data1'],
            'data2'        => $element['data2'],
            'data3'        => $element['data3'],
            'script1cond'  => $element['script1cond'],
            'script1id'    => $element['script1id'],
            'script1code'  => $element['script1code'],
            'script1flag1' => $element['script1flag1'],
            'script1flag2' => $element['script1flag2'],
            'script2cond'  => $element['script2cond'],
            'script2id'    => $element['script2id'],
            'script2code'  => $element['script2code'],
            'script2flag1' => $element['script2flag1'],
            'script2flag2' => $element['script2flag2'],
            'script2flag3' => $element['script2flag3'],
            'script2flag4' => $element['script2flag4'],
            'script2flag5' => $element['script2flag5'],
            'script3cond'  => $element['script3cond'],
            'script3id'    => $element['script3id'],
            'script3code'  => $element['script3code'],
            'script3msg'   => $element['script3msg'],
        ];
    }

    /**
     * Binds a column => value map built by elementFields() onto a query
     * using ":<column>" as the placeholder name for each column.
     *
     * @param array<string, mixed> $fields
     */
    private function bindFields(\Joomla\Database\QueryInterface $query, array $fields): void
    {
        // Bind directly against each array slot (not a shared loop-local
        // scalar) since Joomla's bind() takes the variable by reference -
        // reusing one variable across iterations would leave every
        // placeholder pointing at whatever the last iteration wrote to it.
        foreach ($fields as $col => $value) {
            $type = match (true) {
                is_int($value), is_bool($value) => ParameterType::INTEGER,
                default => ParameterType::STRING,
            };
            $fields[$col] = is_bool($value) ? (int) $value : (string) $value;
            $query->bind(':' . $col, $fields[$col], $type);
        }
    }

    /**
     * @return list<string>
     */
    private function chunkString(string $str, int $size): array
    {
        $chunks = [];
        $length = strlen($str);
        $chunk  = '';
        $cnt    = 0;

        for ($i = 0; $i < $length; $i++) {
            $chunk .= $str[$i];
            $cnt++;
            if ($cnt === $size || $i + 1 === $length) {
                $chunks[] = $chunk;
                $chunk    = '';
                $cnt      = 0;
            }
        }

        return $chunks;
    }
}
