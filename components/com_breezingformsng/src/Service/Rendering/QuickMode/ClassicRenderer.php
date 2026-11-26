<?php

/**
 * BreezingForms NG - A Joomla Forms Application
 *
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2008-2020 by Markus Bopp
 * @copyright Copyright (C) 2024-2026 by XDA+GIL - EVH
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 *
 * Source history: libraries/crosstec/classes/BFQuickMode.php (Phase 9c) -
 * classic-theme QuickMode frontend renderer; BFQuickMode remains as a facade.
 * */

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Site\Service\Rendering\QuickMode;

\defined('_JEXEC') or die;

use HTML_facileFormsProcessor;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Uri\Uri;
use Vcmb\Component\BreezingformsNG\Site\Service\Runtime\RuntimeAssetLoader;
use Vcmb\Component\BreezingformsNG\Site\Service\Widget\WidgetAssets;
use Vcmb\Component\BreezingformsNG\Site\Service\Widget\WidgetFieldBuilder;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Editor\Editor;


/**
 * @phpstan-type QuickModeTreeNode array{
 *     properties?: array<string, mixed>,
 *     attributes?: array{id: string},
 *     children?: list<array<string, mixed>>
     * }
 * @phpstan-type QuickModeProcessNode array{
 *     properties: array<string, mixed>,
 *     attributes?: array{id: string},
 *     children?: list<array<string, mixed>>
 * }
     *
     * @phpstan-type ClassicFieldData array{
     *     type: string,
     *     bfType: string,
     *     bfName: string,
     *     name: string,
     *     dbId: int,
     *     pageNumber: int,
     *     tabIndex: int,
     *     pageIntro: string,
     *     label: string,
     *     hint: string,
     *     labelPosition: string,
     *     title: string,
     *     description: string,
     *     actionClick: bool,
     *     actionBlur: bool,
     *     actionChange: bool,
     *     actionFocus: bool,
     *     actionSelect: bool,
     *     actionFunctionName: string,
     *     connectWith: string|array<int, string>,
     *     connectType: string,
     *     emptyMessage: string,
     *     fieldCalc: string,
     *     format: string,
     *     firstDay: string,
     *     minYear: string,
     *     maxYear: string,
     *     width: string,
     *     height: string,
     *     size: string,
     *     value: string,
     *     placeholder: string,
     *     group: string,
     *     list: string,
     *     src: string,
     *     image: string,
     *     mailbackConnectWith: string,
     *     theme: string,
     *     rolloverColor: string,
     *     toggleFields: string,
     *     pagingPrevLabel: string,
     *     pagingNextLabel: string,
     *     submitLabel: string,
     *     cancelLabel: string,
     *     hideIfEmpty: bool,
     *     hideLabel: bool,
     *     off: bool,
     *     required: bool,
     *     readonly: bool,
     *     multiple: bool,
     *     wrap: bool,
     *     mailbackAccept: bool,
     *     mailbackAsSender: bool,
     *     is_html: bool,
     *     flashUploader: bool,
     *     html5: bool,
     *     invisibleCaptcha: bool,
     *     showMaxlengthCounter: bool,
     *     attachToAdminMail: bool,
     *     attachToUserMail: bool,
     *     fadeIn: bool,
     *     useErrorAlerts: bool,
     *     useDefaultErrors: bool,
     *     useBalloonErrors: bool,
     *     rollover: bool,
     *     lastPageThankYou: bool,
     *     pagingInclude: bool,
     *     submitInclude: bool,
     *     cancelInclude: bool,
     *     joomlaHint: bool
     * }
     */
class ClassicRenderer
{
    use HiddenFieldTrait;
    use CalendarOptionsTrait;

    /**
     * @var HTML_facileFormsProcessor
     */
    private $p = null;
    /** @var QuickModeTreeNode */
    private $dataObject;
    /** @var ClassicFieldData */
    private $rootMdata;
    /** @var mixed */
    private $fading = true;
    /** @var string */
    private $fadingClass = '';
    /** @var mixed */
    private $useErrorAlerts = false;
    /** @var mixed */
    private $useDefaultErrors = false;
    /** @var mixed */
    private $useBalloonErrors = false;
    /** @var mixed */
    private $rollover = false;
    /** @var string */
    private $rolloverColor = '';
    /** @var string */
    private $toggleFields = '';
    /** @var bool */
    private $hasFlashUpload = false;
    /** @var string */
    private $flashUploadTicket = '';
    /** @var string */
    private $cancelImagePath = '';
    /** @var string */
    private $uploadImagePath = '';
    /** @var list<string> */
    private $htmltextareas = array();
    /** @var list<string> */
    private $htmltextareasDbIds = array();
    /** @var string */
    private $language_tag = '';
    /** @var bool */
    private $hasResponsiveDatePicker = false;
    private ?QuickModeTextFieldStrategy $quickModeTextFieldStrategyService = null;
    private ?QuickModeTextareaStrategy $quickModeTextareaStrategyService = null;
    private ?QuickModeCheckboxStrategy $quickModeCheckboxStrategyService = null;
    private ?QuickModeSelectBuilder $quickModeSelectBuilderService = null;
    private ?QuickModeMaxLengthCounterBuilder $quickModeMaxLengthCounterBuilderService = null;
    private ?ClassicChoiceGroupBuilder $classicChoiceGroupBuilderService = null;
    private ?QuickModeSubmitButtonBuilder $quickModeSubmitButtonBuilderService = null;
    private ?QuickModeCalendarButtonBuilder $quickModeCalendarButtonBuilderService = null;
    private ?QuickModeCalendarInputBuilder $quickModeCalendarInputBuilderService = null;
    private ?QuickModeCalendarInitScriptBuilder $quickModeCalendarInitScriptBuilderService = null;
    private ?QuickModeCaptchaUrlBuilder $quickModeCaptchaUrlBuilderService = null;
    private ?QuickModeCaptchaMarkupBuilder $quickModeCaptchaMarkupBuilderService = null;
    private ?QuickModeRequiredMarkupBuilder $quickModeRequiredMarkupBuilderService = null;
    private ?QuickModeCaptchaReloadScriptBuilder $quickModeCaptchaReloadScriptBuilderService = null;
    private ?QuickModeUploadOptionsBuilder $quickModeUploadOptionsBuilderService = null;
    private ?QuickModePagingActionBuilder $quickModePagingActionBuilderService = null;
    private ?QuickModeSubmitActionBuilder $quickModeSubmitActionBuilderService = null;
    private ?QuickModeHtmlTextareaScriptBuilder $quickModeHtmlTextareaScriptBuilderService = null;
    private ?QuickModeReCaptchaFieldBuilder $quickModeReCaptchaFieldBuilderService = null;
    private ?QuickModeDeactivationScriptBuilder $quickModeDeactivationScriptBuilderService = null;
    private ?QuickModeHintContentResolver $quickModeHintContentResolverService = null;

    private function quickModeDeactivationScriptBuilder(): QuickModeDeactivationScriptBuilder
    {
        return $this->quickModeDeactivationScriptBuilderService ??= new QuickModeDeactivationScriptBuilder();
    }

    private function quickModeHtmlTextareaScriptBuilder(): QuickModeHtmlTextareaScriptBuilder
    {
        return $this->quickModeHtmlTextareaScriptBuilderService ??= new QuickModeHtmlTextareaScriptBuilder();
    }

    private function quickModeReCaptchaFieldBuilder(): QuickModeReCaptchaFieldBuilder
    {
        return $this->quickModeReCaptchaFieldBuilderService ??= new QuickModeReCaptchaFieldBuilder();
    }

    private function quickModeHintContentResolver(): QuickModeHintContentResolver
    {
        return $this->quickModeHintContentResolverService ??= new QuickModeHintContentResolver();
    }

    private function quickModeTextFieldStrategy(): QuickModeTextFieldStrategy
    {
        return $this->quickModeTextFieldStrategyService ??= new QuickModeTextFieldStrategy();
    }

    private function quickModeTextareaStrategy(): QuickModeTextareaStrategy
    {
        return $this->quickModeTextareaStrategyService ??= new QuickModeTextareaStrategy();
    }

    private function quickModeCheckboxStrategy(): QuickModeCheckboxStrategy
    {
        return $this->quickModeCheckboxStrategyService ??= new QuickModeCheckboxStrategy();
    }

    public static function getEditorContent(string $editor): string
    {
        return QuickModeEditorValueBuilder::build($editor);
    }

