<?php

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Site\Service\Rendering;
/**
 * BreezingForms NG - A Joomla Forms Application
 *
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2008-2020 by Markus Bopp
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 *
 * SPDX-License-Identifier: GPL-2.0-or-later
 **/

defined('_JEXEC') or die('Direct Access to this location is not allowed.');

use Joomla\CMS\Filter\InputFilter;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomla\Event\Event;
use Joomla\Event\EventInterface;
use Joomla\CMS\Uri\Uri;
use Joomla\Filesystem\Folder;
use Joomla\Filesystem\File;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\Filesystem\Path;
use Joomla\CMS\Environment\Browser;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Log\Log;
use Exception;
use Closure;
use Vcmb\Component\BreezingformsNG\Site\Table\QueryColumn;
use HTML_facileFormsProcessor;
use CB\Component\Contentbuilderng\Administrator\Helper\ContentbuilderngHelper;
use CB\Component\Contentbuilderng\Administrator\Helper\FormSourceFactory;
use CB\Component\Contentbuilderng\Administrator\Service\ArticleService;
use CB\Component\Contentbuilderng\Administrator\Service\ListSupportService;
use CB\Component\Contentbuilderng\Administrator\Service\PermissionService;
use CB\Component\Contentbuilderng\Administrator\Helper\RuntimeContextHelper;
use Vcmb\Component\BreezingformsNG\Site\Service\Upload\TokenizedDirectoryResolver;
use Vcmb\Component\BreezingformsNG\Site\Service\Runtime\RuntimeAssetLoader;
use Vcmb\Component\BreezingformsNG\Site\Service\Integration\ContentBuilderInstallation;

/**
 * Page header, ContentBuilder path handling and form view rendering.
 */
final class RenderingEngine
{
    private ?TokenizedDirectoryResolver $tokenizedDirectoryResolverService = null;
    private ?ProcessorHeaderRenderer $processorHeaderRendererService = null;
    private ?ClassicFormRenderer $classicFormRendererService = null;
    private ?ContentBuilderValueScriptBuilder $contentBuilderValueScriptBuilderService = null;
    private ?EditableRecordHydrationScriptBuilder $editableRecordHydrationScriptBuilderService = null;
    private ?HiddenFormFieldsBuilder $hiddenFormFieldsBuilderService = null;
    private ?LegacyScriptTagWrapperBuilder $legacyScriptTagWrapperBuilderService = null;
    private ?ContentBuilderHydrationScriptBuilder $contentBuilderHydrationScriptBuilderService = null;
    private ?EditableRecordLoader $editableRecordLoaderService = null;
    private ?PostRenderScriptBuilder $postRenderScriptBuilderService = null;
    private ?FormEnvelopeMarkupBuilder $formEnvelopeMarkupBuilderService = null;
    private ?QuickModeFormTagBuilder $quickModeFormTagBuilderService = null;
    private ?FileExtensionsCheckBuilder $fileExtensionsCheckBuilderService = null;
    private ?QueryListSelectAllScriptBuilder $queryListSelectAllScriptBuilderService = null;
    private ?QueryListStateLibraryBuilder $queryListStateLibraryBuilderService = null;
    private ?QueryListRowStateBuilder $queryListRowStateBuilderService = null;
    private ?PaymentProviderDetector $paymentProviderDetectorService = null;
    private ?CaptchaSupportBuilder $captchaSupportBuilderService = null;
    private ?CaptchaValidationRowSelector $captchaValidationRowSelectorService = null;
    private ?CaptchaLegacyValidationScriptBuilder $captchaLegacyValidationScriptBuilderService = null;
    private ?CaptchaReCaptchaValidationScriptBuilder $captchaReCaptchaValidationScriptBuilderService = null;
    private ?CaptchaValidationScriptBuilder $captchaValidationScriptBuilderService = null;
    private ?ContentBuilderNonEditableFieldsResolver $contentBuilderNonEditableFieldsResolverService = null;
    private ?ContentBuilderFormMetadataLoader $contentBuilderFormMetadataLoaderService = null;
    private ?ContentBuilderPermissionChecker $contentBuilderPermissionCheckerService = null;
    private ?ContentBuilderRecordLoader $contentBuilderRecordLoaderService = null;
    private ?QuickModeRendererFactory $quickModeRendererFactoryService = null;
    private ?FormOnloadScriptBuilder $formOnloadScriptBuilderService = null;
    private ?QueryListRowPreparationService $queryListRowPreparationService = null;
    private ?QueryListPageScriptBuilder $queryListPageScriptBuilderService = null;
    private ?CallbackRegistrationService $callbackRegistrationService = null;
    private ?FormPieceExecutionService $formPieceExecutionService = null;
    private ?SubmittedCallbackNameResolver $submittedCallbackNameResolverService = null;

