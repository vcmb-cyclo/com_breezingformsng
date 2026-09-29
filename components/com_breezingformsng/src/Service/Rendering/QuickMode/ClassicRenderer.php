<?php

/**
 * BreezingForms NG - A Joomla Forms Application
 *
 * @version 6.0.0
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
use Joomla\CMS\Language\Text;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Editor\Editor;


class ClassicRenderer
{
    use HiddenFieldTrait;
    use CalendarOptionsTrait;

    /**
     * @var HTML_facileFormsProcessor
     */
    private $p = null;
    private $dataObject = array();
    private $rootMdata = array();
    private $fading = true;
    private $fadingClass = '';
    private $useErrorAlerts = false;
    private $useDefaultErrors = false;
    private $useBalloonErrors = false;
    private $rollover = false;
    private $rolloverColor = '';
    private $toggleFields = '';
    private $hasFlashUpload = false;
    private $flashUploadTicket = '';
    private $cancelImagePath = '';
    private $uploadImagePath = '';
    private $htmltextareas = array();
    private $htmltextareasDbIds = array();
    private $language_tag = '';
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

    public function headers()
    {

        $this->p->app->getDocument()->getWebAssetManager()->addInlineScript('
	    
	        JQuery(document).ready(function(){
	            JQuery(".ff_elem").closest(".input-group").removeClass("input-group");
	            JQuery(".ff_elem").next(".input-group-append").removeClass("input-group-append");
	            JQuery(".ff_elem").removeClass("form-control");
	            JQuery(".js-calendar").closest(".bfElemWrap").css("overflow","visible");
	            JQuery(".js-calendar").each(function(){
	                let elem_id = JQuery(this).closest(".bfElemWrap").find(".ff_elem").attr("id");
	                let _this = this;
	                JQuery("#"+elem_id+"_btn").on("click", function(){
	                    JQuery(_this).closest(".bfElemWrap").removeClass("bfRolloverBg");
	                    JQuery(_this).css("left", jQuery("#"+elem_id).position().left);
	                });
	            });
	        });
	    ');

        if ($this->hasFlashUpload) {
            RuntimeAssetLoader::script($this->p->app, Uri::root(true) . '/components/com_breezingformsng/libraries/jquery/plupload/moxie.js');
            RuntimeAssetLoader::script($this->p->app, Uri::root(true) . '/components/com_breezingformsng/libraries/jquery/plupload/plupload.js');
            RuntimeAssetLoader::script($this->p->app, Uri::root(true) . '/media/com_breezingformsng/js/site/quickmode-plupload-compat.js');
        }
        $this->p->app->getDocument()->getWebAssetManager()->useScript('jquery');
        RuntimeAssetLoader::style($this->p->app, Uri::root(true) . '/media/com_breezingformsng/css/site/quickmode-runtime.css');
        HTMLHelper::_('bootstrap.tooltip', '.hasTooltip');
        $this->p->app->getDocument()->getWebAssetManager()->addInlineStyle('
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

        $this->p->app->getDocument()->getWebAssetManager()->addInlineScript(
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
                $this->p->app->getDocument()->getWebAssetManager()->addInlineScript(
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
                $this->p->app->getDocument()->getWebAssetManager()->addInlineScript(
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

    public function __construct(HTML_facileFormsProcessor $p)
    {
        $this->p = $p;

        $default = ComponentHelper::getParams('com_languages')->get('site');
        $this->language_tag = $this->p->app->getLanguage()->getTag() != $default ? $this->p->app->getLanguage()->getTag() : 'zz-ZZ';

        $this->p->app->getDocument()->getWebAssetManager()->addInlineScript('<!--');

        $this->dataObject = json_decode(bf_b64dec($this->p->formrow->template_code), true);
        $this->rootMdata = $this->dataObject['properties'];

        if ($this->p->app->getInput()->getString('ff_applic', '') != 'mod_facileforms' && $this->p->app->getInput()->getString('ff_applic', '') != 'plg_facileforms') {
            /* translatables */
            if (isset($this->rootMdata['title_translation' . $this->language_tag]) && $this->rootMdata['title_translation' . $this->language_tag] != '') {
                $this->rootMdata['title'] = $this->rootMdata['title_translation' . $this->language_tag];
                $this->p->app->getDocument()->setTitle($this->rootMdata['title']);
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

        mt_srand();
        $this->flashUploadTicket = md5(strtotime('now') . mt_rand(0, mt_getrandmax()));
        $this->cancelImagePath = Uri::root(true) . '/media/breezingforms/themes/cancel.png';
        $this->uploadImagePath = Uri::root(true) . '/media/breezingforms/themes/upload.png';
        if (is_file(JPATH_SITE . '/media/breezingforms/themes/' . $this->rootMdata['theme'] . '/img/cancel.png')) {
            $this->cancelImagePath = Uri::root(true) . '/media/breezingforms/themes/' . $this->rootMdata['theme'] . '/img/cancel.png';
        }
        if (is_file(JPATH_SITE . '/media/breezingforms/themes/' . $this->rootMdata['theme'] . '/img/upload.png')) {
            $this->uploadImagePath = Uri::root(true) . '/media/breezingforms/themes/' . $this->rootMdata['theme'] . '/img/upload.png';
        }
    }

    public function process(&$dataObject, $parent = null, $parentPage = null, $index = 0, $childrenLength = 0)
    {
        $mdata = null;

        if (isset($dataObject['attributes']) && isset($dataObject['properties'])) {
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

            $mdata = $dataObject['properties'];

            if ($mdata['type'] == 'page') {
                $parentPage = $mdata;
                if ($parentPage['pageNumber'] > 1) {
                    echo '</div><!-- bfPage end -->' . "\n"; // closing previous pages
                }

                $display = ' style="display:none;"';
                if ($this->p->app->getInput()->getInt('ff_form_submitted', 0) == 0 && $this->p->app->getInput()->getInt('ff_page', 1) == $parentPage['pageNumber']) {
                    $display = '';
                } elseif ($this->p->app->getInput()->getInt('ff_form_submitted', 0) == 1 && $this->rootMdata['lastPageThankYou'] && $parentPage['pageNumber'] == count($this->dataObject['children'])) {
                    $display = '';
                } elseif ($this->p->app->getInput()->getInt('ff_form_submitted', 0) == 1 && false == $this->rootMdata['lastPageThankYou'] && $parentPage['pageNumber'] == 1) {
                    $display = '';
                }

                echo '<div id="bfPage' . $parentPage['pageNumber'] . '" class="bfPage"' . $display . '>' . "\n"; // opening current page

                /* translatables */
                if (isset($mdata['pageIntro_translation' . $this->language_tag]) && $mdata['pageIntro_translation' . $this->language_tag] != '') {
                    $mdata['pageIntro'] = $mdata['pageIntro_translation' . $this->language_tag];
                }
                /* translatables end */

                if (trim($mdata['pageIntro']) != '') {
                    echo '<section class="bfPageIntro' . $this->fadingClass . '">' . "\n";

                    $regex = '/{loadposition\s+(.*?)}/i';
                    $introtext = $mdata['pageIntro'];

                    preg_match_all($regex, $introtext, $matches, PREG_SET_ORDER);

                        $document = $this->p->app->getDocument();
                        $renderer = $document->loadRenderer('modules');
                        $options = array('style' => 'xhtml');

                    foreach ($matches as $match) {
                        $matcheslist = explode(',', $match[1]);
                        $position = trim($matcheslist[0]);
                        $output = $renderer->render($position, $options, null);
                        $introtext = preg_replace("|$match[0]|", addcslashes($output, '\\'), $introtext, 1);
                    }

                    echo $introtext . "\n";

                    echo '</section>' . "\n";
                }

                if (!$this->useErrorAlerts) {
                    echo '<span class="bfErrorMessage" style="display:none"></span>' . "\n";
                }
            } elseif ($mdata['type'] == 'section') {
                if (isset($dataObject['properties']['name']) && isset($mdata['off']) && $mdata['off']) {
                    echo $this->quickModeDeactivationScriptBuilder()->section($dataObject['properties']['name']);
                }

                /* translatables */
                if (isset($mdata['title_translation' . $this->language_tag]) && $mdata['title_translation' . $this->language_tag] != '') {
                    $mdata['title'] = $mdata['title_translation' . $this->language_tag];
                }
                /* translatables end */

                if ($mdata['bfType'] == 'section') {
                    echo '<div class="bfFieldset-wrapper ' . $wrapper . ' bfClearfix"><div class="bfFieldset-tl"><div class="bfFieldset-tr"><div class="bfFieldset-t"></div></div></div><div class="bfFieldset-l"><div class="bfFieldset-r"><div class="bfFieldset-m bfClearfix"><fieldset' . (isset($mdata['off']) && $mdata['off'] ? ' style="display:none" ' : '') . '' . (isset($mdata['off']) && $mdata['off'] ? '' : $class) . '' . (isset($dataObject['properties']['name']) && $dataObject['properties']['name'] != "" ? ' id="' . $dataObject['properties']['name'] . '"' : '') . '>' . "\n";
                    if (trim($mdata['title']) != '') {
                        echo '<legend><span class="bfLegend-l"><span class="bfLegend-r"><span class="bfLegend-m">' . htmlentities(trim($mdata['title']), ENT_QUOTES, 'UTF-8') . '</span></span></span></legend>' . "\n";
                    }
                } elseif ($mdata['bfType'] == 'normal') {
                    if (isset($dataObject['properties']['name']) && $dataObject['properties']['name'] != '') {
                        echo '<div ' . (isset($mdata['off']) && $mdata['off'] ? 'style="display:none" ' : '') . 'class="bfNoSection"' . (isset($dataObject['properties']['name']) && $dataObject['properties']['name'] != "" ? ' id="' . $dataObject['properties']['name'] . '"' : '') . '>' . "\n";
                    }
                }

                /* translatables */
                if (isset($mdata['description_translation' . $this->language_tag]) && $mdata['description_translation' . $this->language_tag] != '') {
                    $mdata['description'] = $mdata['description_translation' . $this->language_tag];
                }
                /* translatables end */

                if (trim($mdata['description']) != '') {
                    echo '<section class="bfSectionDescription">' . "\n";

                    $regex = '/{loadposition\s+(.*?)}/i';
                    $introtext = $mdata['description'];

                    preg_match_all($regex, $introtext, $matches, PREG_SET_ORDER);

                    $document = $this->p->app->getDocument();
                    $renderer = $document->loadRenderer('modules');
                    $options = array('style' => 'xhtml');

                    foreach ($matches as $match) {
                        $matcheslist = explode(',', $match[1]);
                        $position = trim($matcheslist[0]);
                        $output = $renderer->render($position, $options, null);
                        $introtext = preg_replace("|$match[0]|", addcslashes($output, '\\'), $introtext, 1);
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
                        if (isset($mdata[ 'label_translation' . $this->language_tag ]) && $mdata[ 'label_translation' . $this->language_tag ] != '') {
                            $mdata['label'] = $mdata[ 'label_translation' . $this->language_tag ];
                        }
                        if (isset($mdata[ 'hint_translation' . $this->language_tag ]) && $mdata[ 'hint_translation' . $this->language_tag ] != '') {
                            $mdata['hint'] = $mdata[ 'hint_translation' . $this->language_tag ];
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
                             $mdata['bfType'] == 'bfCheckboxGroup' ||
                             $mdata['bfType'] == 'bfCalendar' ||
                             $mdata['bfType'] == 'bfNumberInput' ||
                             $mdata['bfType'] == 'bfCalendarResponsive' ||
                             $mdata['bfType'] == 'bfSelect' ||
                             $mdata['bfType'] == 'bfRadioGroup' ||
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
                            $required = '<span class="bfRequired">*</span> ' . "\n";
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

                $mdata = QuickModeSubmittedValueHydrator::hydrate($this->p, $mdata, $this->language_tag);

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
                        $this->renderCaptchaField($mdata);
                        break;

                    case 'bfCalendar':
                        $this->renderCalendarField($mdata);
                        break;

                    case 'bfCalendarResponsive':
                        $this->renderCalendarResponsiveField($mdata);
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

                    case 'bfSofortueberweisung':
                        $this->renderSofortueberweisungField($mdata, $tabIndex, $onblur, $onchange, $onfocus, $onselect, $readonly);
                        break;
                }

                if (isset($mdata['bfName']) && isset($mdata['off']) && $mdata['off']) {
                    echo $this->quickModeDeactivationScriptBuilder()->field($mdata['bfName']);
                }

                if ($mdata['bfType'] == 'bfFile') {
                    echo '<span id="ff_elem' . $mdata['dbId'] . '_files"></span>';
                }

                echo $flashUploader;

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
        if (isset($dataObject['properties']) && $dataObject['properties']['type'] == 'section' && $dataObject['properties']['displayType'] == 'inline') {
            echo '<div class="bfClearfix">' . "\n";
        }

        if (isset($dataObject['children']) && count($dataObject['children']) != 0) {
            $childrenAmount = count($dataObject['children']);
            for ($i = 0; $i < $childrenAmount; $i++) {
                $this->process($dataObject['children'][$i], $mdata, $parentPage, $i, $childrenAmount);
            }
        }

        if (isset($dataObject['properties']) && $dataObject['properties']['type'] == 'section' && $dataObject['properties']['displayType'] == 'inline') {
            echo '</div>' . "\n";
        }

        if (isset($dataObject['properties']) && $dataObject['properties']['type'] == 'section' && $dataObject['properties']['bfType'] == 'section') {
            echo '</fieldset></div></div></div><div class="bfFieldset-bl"><div class="bfFieldset-br"><div class="bfFieldset-b"></div></div></div></div><!-- bfFieldset-wrapper end -->' . "\n";
        } elseif (isset($dataObject['properties']) && $dataObject['properties']['type'] == 'section' && $dataObject['properties']['bfType'] == 'normal') {
            if (isset($dataObject['properties']['name']) && $dataObject['properties']['name'] != '') {
                echo '</div>' . "\n";
            }
        } elseif (isset($dataObject['properties']) && $dataObject['properties']['type'] == 'page') {
            $isLastPage = false;
            if ($this->rootMdata['lastPageThankYou'] && $dataObject['properties']['pageNumber'] == count($this->dataObject['children']) && count($this->dataObject['children']) > 1) {
                $isLastPage = true;
            }

            if (!$isLastPage) {
                $last = 0;
                if ($this->rootMdata['lastPageThankYou']) {
                    $last = 1;
                }

                if ($this->rootMdata['pagingInclude'] && $dataObject['properties']['pageNumber'] > 1) {
                    /* translatables */
                    if (isset($this->rootMdata['pagingPrevLabel_translation' . $this->language_tag]) && $this->rootMdata['pagingPrevLabel_translation' . $this->language_tag] != '') {
                        $this->rootMdata['pagingPrevLabel'] = $this->rootMdata['pagingPrevLabel_translation' . $this->language_tag];
                    }
                    /* translatables end */
                    echo '<button type="button" class="btn btn-primary bfPrevButton button' . $this->fadingClass . '" type="submit" onclick="' . $this->quickModePagingActionBuilder()->previous() . '" value="' . htmlentities(trim($this->rootMdata['pagingPrevLabel']), ENT_QUOTES, 'UTF-8') . '"><span>' . htmlentities(trim($this->rootMdata['pagingPrevLabel']), ENT_QUOTES, 'UTF-8') . '</span></button>' . "\n";
                }

                if ($this->rootMdata['pagingInclude'] && $dataObject['properties']['pageNumber'] < count($this->dataObject['children']) - $last) {
                    /* translatables */
                    if (isset($this->rootMdata['pagingNextLabel_translation' . $this->language_tag]) && $this->rootMdata['pagingNextLabel_translation' . $this->language_tag] != '') {
                        $this->rootMdata['pagingNextLabel'] = $this->rootMdata['pagingNextLabel_translation' . $this->language_tag];
                    }
                    /* translatables end */
                    echo '<button type="button" class="btn btn-primary bfNextButton button' . $this->fadingClass . '" type="submit" onclick="' . $this->quickModePagingActionBuilder()->next() . '" value="' . htmlentities(trim($this->rootMdata['pagingNextLabel']), ENT_QUOTES, 'UTF-8') . '"><span>' . htmlentities(trim($this->rootMdata['pagingNextLabel']), ENT_QUOTES, 'UTF-8') . '</span></button>' . "\n";
                }

                $callSubmit = $this->quickModeSubmitActionBuilder()->build(false, $this->hasFlashUpload);
                if ($this->rootMdata['submitInclude'] && $dataObject['properties']['pageNumber'] + 1 > count($this->dataObject['children']) - $last) {
                    /* translatables */
                    if (isset($this->rootMdata['submitLabel_translation' . $this->language_tag]) && $this->rootMdata['submitLabel_translation' . $this->language_tag] != '') {
                        $this->rootMdata['submitLabel'] = $this->rootMdata['submitLabel_translation' . $this->language_tag];
                    }
                    /* translatables end */
                    echo '<button type="button" id="bfSubmitButton" class="btn btn-primary bfSubmitButton button' . $this->fadingClass . '" onclick="if(typeof bf_htmltextareainit != \'undefined\'){ bf_htmltextareainit() }if(document.getElementById(\'bfPaymentMethod\')){document.getElementById(\'bfPaymentMethod\').value=\'\';};' . $callSubmit . ';" value="' . htmlentities(trim($this->rootMdata['submitLabel']), ENT_QUOTES, 'UTF-8') . '"><span>' . htmlentities(trim($this->rootMdata['submitLabel']), ENT_QUOTES, 'UTF-8') . '</span></button>' . "\n";
                }

                if ($this->rootMdata['cancelInclude'] && $dataObject['properties']['pageNumber'] + 1 > count($this->dataObject['children']) - $last) {
                    /* translatables */
                    if (isset($this->rootMdata['cancelLabel_translation' . $this->language_tag]) && $this->rootMdata['cancelLabel_translation' . $this->language_tag] != '') {
                        $this->rootMdata['cancelLabel'] = $this->rootMdata['cancelLabel_translation' . $this->language_tag];
                    }
                    /* translatables end */
                    echo '<button class="btn btn-primary bfCancelButton button' . $this->fadingClass . '" type="submit" onclick="' . $this->quickModePagingActionBuilder()->cancel() . '"  value="' . htmlentities(trim($this->rootMdata['cancelLabel']), ENT_QUOTES, 'UTF-8') . '"><span>' . htmlentities(trim($this->rootMdata['cancelLabel']), ENT_QUOTES, 'UTF-8') . '</span></button>' . "\n";
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

    private function renderTextfieldField(array $mdata, string $tabIndex, string $onclick, string $onblur, string $onchange, string $onfocus, string $onselect, string $readonly): void
    {
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

    private function renderNumberInputField(array $mdata, string $tabIndex, string $onclick, string $onblur, string $onchange, string $onfocus, string $onselect, string $readonly): void
    {
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

    private function renderTextareaField(array $mdata, string $tabIndex, string $onclick, string $onblur, string $onchange, string $onfocus, string $onselect, string $readonly): void
    {
        /* translatables */
        if (isset($mdata['placeholder_translation' . $this->language_tag]) && $mdata['placeholder_translation' . $this->language_tag] != '') {
            $mdata['placeholder'] = $mdata['placeholder_translation' . $this->language_tag];
        }
        if (isset($mdata['value_translation' . $this->language_tag]) && $mdata['value_translation' . $this->language_tag] != '') {
            $mdata['value'] = $mdata['value_translation' . $this->language_tag];
        }
        /* translatables end */

        if (isset($mdata['is_html']) && $mdata['is_html']) {
            echo '<div style="display: inline-block; vertical-align: top; width: ' . strip_tags($mdata['width']) . ';">';
            $editor = Editor::getInstance($this->p->app->get('editor'));
            $this->htmltextareas[] = 'ff_nm_' . $mdata['bfName'] . '[]';
            $this->htmltextareasDbIds[] = 'ff_elem' . $mdata['dbId'];
            echo $editor->display('ff_nm_' . $mdata['bfName'] . '[]', htmlentities(trim($mdata['value']), ENT_QUOTES, 'UTF-8'), strip_tags($mdata['width']), strip_tags($mdata['height']), '75', '20', true, 'ff_elem' . $mdata['dbId']);
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

    private function renderChoiceGroupField(string $type, array $mdata, string $tabIndex, string $onclick, string $onblur, string $onchange, string $onfocus, string $onselect, string $readonly): void
    {
        /* translatables */
        if (isset($mdata['group_translation' . $this->language_tag]) && $mdata['group_translation' . $this->language_tag] != '') {
            $mdata['group'] = $mdata['group_translation' . $this->language_tag];
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
            $readonly !== ''
        );
    }


    private function renderCheckboxField(array $mdata, string $tabIndex, string $onclick, string $onblur, string $onchange, string $onfocus, string $onselect, string $readonly): void
    {
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

    private function renderSelectField(array $mdata, string $tabIndex, string $onclick, string $onblur, string $onchange, string $onfocus, string $onselect, string $readonly): void
    {
        /* translatables */
        if (isset($mdata['list_translation' . $this->language_tag]) && $mdata['list_translation' . $this->language_tag] != '') {
            $mdata['list'] = $mdata['list_translation' . $this->language_tag];
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

    private function renderSubmitButtonField(array $mdata, string $tabIndex, string $onblur, string $onchange, string $onfocus, string $onselect, string $readonly): void
    {
        /* translatables */
        if (isset($mdata['src_translation' . $this->language_tag]) && $mdata['src_translation' . $this->language_tag] != '') {
            $mdata['src'] = $mdata['src_translation' . $this->language_tag];
        }
        if (isset($mdata['value_translation' . $this->language_tag]) && $mdata['value_translation' . $this->language_tag] != '') {
            $mdata['value'] = $mdata['value_translation' . $this->language_tag];
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

    private function renderSummarizeField(array $mdata): void
    {
        /* translatables */
        if (isset($mdata['emptyMessage_translation' . $this->language_tag]) && $mdata['emptyMessage_translation' . $this->language_tag] != '') {
            $mdata['emptyMessage'] = $mdata['emptyMessage_translation' . $this->language_tag];
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

    private function renderReCaptchaField(array $mdata): void
    {
        echo $this->quickModeReCaptchaFieldBuilder()->build(
            $mdata,
            $this->p->app,
            $this->hasFlashUpload,
            true,
            true
        );
    }

    private function renderCaptchaField(array $mdata): void
    {
        $captcha_url = $this->quickModeCaptchaUrlBuilder()->build(
            Uri::root(true),
            $this->p->app->isClient('administrator')
        );

        echo '<span class="bfCaptcha">' . "\n";

        echo $this->quickModeCaptchaMarkupBuilder()->buildImage(
            isset($mdata['width']) && intval($mdata['width']) > 0 ? ' width="' . intval($mdata['width']) . '"' : 'width="230"',
            'ff_capimgValue',
            'ff_capimg',
            $captcha_url
        );

        echo '<br/>';
        echo $this->quickModeCaptchaMarkupBuilder()->buildResponseInput(
            isset($mdata['width']) && intval($mdata['width']) > 0 && (intval($mdata['width']) - 45 >= 230) ? ' style="width:' . (intval($mdata['width']) - 45) . 'px;"' : '',
            'ff_elem',
            '',
            true
        );
        echo '<a href="#" class="ff_elem" onclick="' . $this->quickModeCaptchaReloadScriptBuilder()->build($captcha_url) . '"><img alt="captcha" src="' . Uri::root(true) . '/media/com_breezingformsng/images/site/captcha/refresh-captcha.png" /></a>' . "\n";
        echo '</span>' . "\n";
    }

    private function renderCalendarField(array $mdata): void
    {
        /* translatables */
        if (isset($mdata['value_translation' . $this->language_tag]) && $mdata['value_translation' . $this->language_tag] != '') {
            $mdata['value'] = $mdata['value_translation' . $this->language_tag];
        }
        if (isset($mdata['format_translation' . $this->language_tag]) && $mdata['format_translation' . $this->language_tag] != '') {
            $mdata['format'] = $mdata['format_translation' . $this->language_tag];
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

        echo HTMLHelper::_('calendar', $left, "ff_nm_" . $mdata['bfName'] . "[]", "ff_elem" . $mdata['dbId'], $mdata['format'], $calAttr);
    }

    private function renderCalendarResponsiveField(array $mdata): void
    {
        /* translatables */
        if (isset($mdata['value_translation' . $this->language_tag]) && $mdata['value_translation' . $this->language_tag] != '') {
            $mdata['value'] = $mdata['value_translation' . $this->language_tag];
        }
        if (isset($mdata['format_translation' . $this->language_tag]) && $mdata['format_translation' . $this->language_tag] != '') {
            $mdata['format'] = $mdata['format_translation' . $this->language_tag];
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
            $size
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
            $this->p->app->getDocument()->getWebAssetManager()->addInlineScript(
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

    private function renderSignatureField(array $mdata): void
    {
        RuntimeAssetLoader::script($this->p->app, Uri::root(true) . '/components/com_breezingformsng/libraries/js/signature.js');
        RuntimeAssetLoader::script($this->p->app, Uri::root(true) . '/media/com_breezingformsng/js/site/quickmode-signature.js');
        $this->p->app->getDocument()->getWebAssetManager()->addInlineScript(
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

    private function renderStripeField(array $mdata, string $tabIndex, string $onblur, string $onchange, string $onfocus, string $onselect, string $readonly): void
    {
        /* translatables */
        if (isset($mdata['image_translation' . $this->language_tag]) && $mdata['image_translation' . $this->language_tag] != '') {
            $mdata['image'] = $mdata['image_translation' . $this->language_tag];
        }
        /* translatables end */

        echo QuickModePaymentButtonBuilder::build(
            'Stripe',
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
            (string) ($mdata['actionFunctionName'] ?? '')
        );
    }

    private function renderPayPalField(array $mdata, string $tabIndex, string $onblur, string $onchange, string $onfocus, string $onselect, string $readonly): void
    {
        /* translatables */
        if (isset($mdata['image_translation' . $this->language_tag]) && $mdata['image_translation' . $this->language_tag] != '') {
            $mdata['image'] = $mdata['image_translation' . $this->language_tag];
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
            (string) ($mdata['actionFunctionName'] ?? '')
        );
    }

    private function renderSofortueberweisungField(array $mdata, string $tabIndex, string $onblur, string $onchange, string $onfocus, string $onselect, string $readonly): void
    {
        /* translatables */
        if (isset($mdata['image_translation' . $this->language_tag]) && $mdata['image_translation' . $this->language_tag] != '') {
            $mdata['image'] = $mdata['image_translation' . $this->language_tag];
        }
        /* translatables end */

        echo QuickModePaymentButtonBuilder::build(
            'Sofortueberweisung',
            (string) $mdata['bfName'],
            (int) $mdata['dbId'],
            (string) $mdata['image'],
            'Sofortueberweisung',
            '',
            $tabIndex,
            $onblur,
            $onchange,
            $onfocus,
            $onselect,
            $readonly,
            isset($mdata['actionClick']) && $mdata['actionClick'] == 1,
            (string) ($mdata['actionFunctionName'] ?? '')
        );
    }

    private function renderFileField(array $mdata, string $tabIndex, string $onclick, string $onblur, string $onchange, string $onfocus, string $onselect, string $readonly): string
    {
                    $flashUploader = '';
        if (( isset($mdata['flashUploader']) && $mdata['flashUploader'] ) || ( isset($mdata['html5']) && $mdata['html5'] )) {
            $base = (new QuickModeUploadBasePathBuilder())->build(Uri::base());

            echo '<input type="hidden" id="flashUpload' . $mdata['bfName'] . '" name="flashUpload' . $mdata['bfName'] . '" value="bfFlashFileQueue' . $mdata['dbId'] . '"/>' . "\n";
            $this->hasFlashUpload = true;
            $uploadOptions = $this->quickModeUploadOptionsBuilder()->build($mdata);
            $exts = $uploadOptions['extensions'];
            $bytes = $uploadOptions['maxFileSize'];
            $multiSelection = $uploadOptions['multiSelection'];
            $runtimes = $uploadOptions['runtimes'];
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
" . QuickModeUploadThumbnailScriptBuilder::build($base, chr(10)) . "
                                                        JQuery(document).ready(
                                                            function() {
" . (new QuickModeUploadConfigurationBuilder())->build(
    (int) $this->p->form,
    (string) $mdata['bfName'],
    (string) $this->flashUploadTicket,
    (string) $base,
    (int) $mdata['dbId'],
    (string) $runtimes,
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
    ' ' . Text::_('COM_BREEZINGFORMSNG_FLASH_UPLOADER_TOO_LARGE'),
    ' ' . Text::_('COM_BREEZINGFORMSNG_FILE_EXTENSION_NOT_ALLOWED')
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
                (int) $mdata['dbId']
            );
        } else {
            echo QuickModeFileInputBuilder::build(
                $tabIndex . $onclick . $onblur . $onchange . $onfocus . $onselect . $readonly,
                'file',
                (string) $mdata['bfName'],
                (int) $mdata['dbId']
            );
        }
        if ($mdata['attachToAdminMail']) {
            echo '<input type="hidden" name="attachToAdminMail[' . $mdata['bfName'] . ']" value="true"/>' . "\n";
        }
        if ($mdata['attachToUserMail']) {
            echo '<input type="hidden" name="attachToUserMail[' . $mdata['bfName'] . ']" value="true"/>' . "\n";
        }

                    return $flashUploader;
    }

    public function render()
    {

        $this->process($this->dataObject);
        echo '</div>' . "\n"; // closing last page

        $this->headers();

        if ($this->hasResponsiveDatePicker) {
            RuntimeAssetLoader::script($this->p->app, Uri::root(true) . '/components/com_breezingformsng/libraries/jquery/pickadate/picker.js');
            RuntimeAssetLoader::script($this->p->app, Uri::root(true) . '/components/com_breezingformsng/libraries/jquery/pickadate/picker.date.js');

            $lang = $this->p->app->getLanguage()->getTag();
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
            $tickets[$this->flashUploadTicket] = array(); // stores file info for later processing
            $this->p->app->getSession()->set('bfFlashUploadTickets', $tickets);
            echo '<input type="hidden" name="bfFlashUploadTicket" value="' . $this->flashUploadTicket . '"/>' . "\n";
            RuntimeAssetLoader::script($this->p->app, Uri::root(true) . '/components/com_breezingformsng/libraries/jquery/center.js');
            RuntimeAssetLoader::script($this->p->app, Uri::root(true) . '/media/com_breezingformsng/js/site/quickmode-flash-upload.js');
            echo "<div style=\"visibility:hidden;\" id=\"bfFileQueue\"></div>";
            echo "<div style=\"visibility:hidden;display:none;\" id=\"bfSubmitMessage\">" . Text::_('COM_BREEZINGFORMSNG_SUBMIT_MESSAGE') . "</div>";
        }
        echo '<noscript>' . Text::_('COM_BREEZINGFORMSNG_JAVASCRIPT_REQUIRED') . '</noscript>' . "\n";
        $this->p->app->getDocument()->getWebAssetManager()->addInlineScript('//-->');
    }

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

        return (new QuickModeToggleFieldsParser())->parse((string) $code);
    }
}