    /**
     * @return void
     */
    public function headers()
    {

        $document = $this->p->app->getDocument() ?? throw new \RuntimeException('The document is not available.');
        $document->getWebAssetManager()->addInlineScript('
	    
	        jQuery(document).ready(function(){
	            jQuery(".ff_elem").closest(".input-group").removeClass("input-group");
	            jQuery(".ff_elem").next(".input-group-append").removeClass("input-group-append");
	            jQuery(".ff_elem").removeClass("form-control");
	            jQuery(".js-calendar").closest(".bfElemWrap").css("overflow","visible");
	            jQuery(".js-calendar").each(function(){
	                let elem_id = jQuery(this).closest(".bfElemWrap").find(".ff_elem").attr("id");
	                let _this = this;
	                jQuery("#"+elem_id+"_btn").on("click", function(){
	                    jQuery(_this).closest(".bfElemWrap").removeClass("bfRolloverBg");
	                    jQuery(_this).css("left", jQuery("#"+elem_id).position().left);
	                });
	            });
	        });
	    ');

        if ($this->hasFlashUpload) {
            RuntimeAssetLoader::script($this->p->app, Uri::root(true) . '/components/com_breezingformsng/libraries/jquery/plupload/moxie.js');
            RuntimeAssetLoader::script($this->p->app, Uri::root(true) . '/components/com_breezingformsng/libraries/jquery/plupload/plupload.js');
            RuntimeAssetLoader::script($this->p->app, Uri::root(true) . '/media/com_breezingformsng/js/site/quickmode-plupload-compat.js');
        }
        $document->getWebAssetManager()->useScript('jquery');
        RuntimeAssetLoader::style($this->p->app, Uri::root(true) . '/media/com_breezingformsng/css/site/quickmode-runtime.css');
        HTMLHelper::_('bootstrap.tooltip', '.hasTooltip');
        $document->getWebAssetManager()->addInlineStyle('
.bfInline{
float:left;
}
');
        $jQuery = "\n" . 'var JQuery = jQuery;' . "\n";
        if ($this->useErrorAlerts) {
            RuntimeAssetLoader::script($this->p->app, Uri::root(true) . '/components/com_breezingformsng/libraries/js/sweetalert2.min.js');
        }
        $toggleCode = '';
        if ($this->toggleFields != '[]') {
            RuntimeAssetLoader::script($this->p->app, Uri::root(true) . '/media/com_breezingformsng/js/site/quickmode-toggle-fields.js');
            $toggleCode = '
			var toggleFieldsArray = ' . $this->toggleFields . ';
		';
        }

        RuntimeAssetLoader::script($this->p->app, Uri::root(true) . '/media/com_breezingformsng/js/site/quickmode-core-helpers.js');
        RuntimeAssetLoader::script($this->p->app, Uri::root(true) . '/media/com_breezingformsng/js/site/quickmode-deactivation.js');

        $document->getWebAssetManager()->addInlineScript(
            $jQuery . '
			var inlineErrorElements = new Array();
			var bfSummarizers = new Array();
			var bfDeactivateField = new Array();
			var bfDeactivateSection = new Array();
			var bfCharsLeftLabel = ' . json_encode(Text::_('COM_BREEZINGFORMSNG_CHARS_LEFT')) . ';
			' . $toggleCode . '

'
        );

        if ($this->fading || !$this->useErrorAlerts || $this->rollover) {
            if (!$this->useErrorAlerts) {
                $showDefaultErrors = $this->useDefaultErrors || !$this->useBalloonErrors;
                $document->getWebAssetManager()->addInlineScript(
                    'var bfUseErrorAlerts = false;' . "\n"
                    . 'var bfShowDefaultErrors = ' . ($showDefaultErrors ? 'true' : 'false') . ';' . "\n"
                );
                RuntimeAssetLoader::script($this->p->app, Uri::root(true) . '/media/com_breezingformsng/js/site/quickmode-error-alerts.js');
            }
            if ($this->fading) {
                $this->fadingClass = ' bfFadingClass';
                RuntimeAssetLoader::script($this->p->app, Uri::root(true) . '/media/com_breezingformsng/js/site/quickmode-fade.js');
            }

            if ($this->rollover && trim($this->rolloverColor) != '') {
                $document->getWebAssetManager()->addInlineScript(
                    'var bfRolloverColor = ' . json_encode($this->rolloverColor) . ';'
                );
                RuntimeAssetLoader::script($this->p->app, Uri::root(true) . '/media/com_breezingformsng/js/site/quickmode-rollover.js');
            }
        }
        RuntimeAssetLoader::script($this->p->app, Uri::root(true) . '/media/com_breezingformsng/js/site/quickmode-post-init.js');
        // loading system css
        RuntimeAssetLoader::style($this->p->app, Uri::root(true) . '/components/com_breezingformsng/themes/quickmode/system.css');

        // loading theme
        if ($this->rootMdata['theme'] != 'none' && is_file(JPATH_SITE . '/media/breezingforms/themes/' . $this->rootMdata['theme'] . '/theme.css')) {
            RuntimeAssetLoader::style($this->p->app, Uri::root(true) . '/media/breezingforms/themes/' . $this->rootMdata['theme'] . '/theme.css');
        }
    }

    private function quickModeSelectBuilder(): QuickModeSelectBuilder
    {
        return $this->quickModeSelectBuilderService ??= new QuickModeSelectBuilder();
    }

    private function quickModeMaxLengthCounterBuilder(): QuickModeMaxLengthCounterBuilder
    {
        return $this->quickModeMaxLengthCounterBuilderService ??= new QuickModeMaxLengthCounterBuilder();
    }

    private function classicChoiceGroupBuilder(): ClassicChoiceGroupBuilder
    {
        return $this->classicChoiceGroupBuilderService ??= new ClassicChoiceGroupBuilder(
            new QuickModeGroupOptionBuilder()
        );
    }

    private function quickModeSubmitButtonBuilder(): QuickModeSubmitButtonBuilder
    {
        return $this->quickModeSubmitButtonBuilderService ??= new QuickModeSubmitButtonBuilder();
    }

    private function quickModeCalendarButtonBuilder(): QuickModeCalendarButtonBuilder
    {
        return $this->quickModeCalendarButtonBuilderService ??= new QuickModeCalendarButtonBuilder();
    }

    private function quickModeCalendarInputBuilder(): QuickModeCalendarInputBuilder
    {
        return $this->quickModeCalendarInputBuilderService ??= new QuickModeCalendarInputBuilder();
    }

    private function quickModeCalendarInitScriptBuilder(): QuickModeCalendarInitScriptBuilder
    {
        return $this->quickModeCalendarInitScriptBuilderService ??= new QuickModeCalendarInitScriptBuilder();
    }

    private function quickModeCaptchaUrlBuilder(): QuickModeCaptchaUrlBuilder
    {
        return $this->quickModeCaptchaUrlBuilderService ??= new QuickModeCaptchaUrlBuilder();
    }

    private function quickModeRequiredMarkupBuilder(): QuickModeRequiredMarkupBuilder
    {
        return $this->quickModeRequiredMarkupBuilderService ??= new QuickModeRequiredMarkupBuilder();
    }

    private function quickModeCaptchaMarkupBuilder(): QuickModeCaptchaMarkupBuilder
    {
        return $this->quickModeCaptchaMarkupBuilderService ??= new QuickModeCaptchaMarkupBuilder();
    }

    private function quickModeCaptchaReloadScriptBuilder(): QuickModeCaptchaReloadScriptBuilder
    {
        return $this->quickModeCaptchaReloadScriptBuilderService ??= new QuickModeCaptchaReloadScriptBuilder();
    }

    private function quickModeUploadOptionsBuilder(): QuickModeUploadOptionsBuilder
    {
        return $this->quickModeUploadOptionsBuilderService ??= new QuickModeUploadOptionsBuilder();
    }

    private function quickModePagingActionBuilder(): QuickModePagingActionBuilder
    {
        return $this->quickModePagingActionBuilderService ??= new QuickModePagingActionBuilder();
    }

    private function quickModeSubmitActionBuilder(): QuickModeSubmitActionBuilder
    {
        return $this->quickModeSubmitActionBuilderService ??= new QuickModeSubmitActionBuilder();
    }

    public function __construct(HTML_facileFormsProcessor $p, ?string $theme = null)
    {
        $this->p = $p;

        $default = ComponentHelper::getParams('com_languages')->get('site');
        $language = $this->p->app->getLanguage() ?? throw new \RuntimeException('The language is not loaded.');
        $this->language_tag = $language->getTag() != $default ? $language->getTag() : 'zz-ZZ';

        $document = $this->p->app->getDocument() ?? throw new \RuntimeException('The document is not available.');
        $document->getWebAssetManager()->addInlineScript('<!--');

        $dataObject = json_decode(bf_b64dec($this->p->formrow->template_code), true);
        $this->assertQuickModeProcessNode($dataObject);
        $this->dataObject = $dataObject;
        $this->rootMdata = $this->normalizeClassicFieldData($this->quickModeNodeProperties($dataObject));
        if (array_key_exists('theme', $this->rootMdata)) {
            $this->rootMdata['theme'] = QuickModeThemeName::resolve(
                JPATH_SITE . '/media/breezingforms/themes',
                $this->rootMdata['theme'],
                [],
                'none'
            );
        }
        if ($theme !== null) {
            $this->rootMdata['theme'] = $theme;
        }

        if ($this->p->app->getInput()->getString('ff_applic', '') != 'mod_facileforms' && $this->p->app->getInput()->getString('ff_applic', '') != 'plg_facileforms') {
            /* translatables */
            $translatedTitle = $this->classicTranslation(
                $this->rootMdata,
                'title_translation' . $this->language_tag
            );
            if ($translatedTitle !== null) {
                $this->rootMdata['title'] = $translatedTitle;
                $document->setTitle($this->rootMdata['title']);
            }
            /* translatables end */
        }

        $this->fading = $this->rootMdata['fadeIn'];
        $this->useErrorAlerts = $this->rootMdata['useErrorAlerts'];
        $this->useDefaultErrors = isset($this->rootMdata['useDefaultErrors']) ? $this->rootMdata['useDefaultErrors'] : false;
        $this->useBalloonErrors = isset($this->rootMdata['useBalloonErrors']) ? $this->rootMdata['useBalloonErrors'] : false;
        $this->rollover = $this->rootMdata['rollover'];
        $this->rolloverColor = $this->rootMdata['rolloverColor'];
        $this->toggleFields = $this->parseToggleFields(isset($this->rootMdata['toggleFields']) ? $this->rootMdata['toggleFields'] : '[]');

        $this->flashUploadTicket = bin2hex(random_bytes(16));
        $this->cancelImagePath = Uri::root(true) . '/media/breezingforms/themes/cancel.png';
        $this->uploadImagePath = Uri::root(true) . '/media/breezingforms/themes/upload.png';
        if (is_file(JPATH_SITE . '/media/breezingforms/themes/' . $this->rootMdata['theme'] . '/img/cancel.png')) {
            $this->cancelImagePath = Uri::root(true) . '/media/breezingforms/themes/' . $this->rootMdata['theme'] . '/img/cancel.png';
        }
        if (is_file(JPATH_SITE . '/media/breezingforms/themes/' . $this->rootMdata['theme'] . '/img/upload.png')) {
            $this->uploadImagePath = Uri::root(true) . '/media/breezingforms/themes/' . $this->rootMdata['theme'] . '/img/upload.png';
        }
    }

    /**
     * @param array<string, mixed> $dataObject
     * @param array<string, mixed>|null $parent
     * @param array<string, mixed>|null $parentPage
     * @param int $index
     * @param int $childrenLength
     * @return void
     */
    public function process(&$dataObject, $parent = null, $parentPage = null, $index = 0, $childrenLength = 0)
    {
        $this->assertQuickModeProcessNode($dataObject);
        $mdata = $this->normalizeClassicFieldData($this->quickModeNodeProperties($dataObject));

        if (isset($dataObject['properties']['type'])) {
            $options = array('type' => 'normal', 'displayType' => 'breaks');
            if ($parent != null && $parent['type'] == 'section') {
                $options['type'] = $parent['bfType'];
                $options['displayType'] = $parent['displayType'];
            }

            $class = ' class="bfBlock' . $this->fadingClass . '"';
            $wrapper = 'bfWrapperBlock';
            if ($options['displayType'] == 'inline') {
                $class = ' class="bfInline' . $this->fadingClass . '"';
                $wrapper = 'bfWrapperInline';
            }

            // Sofort was discontinued by Klarna and removed: an element still
            // stored in a form renders nothing until the form is saved again.
            if (($mdata['bfType'] ?? '') === 'bfSofortueberweisung') {
                return;
            }

            if ($mdata['type'] == 'page') {
                $parentPage = $mdata;
                if ($parentPage['pageNumber'] > 1) {
                    echo '</div><!-- bfPage end -->' . "\n"; // closing previous pages
                }

                $display = ' style="display:none;"';
                if ($this->p->app->getInput()->getInt('ff_form_submitted', 0) == 0 && (int) $this->p->page == $parentPage['pageNumber']) {
                    $display = '';
                } elseif ($this->p->app->getInput()->getInt('ff_form_submitted', 0) == 1 && $this->rootMdata['lastPageThankYou'] && $parentPage['pageNumber'] == count($this->quickModeRootChildren())) {
                    $display = '';
                } elseif ($this->p->app->getInput()->getInt('ff_form_submitted', 0) == 1 && false == $this->rootMdata['lastPageThankYou'] && $parentPage['pageNumber'] == 1) {
                    $display = '';
                }

                echo '<div id="bfPage' . $parentPage['pageNumber'] . '" class="bfPage"' . $display . '>' . "\n"; // opening current page

                /* translatables */
                $translatedPageIntro = $this->classicTranslation(
                    $mdata,
                    'pageIntro_translation' . $this->language_tag
                );
                if ($translatedPageIntro !== null) {
                    $mdata['pageIntro'] = $translatedPageIntro;
                }
                /* translatables end */

                if (trim($mdata['pageIntro']) != '') {
                    echo '<section class="bfPageIntro' . $this->fadingClass . '">' . "\n";

                    $regex = '/{loadposition\s+(.*?)}/i';
                    $introtext = $mdata['pageIntro'];

                    preg_match_all($regex, $introtext, $matches, PREG_SET_ORDER);

                        $document = $this->p->app->getDocument() ?? throw new \RuntimeException('The document is not available.');
                        $renderer = $document->loadRenderer('modules');
                        $options = array('style' => 'xhtml');

                    foreach ($matches as $match) {
                        $matcheslist = explode(',', $match[1]);
                        $position = trim($matcheslist[0]);
                        $output = $renderer->render($position, $options, null);
                        $updatedIntrotext = preg_replace("|$match[0]|", addcslashes($output, '\\'), $introtext, 1);
                        if ($updatedIntrotext !== null) {
                            $introtext = $updatedIntrotext;
                        }
                    }

                    echo $introtext . "\n";

                    echo '</section>' . "\n";
                }

                if (!$this->useErrorAlerts) {
                    echo '<span class="bfErrorMessage" style="display:none"></span>' . "\n";
                }
            } elseif ($mdata['type'] == 'section') {
                if (isset($dataObject['properties']['name']) && isset($mdata['off']) && $mdata['off']) {
                    echo $this->quickModeDeactivationScriptBuilder()->section($mdata['name']);
                }

                /* translatables */
                $translatedTitle = $this->classicTranslation(
                    $mdata,
                    'title_translation' . $this->language_tag
                );
                if ($translatedTitle !== null) {
                    $mdata['title'] = $translatedTitle;
                }
                /* translatables end */

                $escapedName = htmlspecialchars(
                    (string) ($mdata['name'] ?? ''),
                    ENT_QUOTES | ENT_SUBSTITUTE,
                    'UTF-8'
                );
                if ($mdata['bfType'] == 'section') {
                    echo '<div class="bfFieldset-wrapper ' . $wrapper . ' bfClearfix"><div class="bfFieldset-tl"><div class="bfFieldset-tr"><div class="bfFieldset-t"></div></div></div><div class="bfFieldset-l"><div class="bfFieldset-r"><div class="bfFieldset-m bfClearfix"><fieldset' . (isset($mdata['off']) && $mdata['off'] ? ' style="display:none" ' : '') . '' . (isset($mdata['off']) && $mdata['off'] ? '' : $class) . '' . ($escapedName !== '' ? ' id="' . $escapedName . '"' : '') . '>' . "\n";
                    if (trim($mdata['title']) != '') {
                        echo '<legend><span class="bfLegend-l"><span class="bfLegend-r"><span class="bfLegend-m">' . htmlentities(trim($mdata['title']), ENT_QUOTES, 'UTF-8') . '</span></span></span></legend>' . "\n";
                    }
                } elseif ($mdata['bfType'] == 'normal') {
                    if (isset($dataObject['properties']['name']) && $dataObject['properties']['name'] != '') {
                        echo '<div ' . (isset($mdata['off']) && $mdata['off'] ? 'style="display:none" ' : '') . 'class="bfNoSection"' . ($escapedName !== '' ? ' id="' . $escapedName . '"' : '') . '>' . "\n";
                    }
                }

                /* translatables */
                $translatedDescription = $this->classicTranslation(
                    $mdata,
                    'description_translation' . $this->language_tag
                );
                if ($translatedDescription !== null) {
                    $mdata['description'] = $translatedDescription;
                }
                /* translatables end */

                if (trim($mdata['description']) != '') {
                    echo '<section class="bfSectionDescription">' . "\n";

                    $regex = '/{loadposition\s+(.*?)}/i';
                    $introtext = $mdata['description'];

                    preg_match_all($regex, $introtext, $matches, PREG_SET_ORDER);

                    $document = $this->p->app->getDocument() ?? throw new \RuntimeException('The document is not available.');
                    $renderer = $document->loadRenderer('modules');
                    $options = array('style' => 'xhtml');

                    foreach ($matches as $match) {
                        $matcheslist = explode(',', $match[1]);
                        $position = trim($matcheslist[0]);
                        $output = $renderer->render($position, $options, null);
                        $updatedIntrotext = preg_replace("|$match[0]|", addcslashes($output, '\\'), $introtext, 1);
                        if ($updatedIntrotext !== null) {
                            $introtext = $updatedIntrotext;
                        }
                    }

                    echo $introtext . "\n";
                    echo '</section>' . "\n";
                }
            } elseif ($mdata['type'] == 'element') {
                $onclick = '';
                if (isset($mdata['actionClick']) && $mdata['actionClick'] == 1) {
                    $onclick = 'onclick="' . $mdata['actionFunctionName'] . '(this,\'click\');" ';
                }

                $onblur = '';
                if (isset($mdata['actionBlur']) && $mdata['actionBlur'] == 1) {
                    $onblur = 'onblur="' . $mdata['actionFunctionName'] . '(this,\'blur\');" ';
                }

                $onchange = '';
                if (isset($mdata['actionChange']) && $mdata['actionChange'] == 1) {
                    $onchange = 'onchange="' . $mdata['actionFunctionName'] . '(this,\'change\');" ';
                }

                $onfocus = '';
                if (isset($mdata['actionFocus']) && $mdata['actionFocus'] == 1) {
                    $onfocus = 'onfocus="' . $mdata['actionFunctionName'] . '(this,\'focus\');" ';
                }

                $onselect = '';
                if (isset($mdata['actionSelect']) && $mdata['actionSelect'] == 1) {
                    $onselect = 'onselect="' . $mdata['actionFunctionName'] . '(this,\'select\');" ';
                }

                if ($mdata['bfType'] != 'bfHidden') {
                    $labelPosition = '';
                    switch ($mdata['labelPosition']) {
                        case 'top':
                            $labelPosition = ' bfLabelTop';
                            break;
                        case 'right':
                            $labelPosition = ' bfLabelRight';
                            break;
                        case 'bottom':
                            $labelPosition = ' bfLabelBottom';
                            break;
                        default:
                            $labelPosition = ' bfLabelLeft';
                    }

                    if ($options['displayType'] == 'breaks') {
                        echo '<section ' . (isset($mdata['off']) && $mdata['off'] ? 'style="display:none" ' : '') . 'class="bfElemWrap' . $labelPosition . (isset($mdata['off']) && $mdata['off'] ? '' : $this->fadingClass) . '" id="bfElemWrap' . $mdata['dbId'] . '">' . "\n";
                    } else {
                        echo '<span ' . (isset($mdata['off']) && $mdata['off'] ? 'style="display:none" ' : '') . 'class="bfElemWrap' . $labelPosition . (isset($mdata['off']) && $mdata['off'] ? '' : $this->fadingClass) . '" id="bfElemWrap' . $mdata['dbId'] . '">' . "\n";
                    }
                }

                $translatedHint = $this->classicTranslation(
                    $mdata,
                    'hint_translation' . $this->language_tag
                );
                if ($translatedHint !== null) {
                    $mdata['hint'] = $translatedHint;
                }

                if (!$mdata['hideLabel']) {
                    $badge = '';

                    if (isset($mdata['theme'])) {
                        $badge = str_replace('invisible_', '', trim($mdata['theme']));
                    }

                    if (!( $mdata['bfType'] == 'bfReCaptcha' && isset($mdata['invisibleCaptcha']) && $mdata['invisibleCaptcha'] && $badge != 'inline' )) {
                        $maxlengthCounter = '';
                        if ($mdata['bfType'] == 'bfTextarea' && isset($mdata['maxlength']) && $mdata['maxlength'] > 0 && isset($mdata['showMaxlengthCounter']) && $mdata['showMaxlengthCounter']) {
                            $maxlengthCounter = $this->quickModeMaxLengthCounterBuilder()->build((int) $mdata['dbId'], (int) $mdata['maxlength'], Text::_('COM_BREEZINGFORMSNG_CHARS_LEFT'));
                        }

                        /* translatables */
                        $translatedLabel = $this->classicTranslation(
                            $mdata,
                            'label_translation' . $this->language_tag
                        );
                        if ($translatedLabel !== null) {
                            $mdata['label'] = $translatedLabel;
                        }
                        /* translatables end */

                        $tipScript = '';
                        $tipOpen   = '';
                        $tipClose  = '';
                        $labelText = trim($mdata['label']) . str_replace("***", "\"", $maxlengthCounter);
                        if (trim($mdata['hint']) != '') {
                            if (isset($this->rootMdata['joomlaHint']) && $this->rootMdata['joomlaHint']) {
                                $content   = trim($mdata['hint']);
                                $tipOpen   = '<span title="<strong>' . htmlspecialchars(strip_tags(trim($mdata['label'])), ENT_QUOTES, 'UTF-8') . '</strong><br />' . str_replace(array(
                                        "\n",
                                        "\r"
                                    ), array(
                                        "",
                                        ""
                                    ), htmlentities($content, ENT_QUOTES, 'UTF-8')) . '" id="bfTooltip' . $mdata['dbId'] . '" class="editlinktip hasTooltip"><span class="bfTooltip">&nbsp;';
                                $tipClose  = '</span></span>';
                                $tipScript = '';
                            } else {
                                $tooltipContent = $this->quickModeHintContentResolver()->resolve(
                                    (string) $mdata['hint'],
                                    true
                                );
                                $tipOpen = '<span title="<strong>'
                                    . htmlspecialchars(strip_tags(trim($mdata['label'])), ENT_QUOTES, 'UTF-8')
                                    . '</strong><br />' . str_replace(
                                        array("\n", "\r"),
                                        array('', ''),
                                        htmlentities($tooltipContent, ENT_QUOTES, 'UTF-8')
                                    ) . '" id="bfTooltip' . $mdata['dbId'] . '" class="editlinktip hasTooltip"><span class="bfTooltip">&nbsp;';
                                $tipClose = '</span></span>';
                                $tipScript = '';
                            }
                        }

                        $for = '';
                        if (
                            $mdata['bfType'] == 'bfTextfield' ||
                             $mdata['bfType'] == 'bfTextarea' ||
                             $mdata['bfType'] == 'bfCheckbox' ||
                             $mdata['bfType'] == 'bfCalendar' ||
                             $mdata['bfType'] == 'bfNumberInput' ||
                             $mdata['bfType'] == 'bfCalendarResponsive' ||
                             $mdata['bfType'] == 'bfSelect' ||
                             ( $mdata['bfType'] == 'bfFile' && ( ( ! isset($mdata['flashUploader']) && ! isset($mdata['html5']) ) || ( isset($mdata['flashUploader']) && ! $mdata['flashUploader'] ) && ( isset($mdata['html5']) && ! $mdata['html5'] ) ) )
                        ) {
                            $for = 'for="ff_elem' . $mdata['dbId'] . '"';
                        }

                        if ($mdata['bfType'] == 'bfCaptcha') {
                            $for = 'for="bfCaptchaEntry"';
                        } elseif ($mdata['bfType'] == 'bfReCaptcha') {
                            $for = 'for="recaptcha_response_field"';
                        }
                        $required = '';
                        if ($mdata['required']) {
                            $required = $this->quickModeRequiredMarkupBuilder()->marker('<span class="bfRequired" aria-hidden="true">*</span>');
                        }
                        echo '<label id="bfLabel' . $mdata['dbId'] . '" ' . $for . '>' . $tipOpen . $tipClose . str_replace("***", "\"", $labelText) . $required . '</label>' . $tipScript . "\n";
                    }
                }

                $readonly = '';
                if (isset($mdata['readonly']) && $mdata['readonly']) {
                    $readonly = 'readonly="readonly" ';
                }

                $tabIndex = '';
                if ($mdata['tabIndex'] != -1 && is_numeric($mdata['tabIndex'])) {
                    $tabIndex = 'tabindex="' . intval($mdata['tabIndex']) . '" ';
                }
                $tabIndex .= $this->quickModeRequiredMarkupBuilder()->ariaRequiredAttribute((string) $mdata['bfType'], !empty($mdata['required']));

                $hintLine = '';
                $hintId = '';
                if ($mdata['bfType'] !== 'bfHidden' && trim((string) ($mdata['hint'] ?? '')) !== '') {
                    $hintLine = trim(strip_tags($this->quickModeHintContentResolver()->resolve((string) $mdata['hint'], true)));
                    // These themes show the help line under the field: the field refers to it.
                    $visibleHint = in_array($this->rootMdata['theme'] ?? '', ['vcmb_j5b', 'vcmb_j6'], true);

                    if ($hintLine !== '' && $visibleHint) {
                        $hintId = 'bfHint' . (int) $mdata['dbId'];
                        $tabIndex .= 'aria-describedby="' . $hintId . '" ';
                    }
                }

                $mdata = QuickModeSubmittedValueHydrator::hydrate($this->p, $mdata, $this->language_tag);
                $mdata = $this->normalizeClassicFieldData($mdata);

                if (WidgetFieldBuilder::hasWidget($mdata)) {
                    WidgetAssets::register($this->p->app);
                }

                $flashUploader = '';

                switch ($mdata['bfType']) {
                    case 'bfNumberInput':
                        $this->renderNumberInputField($mdata, $tabIndex, $onclick, $onblur, $onchange, $onfocus, $onselect, $readonly);
                        break;

                    case 'bfTextfield':
                        $this->renderTextfieldField($mdata, $tabIndex, $onclick, $onblur, $onchange, $onfocus, $onselect, $readonly);
                        break;

                    case 'bfTextarea':
                        $this->renderTextareaField($mdata, $tabIndex, $onclick, $onblur, $onchange, $onfocus, $onselect, $readonly);
                        break;

                    case 'bfRadioGroup':
                        $this->renderChoiceGroupField('radio', $mdata, $tabIndex, $onclick, $onblur, $onchange, $onfocus, $onselect, $readonly);
                        break;

                    case 'bfCheckboxGroup':
                        $this->renderChoiceGroupField('checkbox', $mdata, $tabIndex, $onclick, $onblur, $onchange, $onfocus, $onselect, $readonly);
                        break;

                    case 'bfCheckbox':
                        $this->renderCheckboxField($mdata, $tabIndex, $onclick, $onblur, $onchange, $onfocus, $onselect, $readonly);
                        break;

                    case 'bfSelect':
                        $this->renderSelectField($mdata, $tabIndex, $onclick, $onblur, $onchange, $onfocus, $onselect, $readonly);
                        break;

                    case 'bfFile':
                        $flashUploader = $this->renderFileField($mdata, $tabIndex, $onclick, $onblur, $onchange, $onfocus, $onselect, $readonly);
                        break;

                    case 'bfSubmitButton':
                        $this->renderSubmitButtonField($mdata, $tabIndex, $onblur, $onchange, $onfocus, $onselect, $readonly);
                        break;

                    case 'bfHidden':
                        $this->renderHiddenField($mdata);
                        break;

                    case 'bfSummarize':
                        $this->renderSummarizeField($mdata);
                        break;

                    case 'bfReCaptcha':
                        $this->renderReCaptchaField($mdata);
                        break;

                    case 'bfCaptcha':
                        $this->renderCaptchaField($mdata, $hintId);
                        break;

                    case 'bfCalendar':
                        $this->renderCalendarField($mdata, $hintId);
                        break;

                    case 'bfCalendarResponsive':
                        $this->renderCalendarResponsiveField($mdata, $hintId);
                        break;

                    case 'bfSignature':
                        $this->renderSignatureField($mdata);
                        break;

                    case 'bfStripe':
                        $this->renderStripeField($mdata, $tabIndex, $onblur, $onchange, $onfocus, $onselect, $readonly);
                        break;

                    case 'bfPayPal':
                        $this->renderPayPalField($mdata, $tabIndex, $onblur, $onchange, $onfocus, $onselect, $readonly);
                        break;
                }

                if (isset($mdata['bfName']) && isset($mdata['off']) && $mdata['off']) {
                    echo $this->quickModeDeactivationScriptBuilder()->field($mdata['bfName']);
                }

                if ($mdata['bfType'] == 'bfFile') {
                    echo '<span id="ff_elem' . $mdata['dbId'] . '_files"></span>';
                }

                echo $flashUploader;

                if ($hintLine !== '') {
                    echo '<small class="bfHint" id="bfHint' . (int) $mdata['dbId'] . '">'
                        . htmlspecialchars($hintLine, ENT_QUOTES, 'UTF-8') . '</small>' . "\n";
                }

                if ($mdata['bfType'] != 'bfHidden') {
                    if ($options['displayType'] == 'breaks') {
                        echo '</section>' . "\n";
                    } else {
                        echo '</span>' . "\n";
                    }
                }
            }
        }

        /**
         * Paging and wrapping of inline element containers
         */
        if ($dataObject['properties']['type'] == 'section' && $dataObject['properties']['displayType'] == 'inline') {
            echo '<div class="bfClearfix">' . "\n";
        }

        if (isset($dataObject['children']) && count($dataObject['children']) != 0) {
            $childrenAmount = count($dataObject['children']);
            for ($i = 0; $i < $childrenAmount; $i++) {
                $this->process($dataObject['children'][$i], $mdata, $parentPage, $i, $childrenAmount);
            }
        }

        if ($dataObject['properties']['type'] == 'section' && $dataObject['properties']['displayType'] == 'inline') {
            echo '</div>' . "\n";
        }

        if ($dataObject['properties']['type'] == 'section' && $dataObject['properties']['bfType'] == 'section') {
            echo '</fieldset></div></div></div><div class="bfFieldset-bl"><div class="bfFieldset-br"><div class="bfFieldset-b"></div></div></div></div><!-- bfFieldset-wrapper end -->' . "\n";
        } elseif ($dataObject['properties']['type'] == 'section' && $dataObject['properties']['bfType'] == 'normal') {
            if (isset($dataObject['properties']['name']) && $dataObject['properties']['name'] != '') {
                echo '</div>' . "\n";
            }
        } elseif ($dataObject['properties']['type'] == 'page') {
            $isLastPage = false;
            if ($this->rootMdata['lastPageThankYou'] && $dataObject['properties']['pageNumber'] == count($this->quickModeRootChildren()) && count($this->quickModeRootChildren()) > 1) {
                $isLastPage = true;
            }

            if (!$isLastPage) {
                $last = 0;
                if ($this->rootMdata['lastPageThankYou']) {
                    $last = 1;
                }

                if ($this->rootMdata['pagingInclude'] && $dataObject['properties']['pageNumber'] > 1) {
                    /* translatables */
                    $translatedPreviousLabel = $this->classicTranslation(
                        $this->rootMdata,
                        'pagingPrevLabel_translation' . $this->language_tag
                    );
                    if ($translatedPreviousLabel !== null) {
                        $this->rootMdata['pagingPrevLabel'] = $translatedPreviousLabel;
                    }
                    /* translatables end */
                    $previousLabel = $this->classicRootString('pagingPrevLabel');
                    echo '<button type="button" class="btn btn-primary bfPrevButton button' . $this->fadingClass . '" type="submit" onclick="' . $this->quickModePagingActionBuilder()->previous() . '" value="' . htmlentities(trim($previousLabel), ENT_QUOTES, 'UTF-8') . '"><span>' . htmlentities(trim($previousLabel), ENT_QUOTES, 'UTF-8') . '</span></button>' . "\n";
                }

                if ($this->rootMdata['pagingInclude'] && $dataObject['properties']['pageNumber'] < count($this->quickModeRootChildren()) - $last) {
                    /* translatables */
                    $translatedNextLabel = $this->classicTranslation(
                        $this->rootMdata,
                        'pagingNextLabel_translation' . $this->language_tag
                    );
                    if ($translatedNextLabel !== null) {
                        $this->rootMdata['pagingNextLabel'] = $translatedNextLabel;
                    }
                    /* translatables end */
                    $nextLabel = $this->classicRootString('pagingNextLabel');
                    echo '<button type="button" class="btn btn-primary bfNextButton button' . $this->fadingClass . '" type="submit" onclick="' . $this->quickModePagingActionBuilder()->next() . '" value="' . htmlentities(trim($nextLabel), ENT_QUOTES, 'UTF-8') . '"><span>' . htmlentities(trim($nextLabel), ENT_QUOTES, 'UTF-8') . '</span></button>' . "\n";
                }

                $callSubmit = $this->quickModeSubmitActionBuilder()->build(false, $this->hasFlashUpload);
                $pageNumber = $mdata['pageNumber'] ?? 0;
                if ($this->rootMdata['submitInclude'] && $pageNumber + 1 > count($this->quickModeRootChildren()) - $last) {
                    /* translatables */
                    $translatedSubmitLabel = $this->classicTranslation(
                        $this->rootMdata,
                        'submitLabel_translation' . $this->language_tag
                    );
                    if ($translatedSubmitLabel !== null) {
                        $this->rootMdata['submitLabel'] = $translatedSubmitLabel;
                    }
                    /* translatables end */
                    $submitLabel = $this->classicRootString('submitLabel');
                    echo '<button type="button" id="bfSubmitButton" class="btn btn-primary bfSubmitButton button' . $this->fadingClass . '" onclick="if(typeof bf_htmltextareainit != \'undefined\'){ bf_htmltextareainit() }if(document.getElementById(\'bfPaymentMethod\')){document.getElementById(\'bfPaymentMethod\').value=\'\';};' . $callSubmit . ';" value="' . htmlentities(trim($submitLabel), ENT_QUOTES, 'UTF-8') . '"><span>' . htmlentities(trim($submitLabel), ENT_QUOTES, 'UTF-8') . '</span></button>' . "\n";
                }

                if ($this->rootMdata['cancelInclude'] && $pageNumber + 1 > count($this->quickModeRootChildren()) - $last) {
                    /* translatables */
                    $translatedCancelLabel = $this->classicTranslation(
                        $this->rootMdata,
                        'cancelLabel_translation' . $this->language_tag
                    );
                    if ($translatedCancelLabel !== null) {
                        $this->rootMdata['cancelLabel'] = $translatedCancelLabel;
                    }
                    /* translatables end */
                    $cancelLabel = $this->classicRootString('cancelLabel');
                    echo '<button class="btn btn-primary bfCancelButton button' . $this->fadingClass . '" type="button" onclick="' . $this->quickModePagingActionBuilder()->cancel() . '"  value="' . htmlentities(trim($cancelLabel), ENT_QUOTES, 'UTF-8') . '"><span>' . htmlentities(trim($cancelLabel), ENT_QUOTES, 'UTF-8') . '</span></button>' . "\n";
                }
            }
        }
    }

    /**
     * Field renderers extracted from process()'s bfType switch, one field
     * type at a time, each covered by a characterization test (see
     * tests/Site/Service/Rendering/QuickMode/ClassicRendererCharacterizationTest.php)
     * before being extracted, so any accidental behavior change shows up as
     * a snapshot diff. Purely mechanical moves - no logic changed.
     */

    /**
     * @param array<string, mixed> $mdata
     */
    private function renderTextfieldField(array $mdata, string $tabIndex, string $onclick, string $onblur, string $onchange, string $onfocus, string $onselect, string $readonly): void
    {
        $mdata = $this->normalizeClassicFieldData($mdata);
        $size = '';
        if ($mdata['size'] != '') {
            $size = 'style="width:' . htmlentities(strip_tags($mdata['size'])) . '" ';
        }
        echo $this->quickModeTextFieldStrategy()->textfield(
            $mdata,
            $this->language_tag,
            'ff_elem',
            $size . $tabIndex,
            $onclick . $onblur . $onchange . $onfocus . $onselect . $readonly
        );
        if ($mdata['mailbackAsSender']) {
            echo '<input type="hidden" name="mailbackSender[' . $mdata['bfName'] . ']" value="true"/>' . "\n";
        }
    }

    /**
     * @param array<string, mixed> $mdata
     */
    private function renderNumberInputField(array $mdata, string $tabIndex, string $onclick, string $onblur, string $onchange, string $onfocus, string $onselect, string $readonly): void
    {
        $mdata = $this->normalizeClassicFieldData($mdata);
        echo $this->quickModeTextFieldStrategy()->numberInput(
            $mdata,
            $this->language_tag,
            'ff_elem inputbox',
            $tabIndex,
            $onclick . $onblur . $onchange . $onfocus . $onselect . $readonly,
            'max'
        );

        // set size of element, number input doesn't allow size attr

        if ($mdata['size'] != '') {
            RuntimeAssetLoader::script(
                $this->p->app,
                Uri::root(true) . '/media/com_breezingformsng/js/site/quickmode-number-input.js'
            );
            echo '<script type="text/javascript">bfSetNumberInputWidth('
                . json_encode((int) $mdata['dbId']) . ', ' . json_encode($mdata['size']) . ');</script>';
        }
    }

    /**
     * @param array<string, mixed> $mdata
     */
    private function renderTextareaField(array $mdata, string $tabIndex, string $onclick, string $onblur, string $onchange, string $onfocus, string $onselect, string $readonly): void
    {
        $mdata = $this->normalizeClassicFieldData($mdata);
        /* translatables */
        $translatedPlaceholder = $this->classicTranslation(
            $mdata,
            'placeholder_translation' . $this->language_tag
        );
        if ($translatedPlaceholder !== null) {
            $mdata['placeholder'] = $translatedPlaceholder;
        }
        $translatedValue = $this->classicTranslation($mdata, 'value_translation' . $this->language_tag);
        if ($translatedValue !== null) {
            $mdata['value'] = $translatedValue;
        }
        /* translatables end */

        if (isset($mdata['is_html']) && $mdata['is_html']) {
            echo '<div style="display: inline-block; vertical-align: top; width: ' . strip_tags($mdata['width']) . ';">';
            $editor = Editor::getInstance($this->classicString($this->p->app->get('editor'), 'editor'));
            $this->htmltextareas[] = 'ff_nm_' . $mdata['bfName'] . '[]';
            $this->htmltextareasDbIds[] = 'ff_elem' . $mdata['dbId'];
            echo $editor->display('ff_nm_' . $mdata['bfName'] . '[]', htmlentities(trim($mdata['value']), ENT_QUOTES, 'UTF-8'), strip_tags($mdata['width']), strip_tags($mdata['height']), 75, 20, true, 'ff_elem' . $mdata['dbId']);
            echo '<style type="text/css">.toggle-editor{display: none;}</style>';
            echo '</div>';
        } else {
            echo $this->quickModeTextareaStrategy()->build(
                $mdata,
                $this->language_tag,
                'ff_elem',
                $tabIndex . $onclick . $onblur . $onchange . $onfocus . $onselect . $readonly,
                'cols="20" rows="5" '
            );
        }
    }

    /**
     * @param array<string, mixed> $mdata
     */
    private function renderChoiceGroupField(string $type, array $mdata, string $tabIndex, string $onclick, string $onblur, string $onchange, string $onfocus, string $onselect, string $readonly): void
    {
        $mdata = $this->normalizeClassicFieldData($mdata);
        /* translatables */
        $translatedGroup = $this->classicTranslation($mdata, 'group_translation' . $this->language_tag);
        if ($translatedGroup !== null) {
            $mdata['group'] = $translatedGroup;
        }
        /* translatables end */
        echo $this->classicChoiceGroupBuilder()->build(
            $type,
            (int) $mdata['dbId'],
            (string) $mdata['bfName'],
            (string) $mdata['group'],
            (bool) $mdata['wrap'],
            (string) $mdata['labelPosition'],
            $tabIndex,
            $onclick . $onblur . $onchange . $onfocus . $onselect,
            $readonly !== '',
            "\n",
            empty($mdata['hideLabel'])
        );
    }


    /**
     * @param array<string, mixed> $mdata
     */
    private function renderCheckboxField(array $mdata, string $tabIndex, string $onclick, string $onblur, string $onchange, string $onfocus, string $onselect, string $readonly): void
    {
        $mdata = $this->normalizeClassicFieldData($mdata);
        echo $this->quickModeCheckboxStrategy()->build(
            $mdata,
            'ff_elem',
            $tabIndex . $onclick . $onblur . $onchange . $onfocus . $onselect,
            $readonly !== ''
        );
        if ($mdata['mailbackAccept']) {
            echo '<input type="hidden" class="ff_elem" name="mailbackConnectWith[' . $mdata['mailbackConnectWith'] . ']" value="true_' . $mdata['bfName'] . '"/>' . "\n";
        }
    }

    /**
     * @param array<string, mixed> $mdata
     */
    private function renderSelectField(array $mdata, string $tabIndex, string $onclick, string $onblur, string $onchange, string $onfocus, string $onselect, string $readonly): void
    {
        $mdata = $this->normalizeClassicFieldData($mdata);
        /* translatables */
        $translatedList = $this->classicTranslation($mdata, 'list_translation' . $this->language_tag);
        if ($translatedList !== null) {
            $mdata['list'] = $translatedList;
        }
        /* translatables end */
        if ($mdata['list'] != '') {
            $width = '';
            if (isset($mdata['width']) && $mdata['width'] != '') {
                $width = 'width:' . htmlentities(strip_tags($mdata['width'])) . ';';
            }
            $height = '';
            if (isset($mdata['height']) && $mdata['height'] != '') {
                $height = 'height:' . htmlentities(strip_tags($mdata['height'])) . ';';
            }
            $size = '';
            if ($height != '' || $width != '') {
                $size = 'style="' . $width . $height . '" ';
            }

            echo $this->quickModeSelectBuilder()->build(
                'ff_elem chzn-done',
                (string) $mdata['bfName'],
                (int) $mdata['dbId'],
                (string) $mdata['list'],
                (bool) $mdata['multiple'],
                $tabIndex . $onclick . $onblur . $onchange . $onfocus . $onselect . $readonly,
                $size
            );
        }
    }

    /**
     * @param array<string, mixed> $mdata
     */
    private function renderSubmitButtonField(array $mdata, string $tabIndex, string $onblur, string $onchange, string $onfocus, string $onselect, string $readonly): void
    {
        $mdata = $this->normalizeClassicFieldData($mdata);
        /* translatables */
        $translatedSource = $this->classicTranslation($mdata, 'src_translation' . $this->language_tag);
        if ($translatedSource !== null) {
            $mdata['src'] = $translatedSource;
        }
        $translatedValue = $this->classicTranslation($mdata, 'value_translation' . $this->language_tag);
        if ($translatedValue !== null) {
            $mdata['value'] = $translatedValue;
        }
        /* translatables end */

        $value = '';
        $type = 'submit';
        $src = '';

        if ($mdata['src'] != '') {
            $type = 'image';
            $src = 'src="' . $mdata['src'] . '" ';
        }
        if ($mdata['value'] != '') {
            $value = 'value="' . htmlentities(trim($mdata['value']), ENT_QUOTES, 'UTF-8') . '" ';
        }
        if (isset($mdata['actionClick']) && $mdata['actionClick'] == 1) {
            $onclick = 'onclick="if(typeof bf_htmltextareainit != \'undefined\'){ bf_htmltextareainit() }populateSummarizers();if(document.getElementById(\'bfPaymentMethod\')){document.getElementById(\'bfPaymentMethod\').value=\'\';};' . $mdata['actionFunctionName'] . '(this,\'click\');return false;" ';
        } else {
            $onclick = 'onclick="if(typeof bf_htmltextareainit != \'undefined\'){ bf_htmltextareainit() }populateSummarizers();if(document.getElementById(\'bfPaymentMethod\')){document.getElementById(\'bfPaymentMethod\').value=\'\';};return false;" ';
        }
        if ($src == '') {
            echo $this->quickModeSubmitButtonBuilder()->build(
                'button',
                'type="button" class="ff_elem btn btn-primary bfCustomSubmitButton"',
                $value . $src . $tabIndex . $onclick . $onblur . $onchange . $onfocus . $onselect . $readonly,
                $type,
                (string) $mdata['bfName'],
                (int) $mdata['dbId'],
                '<span>' . $mdata['value'] . '</span>'
            );
        } else {
            echo $this->quickModeSubmitButtonBuilder()->build(
                'input',
                'type="image" class="ff_elem btn btn-primary bfCustomSubmitButton"',
                $value . $src . $tabIndex . $onclick . $onblur . $onchange . $onfocus . $onselect . $readonly,
                $type,
                (string) $mdata['bfName'],
                (int) $mdata['dbId'],
                '',
                '',
                ' value="' . $mdata['value'] . '"'
            );
        }
    }

    /**
     * @param array<string, mixed> $mdata
     */
    private function renderSummarizeField(array $mdata): void
    {
        $mdata = $this->normalizeClassicFieldData($mdata);
        /* translatables */
        $translatedEmptyMessage = $this->classicTranslation(
            $mdata,
            'emptyMessage_translation' . $this->language_tag
        );
        if ($translatedEmptyMessage !== null) {
            $mdata['emptyMessage'] = $translatedEmptyMessage;
        }
        /* translatables end */

        echo '<span class="ff_elem bfSummarize" id="ff_elem' . $mdata['dbId'] . '"></span>' . "\n";
        echo '<script type="text/javascript">bfRegisterSummarize('
            . json_encode('ff_elem' . $mdata['dbId']) . ', '
            . json_encode($mdata['connectWith']) . ', '
            . json_encode($mdata['connectType']) . ', '
            . json_encode($mdata['emptyMessage']) . ', '
            . json_encode((bool) $mdata['hideIfEmpty']) . ');</script>';
        if (trim($mdata['fieldCalc']) != '') {
            echo '<script type="text/javascript">
                                                        <!--
					function bfFieldCalcff_elem' . $mdata['dbId'] . '(value){
						if(!isNaN(value)){
							value = Number(value);
						}
						' . $mdata['fieldCalc'] . '
						return value;
					}
                                                        //-->
					</script>';
        }
    }

    /**
     * @param array<string, mixed> $mdata
     */
    private function renderReCaptchaField(array $mdata): void
    {
        $mdata = $this->normalizeClassicFieldData($mdata);
        echo $this->quickModeReCaptchaFieldBuilder()->build(
            $mdata,
            $this->p->app,
            $this->hasFlashUpload,
            true,
            true
        );
    }

    /**
     * @param array<string, mixed> $mdata
     */
    private function renderCaptchaField(array $mdata, string $hintId): void
    {
        $mdata = $this->normalizeClassicFieldData($mdata);
        $captcha_url = $this->quickModeCaptchaUrlBuilder()->build(
            Uri::root(true),
            $this->p->app->isClient('administrator')
        );

        echo '<span class="bfCaptcha">' . "\n";

        echo $this->quickModeCaptchaMarkupBuilder()->buildImage(
            isset($mdata['width']) && intval($mdata['width']) > 0 ? ' width="' . intval($mdata['width']) . '"' : 'width="230"',
            'ff_capimgValue',
            'ff_capimg',
            $captcha_url,
            '',
            Text::_('COM_BREEZINGFORMSNG_CAPTCHA_IMAGE_ALT')
        );

        echo '<br/>';
        echo $this->quickModeCaptchaMarkupBuilder()->buildResponseInput(
            isset($mdata['width']) && intval($mdata['width']) > 0 && (intval($mdata['width']) - 45 >= 230) ? ' style="width:' . (intval($mdata['width']) - 45) . 'px;"' : '',
            'ff_elem',
            $hintId !== '' ? 'aria-describedby="' . $hintId . '" ' : '',
            true
        );
        echo '<a href="#" class="ff_elem" role="button" onclick="' . $this->quickModeCaptchaReloadScriptBuilder()->build($captcha_url) . '"><img alt="' . htmlspecialchars(Text::_('COM_BREEZINGFORMSNG_RC_REFRESH'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '" src="' . Uri::root(true) . '/media/com_breezingformsng/images/site/captcha/refresh-captcha.png" /></a>' . "\n";
        echo '</span>' . "\n";
    }

    /**
     * @param array<string, mixed> $mdata
     */
    private function renderCalendarField(array $mdata, string $hintId): void
    {
        $mdata = $this->normalizeClassicFieldData($mdata);
        /* translatables */
        $translatedValue = $this->classicTranslation($mdata, 'value_translation' . $this->language_tag);
        if ($translatedValue !== null) {
            $mdata['value'] = $translatedValue;
        }
        $translatedFormat = $this->classicTranslation($mdata, 'format_translation' . $this->language_tag);
        if ($translatedFormat !== null) {
            $mdata['format'] = $translatedFormat;
        }
        /* translatables end */
        $exploded = explode('::', trim((string) $mdata['value']));
        $left = '';

        if (count($exploded) == 2) {
            $left = trim($exploded[0]);
        } elseif (count($exploded) == 1) {
            $left = trim($exploded[0]);

            if ($left === '...') {
                $left = '';
            }
        }

        // public static function calendar($value, $name, $id, $format = '%Y-%m-%d', $attribs = array())
        $calAttr = [
            'class' => 'ff_elem bfCalendar',
            'showTime' => $this->bfCalendarShowTimeEnabled($mdata),
            'timeFormat' => $this->bfCalendarIsTruthy($mdata, 'timeFormat') ? '24' : '12',
            'singleHeader' => $this->bfCalendarIsTruthy($mdata, 'singleHeader'),
            'todayBtn' => $this->bfCalendarIsTruthy($mdata, 'todayButton'),
            'weekNumbers' => $this->bfCalendarIsTruthy($mdata, 'weekNumbers'),
            'minYear' => (isset($mdata['minYear']) && $mdata['minYear'] != '') ? '-' . $mdata['minYear'] : '',
            'maxYear' => (isset($mdata['maxYear']) && $mdata['maxYear'] != '') ? '+' . $mdata['maxYear'] : '',
            'firstDay' => (isset($mdata['firstDay']) && $mdata['firstDay'] != '') ? $mdata['firstDay'] : '7',
        ];

        if ($hintId !== '') {
            $calAttr['aria-describedby'] = $hintId;
        }

        echo $this->classicString(
            HTMLHelper::_('calendar', $left, "ff_nm_" . $mdata['bfName'] . "[]", "ff_elem" . $mdata['dbId'], $mdata['format'], $calAttr),
            'calendarMarkup'
        );
    }

    /**
     * @param array<string, mixed> $mdata
     */
    private function renderCalendarResponsiveField(array $mdata, string $hintId): void
    {
        $mdata = $this->normalizeClassicFieldData($mdata);
        /* translatables */
        $translatedValue = $this->classicTranslation($mdata, 'value_translation' . $this->language_tag);
        if ($translatedValue !== null) {
            $mdata['value'] = $translatedValue;
        }
        $translatedFormat = $this->classicTranslation($mdata, 'format_translation' . $this->language_tag);
        if ($translatedFormat !== null) {
            $mdata['format'] = $translatedFormat;
        }
        /* translatables end */
        $mdata['format'] = $this->bfCalendarToPickadateFormat($mdata['format']);
        $pickerFirstDay = $this->bfCalendarToPickadateFirstDay(isset($mdata['firstDay']) ? $mdata['firstDay'] : '');
        $pickerSelectYears = $this->bfCalendarSelectYears($mdata);

        $size = 'style="width: 65%;min-width: 65%;max-width: 65%;" ';
        if ($mdata['size'] != '') {
            $size = 'style="width:' . htmlentities(strip_tags($mdata['size'])) . ';max-width:' . htmlentities(strip_tags($mdata['size'])) . ';min-width:' . htmlentities(strip_tags($mdata['size'])) . ';" ';
        }

        $exploded = explode('::', trim($mdata['value']));

        $left = '';
        $right = '';
        if (count($exploded) == 2) {
            $left = trim($exploded[0]);
            $right = trim($exploded[1]);
        } else {
            $right = trim($exploded[0]);
        }
        if ($right === '') {
            $right = '...';
        }

        echo '<span class="bfElementGroupNoWrap" id="bfElementGroupNoWrap' . $mdata['dbId'] . '">' . "\n";
        echo $this->quickModeCalendarInputBuilder()->build(
            'ff_elem bfCalendarInput',
            (string) $mdata['bfName'],
            (int) $mdata['dbId'],
            (string) $left,
            $size . ($hintId !== '' ? 'aria-describedby="' . $hintId . '" ' : '')
        );
        echo $this->quickModeCalendarButtonBuilder()->build(
            'type="button"',
            'ff_elem' . $mdata['dbId'] . '_calendarButton',
            'bfCalendar btn btn-secondary',
            (string) $right,
            '<span>' . htmlentities($right, ENT_QUOTES, 'UTF-8') . '</span>'
        );
        echo '</span>' . "\n";

        if (!$this->hasResponsiveDatePicker) {
            $document = $this->p->app->getDocument() ?? throw new \RuntimeException('The document is not available.');
            $document->getWebAssetManager()->addInlineScript(
                'var bfPickerMinusYearIcon = ' . json_encode(Uri::root(true) . '/components/com_breezingformsng/libraries/jquery/pickadate/minusyear.png') . ';'
                . "\n" . 'var bfPickerPlusYearIcon = ' . json_encode(Uri::root(true) . '/components/com_breezingformsng/libraries/jquery/pickadate/plusyear.png') . ';'
            );
            RuntimeAssetLoader::script($this->p->app, Uri::root(true) . '/media/com_breezingformsng/js/site/quickmode-calendar-responsive.js');
            RuntimeAssetLoader::script($this->p->app, Uri::root(true) . '/media/com_breezingformsng/js/site/quickmode-calendar-responsive-init.js');
        }

        echo $this->quickModeCalendarInitScriptBuilder()->buildResponsive(
            (int) $mdata['dbId'],
            (string) $mdata['format'],
            (int) $pickerSelectYears,
            (int) $pickerFirstDay,
            true
        );

        $this->hasResponsiveDatePicker = true;
    }

    /**
     * @param array<string, mixed> $mdata
     */
    private function renderSignatureField(array $mdata): void
    {
        $mdata = $this->normalizeClassicFieldData($mdata);
        RuntimeAssetLoader::script($this->p->app, Uri::root(true) . '/components/com_breezingformsng/libraries/js/signature.js');
        RuntimeAssetLoader::script($this->p->app, Uri::root(true) . '/media/com_breezingformsng/js/site/quickmode-signature.js');
        $document = $this->p->app->getDocument() ?? throw new \RuntimeException('The document is not available.');
        $document->getWebAssetManager()->addInlineScript(
            'bfSignatureInit(' . json_encode((int) $mdata['dbId']) . ');'
        );

        echo QuickModeSignatureMarkupBuilder::build(
            (int) $mdata['dbId'],
            (string) $mdata['bfName'],
            'class="btn btn-primary" onclick="bfSignatureReset(' . json_encode((int) $mdata['dbId'])
                . ');" class="bfSignatureResetButton button"',
            Text::_('COM_BREEZINGFORMSNG_SIGNATURE_RESET_BUTTON'),
            true
        );
        echo QuickModeHiddenFieldBuilder::build([
            'bfName' => $mdata['bfName'],
            'value' => '',
            'dbId' => $mdata['dbId'],
        ]);
    }

    /**
     * @param array<string, mixed> $mdata
     */
    private function renderStripeField(array $mdata, string $tabIndex, string $onblur, string $onchange, string $onfocus, string $onselect, string $readonly): void
    {
        $mdata = $this->normalizeClassicFieldData($mdata);
        /* translatables */
        $translatedImage = $this->classicTranslation($mdata, 'image_translation' . $this->language_tag);
        if ($translatedImage !== null) {
            $mdata['image'] = $translatedImage;
        }
        /* translatables end */

        echo QuickModePaymentButtonBuilder::build(
            'Stripe',
            (string) $mdata['bfName'],
            (int) $mdata['dbId'],
            (string) $mdata['image'],
            'Stripe',
            'Stripe',
            $tabIndex,
            $onblur,
            $onchange,
            $onfocus,
            $onselect,
            $readonly,
            isset($mdata['actionClick']) && $mdata['actionClick'] == 1,
            (string) ($mdata['actionFunctionName'] ?? ''),
            $this->quickModeSubmitActionBuilder()->buildForPaymentButton(false)
        );
    }

    /**
     * @param array<string, mixed> $mdata
     */
    private function renderPayPalField(array $mdata, string $tabIndex, string $onblur, string $onchange, string $onfocus, string $onselect, string $readonly): void
    {
        $mdata = $this->normalizeClassicFieldData($mdata);
        /* translatables */
        $translatedImage = $this->classicTranslation($mdata, 'image_translation' . $this->language_tag);
        if ($translatedImage !== null) {
            $mdata['image'] = $translatedImage;
        }
        /* translatables end */

        echo QuickModePaymentButtonBuilder::build(
            'PayPal',
            (string) $mdata['bfName'],
            (int) $mdata['dbId'],
            (string) $mdata['image'],
            'PayPal',
            '',
            $tabIndex,
            $onblur,
            $onchange,
            $onfocus,
            $onselect,
            $readonly,
            isset($mdata['actionClick']) && $mdata['actionClick'] == 1,
            (string) ($mdata['actionFunctionName'] ?? ''),
            $this->quickModeSubmitActionBuilder()->buildForPaymentButton(false)
        );
    }

    /**
     * @param array<string, mixed> $mdata
     */
    private function renderFileField(array $mdata, string $tabIndex, string $onclick, string $onblur, string $onchange, string $onfocus, string $onselect, string $readonly): string
    {
        $mdata = $this->normalizeClassicFieldData($mdata);
                    $flashUploader = '';
        if ($mdata['flashUploader'] || $mdata['html5']) {
            $base = (new QuickModeUploadBasePathBuilder())->build(Uri::base());

            echo '<input type="hidden" id="flashUpload' . $mdata['bfName'] . '" name="flashUpload' . $mdata['bfName'] . '" value="bfFlashFileQueue' . $mdata['dbId'] . '"/>' . "\n";
            $this->hasFlashUpload = true;
            $uploadOptions = $this->quickModeUploadOptionsBuilder()->build($mdata);
            $exts = $uploadOptions['extensions'];
            $bytes = $uploadOptions['maxFileSize'];
            $multiSelection = $uploadOptions['multiSelection'];
            $flashUploader = "
                                                        <label id=\"bfUploadContainer" . $mdata['dbId'] . "\">
							<img alt=\"\" style=\"cursor: pointer;\" id=\"bfPickFiles" . $mdata['dbId'] . "\" src=\"" . $this->uploadImagePath . "\" width=\"" . $uploadOptions['buttonWidth'] . "\" height=\"" . $uploadOptions['buttonHeight'] . "\"/>
                                                        <div id=\"bfPickFiles" . $mdata['dbId'] . "holder\" style=\"display:none;\">&nbsp;</div>
                                                        </label>
                                                        <span id=\"bfUploader" . $mdata['bfName'] . "\"></span>
                                                        <div class=\"bfFlashFileQueueClass\" id=\"bfFlashFileQueue" . $mdata['dbId'] . "\"></div>
                                                        <script type=\"text/javascript\">
                                                        <!--
							bfFlashUploaders.push('ff_elem" . $mdata['dbId'] . "');
                                                        var bfFlashFileQueue" . $mdata['dbId'] . " = {};
" . QuickModeUploadThumbnailScriptBuilder::build(chr(10)) . "
                                                        JQuery(document).ready(
                                                            function() {
" . (new QuickModeUploadConfigurationBuilder())->build(
    (int) $this->p->form,
    (string) $mdata['bfName'],
    (string) $this->flashUploadTicket,
    (string) $base,
    (int) $mdata['dbId'],
    (string) $exts,
    (string) $multiSelection,
    (string) json_encode(Text::_('COM_BREEZINGFORMSNG_CHOOSE_FILE'))
) . "
" . QuickModeUploadEntryCallbacksBuilder::build(
    (int) $mdata['dbId'],
    (string) $this->cancelImagePath,
    false,
    false,
    (string) $multiSelection,
    (int) $uploadOptions['maxBytes'],
    (string) $exts,
    Text::_('COM_BREEZINGFORMSNG_UPLOAD_FILE_TOO_LARGE_DETAIL'),
    Text::_('COM_BREEZINGFORMSNG_UPLOAD_EXTENSION_NOT_ALLOWED_DETAIL')
) . "
                                                                        // disable the button if no multi upload
                                                                        if( " . $multiSelection . " == false ){
                                                                            var the_size = JQuery('#bfFlashFileQueue" . $mdata['dbId'] . " .bfFileQueueItem').length;
                                                                            if( the_size > 0 ){
                                                                                JQuery('#bfPickFiles" . $mdata['dbId'] . "').css('display','none');
                                                                                JQuery('#bfPickFiles" . $mdata['dbId'] . "holder').css('display','block');
                                                                            }
                                                                        }
                                                                });
" . QuickModeUploadProgressScriptBuilder::build(chr(10)) . "
" . QuickModeUploadCompletedScriptBuilder::build(chr(10)) . "
                                                                uploader.init();
                                                                bfUploaders.push(uploader);
                                                            });
							//-->
                                                        </script>
							";
            echo QuickModeFileInputBuilder::build(
                $tabIndex . $onclick . $onblur . $onchange . $onfocus . $onselect . $readonly,
                'hidden',
                (string) $mdata['bfName'],
                (int) $mdata['dbId'],
                WidgetFieldBuilder::fileAttributes($mdata)
            );
        } else {
            echo QuickModeFileInputBuilder::build(
                $tabIndex . $onclick . $onblur . $onchange . $onfocus . $onselect . $readonly,
                'file',
                (string) $mdata['bfName'],
                (int) $mdata['dbId'],
                WidgetFieldBuilder::fileAttributes($mdata)
            );
        }
        if (!empty($mdata['attachToAdminMail'])) {
            echo '<input type="hidden" name="attachToAdminMail[' . $mdata['bfName'] . ']" value="true"/>' . "\n";
        }
        if (!empty($mdata['attachToUserMail'])) {
            echo '<input type="hidden" name="attachToUserMail[' . $mdata['bfName'] . ']" value="true"/>' . "\n";
        }

                    return $flashUploader;
    }