    public function __construct(private readonly HTML_facileFormsProcessor $processor)
    {
    }

    public function header(): string
    {
        global $ff_config;

        RuntimeAssetLoader::script(
            $this->processor->app,
            Uri::root(true) . '/media/com_breezingformsng/js/facileforms.js'
        );

        return $this->processorHeaderRenderer()->render(
            [
                'ff_processor.okrun      ' => $this->processor->okrun,
                'ff_processor.ip         ' => $this->processor->ip,
                'ff_processor.agent      ' => $this->processor->agent,
                'ff_processor.browser    ' => $this->processor->browser,
                'ff_processor.opsys      ' => $this->processor->opsys,
                'ff_processor.provider   ' => $this->processor->provider,
                'ff_processor.submitted  ' => $this->processor->submitted,
                'ff_processor.form       ' => $this->processor->form,
                'ff_processor.form_id    ' => $this->processor->form_id,
                'ff_processor.page       ' => $this->processor->page,
                'ff_processor.target     ' => $this->processor->target,
                'ff_processor.runmode    ' => $this->processor->runmode,
                'ff_processor.inframe    ' => $this->processor->inframe,
                'ff_processor.inline     ' => $this->processor->inline,
                'ff_processor.template   ' => $this->processor->template,
                'ff_processor.homepage   ' => $this->processor->homepage,
                'ff_processor.mossite    ' => $this->processor->mossite,
                'ff_processor.images     ' => $this->processor->images,
                'ff_processor.border     ' => $this->processor->border,
                'ff_processor.align      ' => $this->processor->align,
                'ff_processor.top        ' => $this->processor->top,
                'ff_processor.suffix     ' => $this->processor->suffix,
                'ff_processor.status     ' => $this->processor->status,
                'ff_processor.message    ' => $this->processor->message,
                'ff_processor.record_id  ' => $this->processor->record_id,
                'ff_processor.showgrid   ' => $this->processor->showgrid,
                'ff_processor.traceBuffer' => $this->processor->traceBuffer,
            ],
            (bool) $ff_config->compress,
            fn (string $code): string => $this->processor->compressJavascript($code)
        );
    }

    // header

    /**
     * @param array<int, \stdClass> $rows
     */
    public function cbCreatePathByTokens(mixed $path, array $rows, mixed $fieldName): string
    {
        $identity = $this->processor->app->getIdentity()
            ?? throw new \RuntimeException('The current user is not loaded.');

        return $this->tokenizedDirectoryResolver()->resolve(
            (string) $path,
            $rows,
            (string) $fieldName,
            $this->processor->findtags ?? [],
            $this->processor->replacetags ?? [],
            [
                'username' => $identity->get('username', 'anonymous'),
                'id' => $identity->get('id', 0),
                'name' => $identity->get('name', 'Anonymous'),
            ],
            (string) $this->processor->submitted,
            (string) $this->processor->app->get('offset')
        );
    }

    public function makeSafeFolder(mixed $path): string
    {
        return $this->tokenizedDirectoryResolver()->makeSafeFolder((string) $path);
    }

    private function tokenizedDirectoryResolver(): TokenizedDirectoryResolver
    {
        return $this->tokenizedDirectoryResolverService ??=
            new TokenizedDirectoryResolver($this->processor->app->getInput());
    }

    private function processorHeaderRenderer(): ProcessorHeaderRenderer
    {
        return $this->processorHeaderRendererService ??=
            new ProcessorHeaderRenderer(new JavascriptValueExporter());
    }

    private function classicFormRenderer(): ClassicFormRenderer
    {
        return $this->classicFormRendererService ??= new ClassicFormRenderer($this->processor);
    }

    private function contentBuilderValueScriptBuilder(): ContentBuilderValueScriptBuilder
    {
        return $this->contentBuilderValueScriptBuilderService ??= new ContentBuilderValueScriptBuilder();
    }

    private function editableRecordHydrationScriptBuilder(): EditableRecordHydrationScriptBuilder
    {
        return $this->editableRecordHydrationScriptBuilderService ??= new EditableRecordHydrationScriptBuilder(
            $this->contentBuilderValueScriptBuilder(),
            fn(string $value): string => (string) InputFilter::getInstance([], [], 1, 1)->clean($value, 'html')
        );
    }

    private function hiddenFormFieldsBuilder(): HiddenFormFieldsBuilder
    {
        return $this->hiddenFormFieldsBuilderService ??= new HiddenFormFieldsBuilder();
    }

    private function legacyScriptTagWrapperBuilder(): LegacyScriptTagWrapperBuilder
    {
        return $this->legacyScriptTagWrapperBuilderService ??= new LegacyScriptTagWrapperBuilder();
    }

