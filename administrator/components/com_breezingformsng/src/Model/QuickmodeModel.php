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
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomla\Filesystem\File;

class QuickmodeModel extends BaseDatabaseModel
{
    private DatabaseInterface $db;

    public function __construct(array $config = [])
    {
        parent::__construct($config);
        $this->db = $this->getDatabase();
    }

    public function save(int $form, array $dataObject): int
    {
        if (isset($dataObject['properties']) && is_array($dataObject['properties'])) {
            unset(
                $dataObject['properties']['themebootstrapUse3'],
                $dataObject['properties']['themebootstrap3builtin'],
                $dataObject['properties']['themebootstrap3classpfx'],
                $dataObject['properties']['themeusebootstraplegacy']
            );
        }

        $areas = new \stdClass();
        $areas->container    = [];
        $areas->container[0] = ['elements' => [], 'elementCount' => 0];

        $this->createAreasFromTree($dataObject, $areas);

        // Inject submit-related fake elements from published scripts.
        $fakes = ['ff_validate_submit', 'ff_resetForm', 'ff_validate_prevpage', 'ff_validate_nextpage'];
        foreach ($fakes as $idx => $scriptName) {
            $query = $this->db->getQuery(true)
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

        $mdata = $dataObject['properties'];

        return $this->save2(
            $form,
            (string) ($mdata['name'] ?? ''),
            (string) ($mdata['title'] ?? ''),
            (string) ($mdata['description'] ?? ''),
            base64_encode((string) json_encode($dataObject)),
            $areas->container,
            count($dataObject['children'] ?? [])
        );
    }

    public function getFormOptions(int $form): ?\stdClass
    {
        $query = $this->db->getQuery(true)
            ->select(['package', 'name', 'title', 'description', 'emailntf', 'emailadr', 'published', 'debug_mode'])
            ->from($this->db->quoteName('#__facileforms_forms'))
            ->where($this->db->quoteName('id') . ' = :form')
            ->bind(':form', $form, ParameterType::INTEGER);
        $this->db->setQuery($query);
        $list = $this->db->loadObjectList();

        return count($list) === 1 ? $list[0] : null;
    }

    public function getTemplateCode(int $form): string
    {
        $query = $this->db->getQuery(true)
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

    public function getElementScripts(): array
    {
        $result = [];

        foreach (['Element Validation' => 'validation', 'Element Action' => 'action', 'Element Init' => 'init'] as $type => $key) {
            $query = $this->db->getQuery(true)
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

    public function getThemes(): array
    {
        return $this->scanThemeDir(JPATH_SITE . '/media/breezingforms/themes/');
    }

    public function getThemesBootstrap(): array
    {
        return $this->scanThemeDir(JPATH_SITE . '/media/breezingforms/themes-bootstrap5/');
    }

    // ── Private helpers ───────────────────────────────────────────────────────

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

    private function createAreasFromTree(array $dataObject, \stdClass $areas, int $page = 1): void
    {
        $element = $this->getDefaultElement();

        if (isset($dataObject['attributes'], $dataObject['properties'])) {
            $mdata = $dataObject['properties'];

            if ($mdata['type'] === 'root') {
                if (!empty($mdata['themebootstrap']) && !empty($mdata['themebootstrapvars'])
                    && isset($mdata['themebootstrapbefore'])
                    && $mdata['themebootstrapbefore'] === $mdata['themebootstrap']
                ) {
                    $folder   = 'themes-bootstrap5';
                    $varspath = JPATH_SITE . '/media/breezingforms/' . $folder . '/' . $mdata['themebootstrap'] . '/vars.txt';
                    if (file_exists($varspath)) {
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
                        $element['data1']              = $mdata['value'];
                        $element['options']['password'] = $mdata['password'];
                        $element['flag1']              = $mdata['password'] ? 1 : 0;
                        $element['options']['mailback'] = $mdata['mailback'];
                        $element['mailback']           = $mdata['mailback'] ? 1 : 0;
                        $element['mailbackAsSender']   = $mdata['mailbackAsSender'] ? 1 : 0;
                        $element['mailbackfile']       = $mdata['mailbackfile'];
                        break;
                    case 'bfTextarea':
                        $element['bfType']             = 'Textarea';
                        $element['options']['value']   = $mdata['value'];
                        $element['data1']              = $mdata['value'];
                        $element['options']['placeholder'] = $mdata['placeholder'] ?? '';
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
                        $element['options']['itemname']                  = $mdata['itemname'];
                        $element['options']['amount']                    = $mdata['amount'];
                        $element['options']['thankYouPage']              = $mdata['thankYouPage'];
                        $element['options']['currencyCode']              = $mdata['currencyCode'];
                        $element['options']['sendNotificationAfterPayment'] = $mdata['sendNotificationAfterPayment'];
                        $element['options']['image']                     = $mdata['image'];
                        $element['options']['emailfield']                = $mdata['emailfield'] ?? '';
                        $element['data1']                                = $mdata['image'];
                        break;
                    case 'bfSofortueberweisung':
                        $element['bfType']                              = 'Sofortueberweisung';
                        $element['options']['mailback']                  = $mdata['mailback'];
                        $element['options']['downloadableFile']          = $mdata['downloadableFile'];
                        $element['options']['filepath']                  = $mdata['filepath'];
                        $element['options']['downloadTries']             = $mdata['downloadTries'];
                        $element['options']['user_id']                   = $mdata['user_id'];
                        $element['options']['project_id']                = $mdata['project_id'];
                        $element['options']['project_password']          = $mdata['project_password'];
                        $element['options']['reason_1']                  = $mdata['reason_1'];
                        $element['options']['reason_2']                  = $mdata['reason_2'];
                        $element['options']['amount']                    = $mdata['amount'];
                        $element['options']['thankYouPage']              = $mdata['thankYouPage'];
                        $element['options']['language_id']               = $mdata['language_id'];
                        $element['options']['currency_id']               = $mdata['currency_id'];
                        $element['options']['image']                     = $mdata['image'];
                        $element['options']['sendNotificationAfterPayment'] = $mdata['sendNotificationAfterPayment'] ?? false;
                        $element['data1']                                = $mdata['image'];
                        break;
                    default:
                        $element['bfType'] = 'Unknown';
                }

                $areas->container[0]['elementCount']++;

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
                $this->createAreasFromTree($child, $areas, $page);
            }
        }
    }

    private function updateDbId(array &$dataObject, mixed $id, int $dbId): void
    {
        if (isset($dataObject['attributes'], $dataObject['properties'])) {
            if ($dataObject['properties']['type'] === 'element' && $dataObject['attributes']['id'] === $id) {
                $dataObject['properties']['dbId'] = $dbId;
                return;
            }
        }

        if (!empty($dataObject['children'])) {
            foreach ($dataObject['children'] as &$child) {
                $this->updateDbId($child, $id, $dbId);
            }
        }
    }

    private function save2(int $form, string $formName, string $formTitle, string $formDesc, string $templateCode, array $areas, int $pages = 1): int
    {
        $dataObject = json_decode(base64_decode($templateCode), true);
        $mdata      = $dataObject['properties'];
        $now        = (new \Joomla\CMS\Date\Date())->toSql();
        $userId     = (string) $this->getCurrentUser()->username;

        $existsQuery = $this->db->getQuery(true)
            ->select('id')
            ->from($this->db->quoteName('#__facileforms_forms'))
            ->where($this->db->quoteName('id') . ' = :form')
            ->bind(':form', $form, ParameterType::INTEGER);
        $this->db->setQuery($existsQuery);

        if (count($this->db->loadObjectList()) === 0) {
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
            $published  = 1;
            $class1     = '';
            $width      = 400;
            $height     = 500;

            $query = $this->db->getQuery(true)
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
            $templateAreas = (string) json_encode($areas);

            $emailntf  = 0;
            $recipient = trim((string) ($mdata['mailRecipient'] ?? ''));
            if (!empty($mdata['mailNotification']) && $recipient === '') {
                $emailntf = 1;
            } elseif (!empty($mdata['mailNotification']) && $recipient !== '') {
                $emailntf = 2;
            }
            $mailRecipient = (string) ($mdata['mailRecipient'] ?? '');

            $query = $this->db->getQuery(true)
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
                $appendQuery = $this->db->getQuery(true)
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

        foreach ($areas[0]['elements'] as $element) {
            $elementId = -1;
            $fields = $this->elementFields($element, $form, $elementCount);

            if ($element['dbId'] == 0) {
                $columns = array_keys($fields);
                $placeholders = array_map(static fn (string $col): string => ':' . $col, $columns);
                $query = $this->db->getQuery(true)
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
                // Fix ids of copied elements
                $elementName = (string) $element['name'];
                $checkQuery = $this->db->getQuery(true)
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

                $dbId = (int) $element['dbId'];
                $query = $this->db->getQuery(true)->update($this->db->quoteName('#__facileforms_elements'));
                foreach (array_keys($fields) as $col) {
                    $query->set($this->db->quoteName($col) . ' = :' . $col);
                }
                $query->where($this->db->quoteName('id') . ' = :dbId')
                    ->bind(':dbId', $dbId, ParameterType::INTEGER);
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
        $deleteQuery = $this->db->getQuery(true)
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

        $finalQuery = $this->db->getQuery(true)
            ->update($this->db->quoteName('#__facileforms_forms'))
            ->set($this->db->quoteName('template_code') . " = ''")
            ->set($this->db->quoteName('template_code_processed') . " = 'QuickMode'")
            ->set($this->db->quoteName('template_areas') . ' = :templateAreas')
            ->where($this->db->quoteName('id') . ' = :form')
            ->bind(':templateAreas', $templateAreas, ParameterType::STRING)
            ->bind(':form', $form, ParameterType::INTEGER);
        $this->db->setQuery($finalQuery)->execute();

        foreach ($finalChunks as $chunk) {
            $appendQuery = $this->db->getQuery(true)
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
     */
    private function bindFields(\Joomla\Database\DatabaseQuery $query, array $fields): void
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