    /**
     * @return void
     */
    public function render()
    {

        $dataObject = $this->dataObject;
        $this->process($dataObject);
        echo '</div>' . "\n"; // closing last page

        $this->headers();

        if ($this->hasResponsiveDatePicker) {
            RuntimeAssetLoader::script($this->p->app, Uri::root(true) . '/components/com_breezingformsng/libraries/jquery/pickadate/picker.js');
            RuntimeAssetLoader::script($this->p->app, Uri::root(true) . '/components/com_breezingformsng/libraries/jquery/pickadate/picker.date.js');

            $language = $this->p->app->getLanguage() ?? throw new \RuntimeException('The language is not loaded.');
            $lang = $language->getTag();
            $lang = explode('-', $lang);
            $lang = strtolower($lang[0]);
            if (file_exists(JPATH_SITE . '/components/com_breezingformsng/libraries/jquery/pickadate/translations/' . $lang . '.js')) {
                RuntimeAssetLoader::script($this->p->app, Uri::root(true) . '/components/com_breezingformsng/libraries/jquery/pickadate/translations/' . $lang . '.js');
            }

            RuntimeAssetLoader::style($this->p->app, Uri::root(true) . '/components/com_breezingformsng/libraries/jquery/pickadate/themes/default.css');
            RuntimeAssetLoader::style($this->p->app, Uri::root(true) . '/components/com_breezingformsng/libraries/jquery/pickadate/themes/default.date.css');
        }

        // we gonna add a blank to each textarea, since the value is transferred upon submit
        // requires a different mandatory validation than ff_valuenotempty
        if (count($this->htmltextareas)) {
            RuntimeAssetLoader::script(
                $this->p->app,
                Uri::root(true) . '/media/com_breezingformsng/js/site/quickmode-html-textareas.js'
            );
            foreach ($this->htmltextareas as $index => $htmltextarea) {
                $editorContent = $this->getEditorContent($this->htmltextareasDbIds[$index]);
                echo $this->quickModeHtmlTextareaScriptBuilder()->build(
                    $htmltextarea,
                    $editorContent
                );
            }
        }

        if ($this->hasFlashUpload) {
            $tickets = $this->p->app->getSession()->get('bfFlashUploadTickets', array());
            if (!is_array($tickets)) {
                $tickets = [];
            }
            $tickets[$this->flashUploadTicket] = array(); // stores file info for later processing
            $this->p->app->getSession()->set('bfFlashUploadTickets', $tickets);
            echo '<input type="hidden" name="bfFlashUploadTicket" value="' . $this->flashUploadTicket . '"/>' . "\n";
            RuntimeAssetLoader::script($this->p->app, Uri::root(true) . '/components/com_breezingformsng/libraries/jquery/center.js');
            RuntimeAssetLoader::script($this->p->app, Uri::root(true) . '/media/com_breezingformsng/js/site/quickmode-flash-upload.js');
            echo "<div style=\"visibility:hidden;\" id=\"bfFileQueue\"></div>";
            echo "<div style=\"visibility:hidden;display:none;\" id=\"bfSubmitMessage\">" . Text::_('COM_BREEZINGFORMSNG_SUBMIT_MESSAGE') . "</div>";
        }
        echo '<noscript>' . Text::_('COM_BREEZINGFORMSNG_JAVASCRIPT_REQUIRED') . '</noscript>' . "\n";
        $document = $this->p->app->getDocument() ?? throw new \RuntimeException('The document is not available.');
        $document->getWebAssetManager()->addInlineScript('//-->');
    }