    private function contentBuilderHydrationScriptBuilder(): ContentBuilderHydrationScriptBuilder
    {
        return $this->contentBuilderHydrationScriptBuilderService ??= new ContentBuilderHydrationScriptBuilder(
            fn(string $value): string => (string) InputFilter::getInstance([], [], 1, 1)->clean($value, 'html'),
            fn(string $value, int $width, string $break, bool $cut): string =>
                ContentbuilderngHelper::contentbuilderng_wordwrap($value, $width, $break, $cut)
        );
    }

    private function editableRecordLoader(): EditableRecordLoader
    {
        return $this->editableRecordLoaderService ??= new EditableRecordLoader($this->processor->database);
    }

    private function postRenderScriptBuilder(): PostRenderScriptBuilder
    {
        return $this->postRenderScriptBuilderService ??= new PostRenderScriptBuilder();
    }


    private function buildContentBuilderTechnicalFields(): string
    {
        $input = $this->processor->app->getInput();
        $formId = $input->getInt('cb_form_id', 0);

        return $formId
            ? $this->hiddenFormFieldsBuilder()->contentBuilderTechnical(
                '',
                $formId,
                $input->getInt('cb_record_id', 0),
                $input->getBool('cbIsNew', false)
            )
            : '';
    }

    /**
     * @return array<string, int|string>
     */
    private function buildFormContext(): array
    {
        $input = $this->processor->app->getInput();

        return [
            'ff_contentid' => $input->getInt('ff_contentid', 0),
            'ff_applic' => $input->getWord('ff_applic', ''),
            'ff_record_id' => $this->processor->record_id ?? '',
            'ff_module_id' => $input->getInt('ff_module_id', 0),
        ];
    }




    private function formEnvelopeMarkupBuilder(): FormEnvelopeMarkupBuilder
    {
        return $this->formEnvelopeMarkupBuilderService ??= new FormEnvelopeMarkupBuilder();
    }

    private function quickModeFormTagBuilder(): QuickModeFormTagBuilder
    {
        return $this->quickModeFormTagBuilderService ??= new QuickModeFormTagBuilder();
    }



    private function fileExtensionsCheckBuilder(): FileExtensionsCheckBuilder
    {
        return $this->fileExtensionsCheckBuilderService ??= new FileExtensionsCheckBuilder();
    }

    private function queryListSelectAllScriptBuilder(): QueryListSelectAllScriptBuilder
    {
        return $this->queryListSelectAllScriptBuilderService ??= new QueryListSelectAllScriptBuilder();
    }

    private function queryListStateLibraryBuilder(): QueryListStateLibraryBuilder
    {
        return $this->queryListStateLibraryBuilderService ??= new QueryListStateLibraryBuilder();
    }

    private function queryListRowStateBuilder(): QueryListRowStateBuilder
    {
        return $this->queryListRowStateBuilderService ??= new QueryListRowStateBuilder();
    }


    private function paymentProviderDetector(): PaymentProviderDetector
    {
        return $this->paymentProviderDetectorService ??= new PaymentProviderDetector();
    }

    private function captchaSupportBuilder(): CaptchaSupportBuilder
    {
        return $this->captchaSupportBuilderService ??= new CaptchaSupportBuilder();
    }

    private function captchaValidationRowSelector(): CaptchaValidationRowSelector
    {
        return $this->captchaValidationRowSelectorService ??= new CaptchaValidationRowSelector();
    }


    private function captchaLegacyValidationScriptBuilder(): CaptchaLegacyValidationScriptBuilder
    {
        return $this->captchaLegacyValidationScriptBuilderService ??= new CaptchaLegacyValidationScriptBuilder();
    }

    private function captchaReCaptchaValidationScriptBuilder(): CaptchaReCaptchaValidationScriptBuilder
    {
        return $this->captchaReCaptchaValidationScriptBuilderService ??= new CaptchaReCaptchaValidationScriptBuilder();
    }

    private function captchaValidationScriptBuilder(): CaptchaValidationScriptBuilder
    {
        return $this->captchaValidationScriptBuilderService ??= new CaptchaValidationScriptBuilder(
            $this->captchaSupportBuilder(),
            $this->captchaValidationRowSelector(),
            $this->captchaLegacyValidationScriptBuilder(),
            $this->captchaReCaptchaValidationScriptBuilder()
        );
    }

    private function contentBuilderNonEditableFieldsResolver(): ContentBuilderNonEditableFieldsResolver
    {
        return $this->contentBuilderNonEditableFieldsResolverService ??= new ContentBuilderNonEditableFieldsResolver(
            static fn(int $contentBuilderId): array => ListSupportService::createFromRuntimeContext()
                ->getListNonEditableElements($contentBuilderId)
        );
    }

    private function contentBuilderFormMetadataLoader(): ContentBuilderFormMetadataLoader
    {
        return $this->contentBuilderFormMetadataLoaderService ??= new ContentBuilderFormMetadataLoader(
            $this->processor->database
        );
    }

    private function contentBuilderPermissionChecker(): ContentBuilderPermissionChecker
    {
        return $this->contentBuilderPermissionCheckerService ??= new ContentBuilderPermissionChecker(
            static fn(): ContentBuilderPermissionGateway => new ContentBuilderPermissionServiceAdapter(
                PermissionService::createFromRuntimeContext()
            )
        );
    }

    private function contentBuilderRecordLoader(): ContentBuilderRecordLoader
    {
        return $this->contentBuilderRecordLoaderService ??= new ContentBuilderRecordLoader(
            static function (string $referenceId, int $recordId, bool $publishedOnly, int $ownerId, bool $showAllLanguages): array {
                $form = FormSourceFactory::getForm('com_breezingformsng', $referenceId)
                    ?? throw new \RuntimeException('The ContentBuilder form source is not available.');

                return (array) $form->getRecord($recordId, $publishedOnly, $ownerId, $showAllLanguages);
            }
        );
    }

    private function quickModeRendererFactory(): QuickModeRendererFactory
    {
        return $this->quickModeRendererFactoryService ??= new QuickModeRendererFactory();
    }

    private function formOnloadScriptBuilder(): FormOnloadScriptBuilder
    {
        return $this->formOnloadScriptBuilderService ??= new FormOnloadScriptBuilder();
    }

    private function queryListRowPreparationService(): QueryListRowPreparationService
    {
        return $this->queryListRowPreparationService ??= new QueryListRowPreparationService(
            $this->processor,
            $this->queryListRowStateBuilder()
        );
    }

    private function queryListPageScriptBuilder(): QueryListPageScriptBuilder
    {
        return $this->queryListPageScriptBuilderService ??= new QueryListPageScriptBuilder();
    }

    private function callbackRegistrationService(): CallbackRegistrationService
    {
        return $this->callbackRegistrationService ??= new CallbackRegistrationService($this->processor);
    }

    private function formPieceExecutionService(): FormPieceExecutionService
    {
        return $this->formPieceExecutionService ??= new FormPieceExecutionService(
            $this->processor,
            $this->processor->database
        );
    }