    /**
     * @phpstan-assert QuickModeTreeNode $dataObject
     */
    private function assertQuickModeTreeNode(mixed $dataObject): void
    {
        if (
            !is_array($dataObject)
        ) {
            throw new \UnexpectedValueException('QuickMode tree node has an invalid shape.');
        }

        if (array_key_exists('properties', $dataObject) && !is_array($dataObject['properties'])) {
            throw new \UnexpectedValueException('QuickMode tree node properties have an invalid shape.');
        }

        if (
            array_key_exists('attributes', $dataObject)
            && (!is_array($dataObject['attributes'])
                || !isset($dataObject['attributes']['id'])
                || !is_string($dataObject['attributes']['id']))
        ) {
            throw new \UnexpectedValueException('QuickMode tree node attributes have an invalid shape.');
        }

        if (!array_key_exists('children', $dataObject)) {
            return;
        }

        if (!is_array($dataObject['children']) || !array_is_list($dataObject['children'])) {
            throw new \UnexpectedValueException('QuickMode tree children must be a list.');
        }
    }

    /**
     * @phpstan-assert QuickModeProcessNode $dataObject
     */
    private function assertQuickModeProcessNode(mixed $dataObject): void
    {
        $this->assertQuickModeTreeNode($dataObject);

        if (!isset($dataObject['properties']) || !is_array($dataObject['properties'])) {
            throw new \UnexpectedValueException('QuickMode process node properties have an invalid shape.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function quickModeNodeProperties(mixed $dataObject): array
    {
        $this->assertQuickModeProcessNode($dataObject);

        return $dataObject['properties'];
    }

    /**
     * @return list<mixed>
     */
    private function quickModeRootChildren(): array
    {
        $this->assertQuickModeTreeNode($this->dataObject);

        if (!isset($this->dataObject['children']) || !is_array($this->dataObject['children'])) {
            return [];
        }

        return $this->dataObject['children'];
    }

    /**
     * @param array<string, mixed> $mdata
     * @return ClassicFieldData
     */
    private function normalizeClassicFieldData(array $mdata): array
    {
        $normalized = $mdata;
        foreach (
            [
                'type', 'bfType', 'bfName', 'name', 'pageIntro', 'label', 'hint', 'labelPosition', 'title', 'description',
                'actionFunctionName', 'connectType', 'emptyMessage', 'fieldCalc', 'format', 'firstDay', 'minYear',
                'maxYear', 'width', 'height', 'size', 'value', 'placeholder', 'group', 'list', 'src', 'image',
                'mailbackConnectWith', 'theme', 'rolloverColor', 'toggleFields', 'pagingPrevLabel', 'pagingNextLabel',
                'submitLabel', 'cancelLabel',
            ] as $key
        ) {
            $normalized[$key] = $this->classicString($mdata[$key] ?? null, $key);
        }

        foreach (['dbId', 'pageNumber', 'tabIndex', 'maxlength'] as $key) {
            $normalized[$key] = $this->classicInteger($mdata[$key] ?? null, $key);
        }

        foreach (
            [
                'actionClick', 'actionBlur', 'actionChange', 'actionFocus', 'actionSelect', 'hideIfEmpty', 'hideLabel',
                'off', 'required', 'readonly', 'multiple', 'wrap', 'mailbackAccept', 'mailbackAsSender', 'is_html',
                'flashUploader', 'html5', 'invisibleCaptcha', 'showMaxlengthCounter', 'attachToAdminMail',
                'attachToUserMail', 'fadeIn', 'useErrorAlerts',
                'useDefaultErrors', 'useBalloonErrors', 'rollover', 'lastPageThankYou', 'pagingInclude', 'submitInclude',
                'cancelInclude', 'joomlaHint',
            ] as $key
        ) {
            $normalized[$key] = $this->classicBoolean($mdata[$key] ?? null, $key);
        }

        $normalized['connectWith'] = $this->classicStringOrList($mdata['connectWith'] ?? null, 'connectWith');

        return $this->validatedClassicFieldData($normalized);
    }

    /**
     * @return ClassicFieldData
     */
    private function validatedClassicFieldData(mixed $mdata): array
    {
        $this->assertClassicFieldData($mdata);

        return $mdata;
    }

    /**
     * @param mixed $mdata
     * @phpstan-assert ClassicFieldData $mdata
     */
    private function assertClassicFieldData(mixed $mdata): void
    {
        if (!is_array($mdata)) {
            throw new \UnexpectedValueException('Classic QuickMode metadata must be an array.');
        }

        $stringKeys = [
            'type', 'bfType', 'bfName', 'name', 'pageIntro', 'label', 'hint', 'labelPosition', 'title',
            'description', 'actionFunctionName', 'connectType', 'emptyMessage', 'fieldCalc', 'format', 'firstDay',
            'minYear', 'maxYear', 'width', 'height', 'size', 'value', 'placeholder', 'group', 'list', 'src',
            'image', 'mailbackConnectWith', 'theme', 'rolloverColor', 'toggleFields', 'pagingPrevLabel',
            'pagingNextLabel', 'submitLabel', 'cancelLabel',
        ];
        foreach ($stringKeys as $key) {
            if (!is_string($mdata[$key] ?? null)) {
                throw new \UnexpectedValueException(sprintf('Classic QuickMode metadata "%s" must be a string.', $key));
            }
        }

        foreach (['dbId', 'pageNumber', 'tabIndex', 'maxlength'] as $key) {
            if (!is_int($mdata[$key] ?? null)) {
                throw new \UnexpectedValueException(sprintf('Classic QuickMode metadata "%s" must be an integer.', $key));
            }
        }

        foreach (
            [
                'actionClick', 'actionBlur', 'actionChange', 'actionFocus', 'actionSelect', 'hideIfEmpty', 'hideLabel',
                'off', 'required', 'readonly', 'multiple', 'wrap', 'mailbackAccept', 'mailbackAsSender', 'is_html',
                'flashUploader', 'html5', 'invisibleCaptcha', 'showMaxlengthCounter', 'attachToAdminMail',
                'attachToUserMail', 'fadeIn', 'useErrorAlerts', 'useDefaultErrors', 'useBalloonErrors', 'rollover',
                'lastPageThankYou', 'pagingInclude', 'submitInclude', 'cancelInclude', 'joomlaHint',
            ] as $key
        ) {
            if (!is_bool($mdata[$key] ?? null)) {
                throw new \UnexpectedValueException(sprintf('Classic QuickMode metadata "%s" must be a boolean.', $key));
            }
        }

        if (!is_string($mdata['connectWith'] ?? null) && !is_array($mdata['connectWith'] ?? null)) {
            throw new \UnexpectedValueException('Classic QuickMode metadata "connectWith" has an invalid type.');
        }

        if (is_array($mdata['connectWith'] ?? null)) {
            foreach ($mdata['connectWith'] as $value) {
                if (!is_string($value)) {
                    throw new \UnexpectedValueException(
                        'Classic QuickMode metadata "connectWith" must contain only strings.'
                    );
                }
            }
        }
    }

    private function classicString(mixed $value, string $key): string
    {
        if ($value === null) {
            return '';
        }

        if (!is_scalar($value)) {
            throw new \UnexpectedValueException(sprintf('Classic QuickMode metadata "%s" must be scalar.', $key));
        }

        return (string) $value;
    }

    private function classicInteger(mixed $value, string $key): int
    {
        if ($value === null) {
            return 0;
        }

        if (!is_scalar($value)) {
            throw new \UnexpectedValueException(sprintf('Classic QuickMode metadata "%s" must be scalar.', $key));
        }

        return (int) $value;
    }

    private function classicBoolean(mixed $value, string $key): bool
    {
        if ($value === null) {
            return false;
        }

        if (!is_scalar($value)) {
            throw new \UnexpectedValueException(sprintf('Classic QuickMode metadata "%s" must be scalar.', $key));
        }

        return (bool) $value;
    }

    /**
     * @return string|list<string>
     */
    private function classicStringOrList(mixed $value, string $key): string|array
    {
        if ($value === null || is_scalar($value)) {
            return $this->classicString($value, $key);
        }

        if (!is_array($value) || !array_is_list($value)) {
            throw new \UnexpectedValueException(
                sprintf('Classic QuickMode metadata "%s" must be a string or a list.', $key)
            );
        }

        $normalized = [];
        foreach ($value as $index => $item) {
            $normalized[] = $this->classicString($item, $key . '[' . $index . ']');
        }

        return $normalized;
    }

    /**
     * @param array<string, mixed> $mdata
     */
    private function classicTranslation(array $mdata, string $key): ?string
    {
        if (!array_key_exists($key, $mdata)) {
            return null;
        }

        $value = $this->classicString($mdata[$key], $key);

        return $value !== '' ? $value : null;
    }

    private function classicRootString(string $key): string
    {
        return $this->classicString($this->rootMdata[$key] ?? null, $key);
    }

    /**
     * @param mixed $code
     * @return string
     */
    public function parseToggleFields($code)
    {
        /*
          example codes:

          turn on element bla if blub is on
          turn off section bla if blub is on
          turn on section bla if blub is off
          turn off element bla if blub is off

          if element opener is off set opener huhuu

          syntax:
          ACTION STATE TARGETCATEGORY TARGETNAME if SRCNAME is VALUE
         */

        return (new QuickModeToggleFieldsParser())->parse($this->classicString($code, 'toggleFields'));
    }
}