    private function submittedCallbackNameResolver(): SubmittedCallbackNameResolver
    {
        return $this->submittedCallbackNameResolverService ??= new SubmittedCallbackNameResolver(
            $this->processor->database
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function cbCheckPermissions(): array
    {
        // CONTENTBUILDER BEGIN

        $cbData = null;
        $cbForm = null;
        $cbRecord = null;
        $cbFrontend = true;
        $cbFull = false;

        if (ContentBuilderInstallation::isInstalled()) {
            $input = $this->processor->app->getInput();

            if ($this->processor->app->isClient('administrator')) {
                $cbFrontend = false;
            }

            $language = $this->processor->app->getLanguage()
                ?? throw new \RuntimeException('The language is not loaded.');

            if ($cbFrontend) {
                $language->load('com_contentbuilderng');
            } else {
                $language->load('com_contentbuilderng', JPATH_SITE . '/administrator');
            }

            $db = $this->processor->database;
            $cbForms = $this->contentBuilderFormMetadataLoader()->loadAssociatedFormIds((int) $this->processor->form);

            // if no BF form is associated with contentbuilder, we don't need no further checks
            if (!count($cbForms)) {
                return array('form' => $cbForm, 'record' => $cbRecord, 'frontend' => $cbFrontend, 'data' => $cbData, 'full' => $cbFull);
            }

            // test if all published contentbuilder views allow creating new submissions
            if (!$input->getInt('cb_record_id', 0) || !$input->getInt('cb_form_id', 0)) {

                $this->contentBuilderPermissionChecker()->assertCanCreate(
                    $cbForms,
                    $cbFrontend,
                    Text::_('COM_CONTENTBUILDERNG_PERMISSIONS_NEW_NOT_ALLOWED'),
                    Text::_('COM_CONTENTBUILDERNG_PERMISSIONS_NEW_NOT_ALLOWED')
                );
            }

            if ($input->getInt('cb_form_id', 0)) {

                // test the permissions of given record
                $permissionService = $this->contentBuilderPermissionChecker()->assertCanEditOrCreate(
                    $input->getInt('cb_form_id', 0),
                    $input->getInt('cb_record_id', 0),
                    $cbFrontend,
                    Text::_('COM_CONTENTBUILDERNG_PERMISSIONS_EDIT_NOT_ALLOWED'),
                    Text::_('COM_CONTENTBUILDERNG_PERMISSIONS_NEW_NOT_ALLOWED')
                );

                $cbFormId = $input->getInt('cb_form_id', 0);
                $cbData = $this->contentBuilderFormMetadataLoader()->loadForm($cbFormId);
                if (is_array($cbData)) {
                    $cbFull = $this->contentBuilderPermissionChecker()->canViewFullArticle($permissionService, $cbFrontend);
                    $cbForm = FormSourceFactory::getForm('com_breezingformsng', $cbData['reference_id']);
                    $identity = $this->processor->app->getIdentity()
                        ?? throw new \RuntimeException('The current user is not loaded.');
                    $cbRecord = $this->contentBuilderRecordLoader()->load(
                        $cbData,
                        $input->getInt('cb_record_id', 0),
                        $cbFrontend,
                        (int) $identity->get('id', 0),
                        $input->getBool('cbIsNew', false),
                        Text::_('COM_CONTENTBUILDERNG_RECORD_NOT_FOUND')
                    );
                }
            }
        }
        return array('form' => $cbForm, 'record' => $cbRecord, 'frontend' => $cbFrontend, 'data' => $cbData, 'full' => $cbFull);
        // CONTENTBUILDER END
    }

    public function view(): void
    {
        global $ff_mospath, $ff_mossite, $my;
        global $ff_config, $ff_version, $ff_comsite, $ff_otherparams;

        if (trim((string) $this->processor->formrow->template_code_processed) !== 'QuickMode') {
            echo '<div class="alert alert-warning">' . Text::_('COM_BREEZINGFORMSNG_QUICKMODE_ONLY') . '</div>';
            return;
        }

        require_once JPATH_SITE . '/administrator/components/com_breezingformsng/libraries/crosstec/functions/helpers.php';

        $rootMdata = $this->loadQuickModeMetadata();

        // CONTENTBUILDER BEGIN
        $cbResult = $this->processor->cbCheckPermissions();

        $cbForm = $cbResult['form'];
        $cbRecord = $cbResult['record'];
        $cbFrontend = $cbResult['frontend'];
        $cbFull = $cbResult['full'];
        // CONTENTBUILDER END

        if (!$this->processor->okrun)
            return;
        set_error_handler('_ff_errorHandler');
        ob_start();
        echo $this->processor->header();
        $this->initializeFormRendering();

        if ($this->executeBeforeFormPiece()) {
            $this->abortViewRendering();
            return;
        }

        [$fileExtensionsCheck, $cntFiles] = $this->fileExtensionsCheckBuilder()->build(
            $this->processor->rows ?? [],
            $this->processor->rowcount ?? 0,
            json_encode(
                Text::_('COM_BREEZINGFORMSNG_FILE_EXTENSION_NOT_ALLOWED'),
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR
            ),
            trim((string) $this->processor->formrow->template_code) != ''
        );

        $capFunc = $this->captchaValidationScriptBuilder()->build(
            Uri::root(true),
            $this->processor->app->isClient('administrator'),
            (int) $this->processor->form,
            $this->processor->rows ?? [],
            $this->processor->rowcount ?? 0,
            Text::_('COM_BREEZINGFORMSNG_CAPTCHA_MISSING_WRONG')
        );

        echo $this->legacyScriptTagWrapperBuilder()->formValidationOpen(
            $fileExtensionsCheck,
            $capFunc,
            nl()
        );

        [$library, $linked] = $this->createScriptLibraryState();

        if ($this->processor->status == '') {
            $this->linkInitialOnload($library, $linked);
        } else {
            $this->linkSubmittedOnload($library, $linked);
        } // if
        if ($this->processor->bury()) {
            $this->abortViewRendering();
            return;
        }

        // add form scripts
        if ($this->addFormScripts($library, $linked)) {
            $this->abortViewRendering();
            return;
        }

        // all element scripts & static text/HTML
        $icons = 0;
        $tooltips = 0;
        $qcheckboxes = 0;
        $qcode = '';

        for ($i = 0; $i < $this->processor->rowcount; $i++) {
            $row = &$this->processor->rows[$i];

            $this->processor->draggableDivIds[] = 'ff_div' . $row->id;

            $this->collectElementMetadata($row, $icons, $tooltips);
            if ($row->type == "Query List") {
                $this->prepareQueryListRow($row, $qcheckboxes, $qcode);
                if ($this->processor->bury()) {
                    $this->abortViewRendering();
                    return;
                }
            } // if
            if ($this->registerElementCallbacks($row, $library, $linked)) {
                unset($row);
                $this->abortViewRendering();
                return;
            }
            $this->registerStaticTextScanCallback($row, $library, $linked);
            unset($row);
            if ($this->processor->bury()) {
                $this->abortViewRendering();
                return;
            }
        } // for

        if ($icons > 0) {
            if ($this->registerIconBorderScripts($library, $linked)) {
                $this->abortViewRendering();
                return;
            }
        } // if

        if ($qcode != '') {
            foreach ($this->queryListStateLibraryBuilder()->build($qcode, nl()) as $entry) {
                $library[] = $entry;
            }

            $library[] = array(
                'ff_selectAllQueryRows',
                $this->queryListSelectAllScriptBuilder()->build(nl())
            );

            $code = $this->queryListPageScriptBuilder()->build(
                [
                    'start' => Text::_('COM_BREEZINGFORMSNG_PROCESS_PAGESTART'),
                    'previous' => Text::_('COM_BREEZINGFORMSNG_PROCESS_PAGEPREV'),
                    'next' => Text::_('COM_BREEZINGFORMSNG_PROCESS_PAGENEXT'),
                    'end' => Text::_('COM_BREEZINGFORMSNG_PROCESS_PAGEEND'),
                ],
                (bool) $qcheckboxes,
                (int) $this->processor->formrow->heightmode,
                (int) $this->processor->formrow->height,
                (bool) $this->processor->inframe,
                nl()
            );
            $this->processor->linkcode('ff_dispQueryPage', $library, $linked, $code);
            if ($this->processor->bury()) {
                $this->abortViewRendering();
                return;
            }
        } // if

        echo $this->legacyScriptTagWrapperBuilder()->formValidationClose(nl());

        if ($icons > 0) {
            RuntimeAssetLoader::script(
                $this->processor->app,
                Uri::root(true) . '/components/com_breezingformsng/libraries/js/joomla.javascript.js'
            );
        }
        if ($tooltips > 0) {
            HTMLHelper::_('bootstrap.tooltip', '.hasTooltip');
        } // if

        if (!$this->processor->inline) {
            $current_url = Uri::getInstance()->toString();

            $url = ($this->processor->inframe) ? $ff_mossite . '/index.php?format=html&tmpl=component' : $current_url;
            echo $this->quickModeFormTagBuilder()->build(
                $url,
                (string) $this->processor->form_id,
                $this->processor->formrow->class2 != ''
                    ? $this->processor->getClassName($this->processor->formrow->class2)
                    : '',
                nl()
            );
        } // if

        $js = '';
        $cbJs = '';

        if ($this->processor->editable && $cbRecord === null) {
            $identity = $this->processor->app->getIdentity()
                ?? throw new \RuntimeException('The current user is not loaded.');
            $userId = $identity->get('id', -1);
            $editableRecord = $this->editableRecordLoader()->load((int) $this->processor->form, (int) $userId);
            if ($editableRecord !== null) {
                $this->processor->record_id = $editableRecord->id;
                $recordEntries = $editableRecord->entries;

                $js = $this->editableRecordHydrationScriptBuilder()->build($recordEntries, (int) $this->processor->form);

                echo $this->legacyScriptTagWrapperBuilder()->editableRecord(
                    (int) $this->processor->form,
                    $js,
                    nl()
                );
            }
        }

        // CONTENTBUILDER BEGIN

        if ($cbRecord !== null) {
            $cbNonEditableFields = $this->contentBuilderNonEditableFieldsResolver()->resolve(
                (int) $cbResult['data']['id']
            );
            $scripts = $this->contentBuilderHydrationScriptBuilder()->buildEditable(
                $cbRecord,
                $cbNonEditableFields,
                trim((string) $this->processor->formrow->template_code_processed) === 'QuickMode',
                (int) $this->processor->form,
                JPATH_SITE . '/media/breezingforms/signatures/'
            );
            echo $this->legacyScriptTagWrapperBuilder()->contentBuilderEditable(
                (int) $this->processor->form,
                $scripts['contentBuilderScript'],
                $scripts['javascript']
            );
        }

            $cbNonEditableFields = array();
        if ($cbForm !== null) {
            $cbNonEditableFields = $this->contentBuilderNonEditableFieldsResolver()->resolve(
                (int) $cbResult['data']['id']
            );
            if (count($cbNonEditableFields)) {
                $document = $this->processor->app->getDocument()
                    ?? throw new \RuntimeException('The document is not available.');
                $document->getWebAssetManager()->addInlineScript('<!--' . nl() . 'var bfDeactivateField = new Array();' . nl() . '//-->');
                echo $this->legacyScriptTagWrapperBuilder()->contentBuilderReadonly(
                    $this->contentBuilderHydrationScriptBuilder()->buildReadonly($cbNonEditableFields),
                    nl()
                );
            }
        }

        // CONTENTBUILDER END

        if (trim((string) $this->processor->formrow->template_code_processed) == '') {
            $classicMarkup = $this->classicFormRenderer()->render($ff_mossite);
            if ($classicMarkup === null) {
                $this->abortViewRendering();
                return;
            }

            echo $classicMarkup;
        } else if (trim((string) $this->processor->formrow->template_code_processed) == 'QuickMode') {

            $quickMode = $this->quickModeRendererFactory()->create($this->processor, $rootMdata);
            $this->processor->quickmode = $quickMode;

            $quickMode->render();
        }

        if ($this->processor->editable) {
            echo $this->postRenderScriptBuilder()->build('bfLoadEditable');
        }

        if ($cbRecord !== null) {
            echo $this->postRenderScriptBuilder()->build('bfLoadContentBuilderEditable');
        }

        if ($cbForm !== null && count($cbNonEditableFields)) {
            echo $this->postRenderScriptBuilder()->build('bfDisableContentBuilderFields');
        }

        // CONTENTBUILDER
        // writing hidden input for groups. helps on recording updates, otherwise no value would be transferred.
        // the "cbGroupMark" won't be stored.
        if ($cbForm !== null) {
            $elementRows = $this->processor->rows ?? [];
            for ($i = 0; $i < $this->processor->rowcount; $i++) {
                $row = $elementRows[$i];
                switch ($row->type) {
                    case 'Checkbox':
                    case 'Checkbox Group':
                    case 'Radio Button':
                    case 'Radio Group':
                    case 'Select List':
                        // temporary removed until further clarification if needed or not as this will interfere with javasripts on group elements (loosing their type)
                        //echo '<input type="hidden" name="ff_nm_' . $row->name . '[]" value="cbGroupMark"/>' . nl();
                        break;
                }
            }
        }

        if ($this->paymentProviderDetector()->hasSupportedProvider($this->processor->rows ?? [])) {
            echo $this->hiddenFormFieldsBuilder()->paymentMethod(indentc(1));
        }

        $input = $this->processor->app->getInput();
        $context = $this->buildFormContext();
        $routing = $this->hiddenFormFieldsBuilder()->routing($input->getString('return', ''), $input->getString('tmpl', ''), nl());
        $technical = $this->buildContentBuilderTechnicalFields();
        echo $this->formEnvelopeMarkupBuilder()->frontend(
            $this->hiddenFormFieldsBuilder()->context($context, indentc(1)),
            $this->hiddenFormFieldsBuilder()->submission((int) $this->processor->form, indentc(1), nl()),
            $this->hiddenFormFieldsBuilder()->token(\Joomla\CMS\HTML\HTMLHelper::_('form.token'), indentc(1), nl()),
            $this->hiddenFormFieldsBuilder()->optionalContext((int) $this->processor->target, (bool) $this->processor->inframe, (bool) $this->processor->border, (int) $this->processor->page, $this->processor->align ?? 1, $this->processor->top ?? 0, indentc(1), true, true, true, true, nl()),
            $this->hiddenFormFieldsBuilder()->additional($ff_otherparams, indentc(1), nl()),
            $technical,
            $routing,
            nl()
        );
        if ($this->executeAfterFormPiece()) {
            $this->abortViewRendering();
            return;
        }

        $this->closeFormRendering();
        $this->finishViewRendering();

    }

    /**
     * Decode the QuickMode template metadata used by the rendering stages.
     *
     * @return array<string, mixed>
     */
    private function loadQuickModeMetadata(): array
    {
        $dataObject = json_decode(bf_b64dec($this->processor->formrow->template_code), true);

        return $dataObject['properties'];
    }

    /**
     * Build the script library and the registry used while linking callbacks.
     *
     * @return array{0: array<int, array<int, mixed>>, 1: array<int, string>}
     */
    private function createScriptLibraryState(): array
    {
        $library = [];
        $this->processor->loadBuiltins($library);
        $this->processor->loadScripts($library);

        return [$library, []];
    }

    /**
     * Link the onload callback used when a form is displayed for the first time.
     *
     * @param array<int, array<int, mixed>> $library
     * @param array<int, string> $linked
     */
    private function linkInitialOnload(array &$library, array &$linked): void
    {
        $code = $this->formOnloadScriptBuilder()->initial(
            $this->processor->formrow->heightmode ?? 0,
            $this->processor->formrow->height ?? 0,
            (bool) $this->processor->showgrid,
            nl()
        );
        $this->processor->linkcode('onload', $library, $linked, $code);
    }

    private function initializeFormRendering(): void
    {
        $this->processor->queryCols = [];
        $this->processor->queryRows = [];

        echo $this->formEnvelopeMarkupBuilder()->opening(
            (string) $this->processor->form,
            $this->processor->formrow->class1 != ''
                ? $this->processor->getClassName($this->processor->formrow->class1)
                : ''
        );

        $this->processor->status = $this->processor->app->getInput()->getCmd('ff_status', '');
        $this->processor->message = $this->processor->app->getInput()->getString('ff_message', '');
    }

    private function closeFormRendering(): void
    {
        echo $this->formEnvelopeMarkupBuilder()->closing(nl());
    }

    private function abortViewRendering(): void
    {
        ob_end_flush();
        restore_error_handler();
    }

    private function finishViewRendering(): void
    {
        if ($this->processor->traceMode & _FF_TRACEMODE_DIRECT) {
            $this->processor->dumpTrace();
            ob_end_flush();
            echo '</pre>';
        } else {
            ob_end_flush();
            $this->processor->dumpTrace();
        }

        restore_error_handler();
    }

    private function executeBeforeFormPiece(): bool
    {
        return $this->formPieceExecutionService()->executeBefore(
            $this->processor->formrow,
            Text::_('COM_BREEZINGFORMSNG_PROCESS_BFPIECE'),
            Text::_('COM_BREEZINGFORMSNG_PROCESS_BFPIECEC'),
            (int) $this->processor->form
        );
    }

    private function executeAfterFormPiece(): bool
    {
        return $this->formPieceExecutionService()->executeAfter(
            $this->processor->formrow,
            Text::_('COM_BREEZINGFORMSNG_PROCESS_AFPIECE'),
            Text::_('COM_BREEZINGFORMSNG_PROCESS_AFPIECEC'),
            (int) $this->processor->form
        );
    }

    /**
     * Link the onload callback used after a form submission.
     *
     * @param array<int, array<int, mixed>> $library
     * @param array<int, string> $linked
     */
    private function linkSubmittedOnload(array &$library, array &$linked): void
    {
        $functionName = $this->submittedCallbackNameResolver()->resolve($this->processor->formrow);

        $code = $this->formOnloadScriptBuilder()->submitted(
            (string) $functionName,
            $this->processor->formrow->heightmode ?? 0,
            $this->processor->formrow->height ?? 0,
            (bool) $this->processor->showgrid,
            $this->processor->status ?? '',
            (string) $this->processor->message,
            nl()
        );
        if ($code === null) {
            return;
        }
        $this->processor->linkcode('onload', $library, $linked, $code);
    }

    /**
     * Prepare the client-side state for one classic Query List element.
     *
     * @param \stdClass $row
     */
    private function prepareQueryListRow(object $row, int &$queryCheckboxCount, string &$queryCode): void
    {
        if ($row->flag2) {
            $queryCheckboxCount++;
        }

        $prepared = $this->queryListRowPreparationService()->prepare($row, nl());
        $this->processor->queryCols[$prepared['key']] = $prepared['columns'];
        $this->processor->queryRows[$prepared['key']] = $prepared['rows'];
        $queryCode .= $prepared['script'];
    }

    /**
     * Link the icon border hover callbacks used when the form has at least
     * one "Icon" row. Returns whether view() should bury/return - mirrors
     * the original inline control flow exactly: the second linkcode() call
     * never runs if bury() is already true after the first.
     *
     * @param array<int, array<int, mixed>> $library
     * @param array<int, string> $linked
     */
    private function registerIconBorderScripts(array &$library, array &$linked): bool
    {
        return $this->callbackRegistrationService()->registerIconBorders($library, $linked, nl());
    }

    /**
     * Register the three callbacks associated with one classic element.
     *
     * The callback order and early-return behavior are part of the historical
     * rendering contract. The validation callback also closes the active
     * output buffer when it triggers bury().
     *
     * @param \stdClass $row Classic element row.
     * @param array<int, array<int, mixed>> $library
     * @param array<int, string> $linked
     */
    private function registerElementCallbacks(object $row, array &$library, array &$linked): bool
    {
        return $this->callbackRegistrationService()->registerElement($row, $library, $linked);
    }

    /**
     * Count the element types that require post-render assets or behavior.
     *
     * @param \stdClass $row
     */
    private function collectElementMetadata(object $row, int &$icons, int &$tooltips): void
    {
        if ($row->type == "Icon") {
            $icons++;
        }

        if ($row->type == "Tooltip") {
            $tooltips++;
        }
    }

    /**
     * Register the scan-only callback used by static HTML elements.
     *
     * @param \stdClass $row
     * @param array<int, array<int, mixed>> $library
     * @param array<int, string> $linked
     */
    private function registerStaticTextScanCallback(object $row, array &$library, array &$linked): void
    {
        $this->callbackRegistrationService()->registerStaticTextScan($row, $library, $linked);
    }

    /**
     * Link the form-level init and submission callbacks.
     *
     * @param array<int, array<int, mixed>> $library
     * @param array<int, string> $linked
     */
    private function addFormScripts(array &$library, array &$linked): bool
    {
        return $this->callbackRegistrationService()->registerForm(
            $this->processor->formrow,
            $library,
            $linked,
            (int) $this->processor->form
        );
    }

    // view

}
