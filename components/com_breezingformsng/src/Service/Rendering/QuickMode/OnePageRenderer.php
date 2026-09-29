<?php

/**
 * BreezingForms NG - A Joomla Forms Application
 * @version       5.0
 * @package       BreezingForms
 * @copyright Copyright (C) 2008-2020 by Markus Bopp
 * @copyright Copyright (C) 2024-2026 by XDA+GIL- EVH
 * @license       Released under the terms of the GNU General Public License
 * */

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Site\Service\Rendering\QuickMode;

\defined('_JEXEC') or die;

use HTML_facileFormsProcessor;


use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Editor\Editor;
use Joomla\CMS\Uri\Uri;
use Vcmb\Component\BreezingformsNG\Site\Service\Runtime\RuntimeAssetLoader;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Component\ComponentHelper;

class OnePageRenderer
{
    use HiddenFieldTrait;
    use BootstrapStyleFieldTrait;
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
    private $htmltextareas = array();
    private $htmltextareasDbIds = array();
    private $language_tag = '';
    private $hasResponsiveDatePicker = false;
    private $bsClasses = array();
    private ?QuickModeTextFieldStrategy $quickModeTextFieldStrategyService = null;
    private ?QuickModeTextareaStrategy $quickModeTextareaStrategyService = null;
    private ?QuickModeCheckboxStrategy $quickModeCheckboxStrategyService = null;
    private ?QuickModeSelectBuilder $quickModeSelectBuilderService = null;
    private ?QuickModeMaxLengthCounterBuilder $quickModeMaxLengthCounterBuilderService = null;
    private ?QuickModeGroupOptionBuilder $quickModeGroupOptionBuilderService = null;
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

    public function bsClass($key)
    {

        return $this->bsClasses[5][$key];
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

    private function quickModeSelectBuilder(): QuickModeSelectBuilder
    {
        return $this->quickModeSelectBuilderService ??= new QuickModeSelectBuilder();
    }

    private function quickModeMaxLengthCounterBuilder(): QuickModeMaxLengthCounterBuilder
    {
        return $this->quickModeMaxLengthCounterBuilderService ??= new QuickModeMaxLengthCounterBuilder();
    }

    private function quickModeGroupOptionBuilder(): QuickModeGroupOptionBuilder
    {
        return $this->quickModeGroupOptionBuilderService ??= new QuickModeGroupOptionBuilder();
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

    public static function getEditorContent($editor)
    {
        return QuickModeEditorValueBuilder::build($editor);
    }

    public function __construct(HTML_facileFormsProcessor $p)
    {
        $this->p = $p;
        $default = ComponentHelper::getParams('com_languages')->get('site');
        $this->language_tag = $this->p->app->getLanguage()->getTag() != $default ? $this->p->app->getLanguage()->getTag() : 'zz-ZZ';

        $this->p->app->getDocument()->getWebAssetManager()->addInlineScript('<!--');

        $this->dataObject = json_decode(bf_b64dec($this->p->formrow->template_code), true);

        $this->rootMdata = $this->dataObject['properties'];

        $this->bsClasses[5] = QuickModeBootstrapClassMapBuilder::build();

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

        $this->cancelImagePath = Uri::root(true) . '/components/com_breezingformsng/themes/quickmode-bootstrap' . '5' . '/cancel.png';
        if (isset($this->rootMdata['themebootstrap']) && is_file(JPATH_SITE . '/media/breezingforms/themes-bootstrap' . '5' . '/' . $this->rootMdata['themebootstrap'] . '/images/cancel.png')) {
            $this->cancelImagePath = Uri::root(true) . '/media/breezingforms/themes-bootstrap' . '5' . '/' . $this->rootMdata['themebootstrap'] . '/images/cancel.png';
        }
        if (isset($this->rootMdata['themebootstrap']) && is_file(JPATH_SITE . '/media/breezingforms/themes-bootstrap' . '5' . '/' . $this->rootMdata['themebootstrap'] . '/images/upload.png')) {
        }
    }

    public function headers()
    {

        if ($this->hasFlashUpload) {
            RuntimeAssetLoader::script($this->p->app, Uri::root(true) . '/components/com_breezingformsng/libraries/jquery/plupload/moxie.js');
            RuntimeAssetLoader::script($this->p->app, Uri::root(true) . '/components/com_breezingformsng/libraries/jquery/plupload/plupload.js');
            RuntimeAssetLoader::script($this->p->app, Uri::root(true) . '/media/com_breezingformsng/js/site/quickmode-plupload-compat.js');
        }

        RuntimeAssetLoader::style($this->p->app, Uri::root(true) . '/media/com_breezingformsng/css/site/quickmode-runtime.css');

        $this->p->app->getDocument()->getWebAssetManager()->useScript('jquery');
        HTMLHelper::_('bootstrap.tooltip', '.hasTooltip');
        RuntimeAssetLoader::script($this->p->app, Uri::root(true) . '/media/com_breezingformsng/js/site/quickmode-tooltip-init.js');

        $jQuery = 'var JQuery = jQuery;' . "\n";

        RuntimeAssetLoader::script($this->p->app, Uri::root(true) . '/components/com_breezingformsng/libraries/jquery/scrollto.js');

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

        $has_last_page = 'false';
        if ($this->rootMdata['lastPageThankYou']) {
            $has_last_page = 'true';

            // loading remodal
            RuntimeAssetLoader::style($this->p->app, Uri::root(true) . '/components/com_breezingformsng/libraries/jquery/remodal/remodal.css');
            RuntimeAssetLoader::style($this->p->app, Uri::root(true) . '/components/com_breezingformsng/libraries/jquery/remodal/remodal-default-theme.css');
            RuntimeAssetLoader::script($this->p->app, Uri::root(true) . '/components/com_breezingformsng/libraries/jquery/remodal/remodal.min.js');
            $this->p->app->getDocument()->getWebAssetManager()->addInlineScript(
                "\n"
                . (new QuickModeOnePageCallbackScriptBuilder())->closeRemodal(
                    (string) json_encode(Uri::getInstance()->toString()),
                    "\n"
                )
            );
        }

        RuntimeAssetLoader::style($this->p->app, Uri::root(true) . '/components/com_breezingformsng/libraries/jquery/ladda/ladda-themeless.min.css');
        RuntimeAssetLoader::script($this->p->app, Uri::root(true) . '/components/com_breezingformsng/libraries/jquery/ladda/spin.min.js');
        RuntimeAssetLoader::script($this->p->app, Uri::root(true) . '/components/com_breezingformsng/libraries/jquery/ladda/ladda.min.js');
        RuntimeAssetLoader::script($this->p->app, Uri::root(true) . '/components/com_breezingformsng/libraries/jquery/ladda/ladda.jq.min.js');

        $this->p->app->getDocument()->getWebAssetManager()->addInlineScript(
            $jQuery . '
			var inlineErrorElements = new Array();
			var bfSummarizers = new Array();
			var bfDeactivateField = new Array();
			var bfDeactivateSection = new Array();
			var bfCharsLeftLabel = ' . json_encode(Text::_('COM_BREEZINGFORMSNG_CHARS_LEFT')) . ';
                        var ladda_button = null;
                        var orig_submit_button = null;

			' . $toggleCode . '

                        JQuery(document).ready(function(){
                            // due to a glitch in Ladda, we need to clone the submit buttons 2 times and reload ladda after submit errors
                            orig_submit_button = JQuery( "#bfSubmitButton" ).clone(true);
                            JQuery(orig_submit_button).attr("id","bfSubmitButtonTmp");
                            ladda_button = JQuery( "#bfSubmitButton" ).ladda();
                            Ladda.bind("#bfSubmitButton");
                        });

                        ' . QuickModeAjaxSubmitScriptBuilder::build(
                            (int) $this->p->form_id,
                            $has_last_page === 'true',
                            (string) json_encode(Text::_('COM_BREEZINGFORMSNG_ERROR')),
                            (string) json_encode(Text::_('COM_BREEZINGFORMSNG_PROCESS_SUBMITSUCCESS')),
                            (string) json_encode(Uri::getInstance()->toString()),
                            "\n"
                        ) . '

                        ' . (new QuickModeOnePageCallbackScriptBuilder())->restoreSubmitButton("\n") . '

                        ' . (new QuickModeSubmitValidationBuilder())->build("\n") . '

                        ' . (new QuickModeNextPageValidationBuilder())->build("\n") . '

                        ' . (new QuickModePreviousPageValidationBuilder())->build("\n") . '

'
        );

        RuntimeAssetLoader::script($this->p->app, Uri::root(true) . '/media/com_breezingformsng/js/site/quickmode-field-helpers-bootstrap.js');
        RuntimeAssetLoader::script($this->p->app, Uri::root(true) . '/media/com_breezingformsng/js/site/quickmode-deactivation.js');

        if ($this->fading || !$this->useErrorAlerts || $this->rollover) {
            if (!$this->useErrorAlerts) {
                $showDefaultErrors = $this->useDefaultErrors || !$this->useBalloonErrors;
                $this->p->app->getDocument()->getWebAssetManager()->addInlineScript(
                    QuickModeErrorRuntimeConfigBuilder::build($showDefaultErrors, true, "\n")
                );
                RuntimeAssetLoader::script($this->p->app, Uri::root(true) . '/media/com_breezingformsng/js/site/quickmode-error-alerts-bootstrap.js');
            }
            if ($this->fading) {
                $this->fadingClass = ' bfFadingClass';
                RuntimeAssetLoader::script($this->p->app, Uri::root(true) . '/media/com_breezingformsng/js/site/quickmode-fade.js');
            }

            if ($this->rollover && trim($this->rolloverColor) != '') {
                // removed in bootstrap
            }
        }
        RuntimeAssetLoader::script($this->p->app, Uri::root(true) . '/media/com_breezingformsng/js/site/quickmode-post-init-onepage.js');
        // loading system css

            RuntimeAssetLoader::style($this->p->app, Uri::root(true) . '/components/com_breezingformsng/themes/quickmode-bootstrap' . '5' . '/system.css');

            // loading theme
        if (isset($this->rootMdata['themebootstrap'])) {
            $vars = '';
            $themecss = '';
            $scriptjs = '';
            $scriptphp = '';

            $themecss_path = JPATH_SITE . '/media/breezingforms/themes-bootstrap' . '5' . '/' . $this->rootMdata['themebootstrap'] . '/theme.css';
            $vars_path = JPATH_SITE . '/media/breezingforms/themes-bootstrap' . '5' . '/' . $this->rootMdata['themebootstrap'] . '/vars.txt';
            $scriptjs_path = JPATH_SITE . '/media/breezingforms/themes-bootstrap' . '5' . '/' . $this->rootMdata['themebootstrap'] . '/script.js';
            $scriptphp_path = JPATH_SITE . '/media/breezingforms/themes-bootstrap' . '5' . '/' . $this->rootMdata['themebootstrap'] . '/script.php';

            if ($this->rootMdata['themebootstrap'] != '' && $this->rootMdata['themebootstrap'] != 'none' && file_exists($themecss_path)) {
                if (file_exists($vars_path)) {
                    $vars = file_get_contents($vars_path);
                }
                $themecss = file_get_contents($themecss_path);
                if (file_exists($scriptphp_path)) {
                    require_once($scriptphp_path);
                }
                if (file_exists($scriptjs_path)) {
                    $scriptjs = file_get_contents($scriptjs_path);
                }

                $vars = str_replace("\r", '', $vars);
                $vars = explode("\n", $vars);
                foreach ($vars as $var) {
                    if (trim($var)) {
                        $keyvalue = explode('=', $var);
                        if (count($keyvalue) == 2) {
                            $themecss = str_replace('{' . trim($keyvalue[0]) . '}', trim($keyvalue[1]), $themecss);
                        }
                    }
                }

                $manager = $this->p->app->getDocument()->getWebAssetManager();
                $manager->addInlineStyle($themecss);
                if ($scriptjs) {
                    $manager->addInlineScript($scriptjs);
                }
            }
        }
    }

    public function process(&$dataObject, $parent = null, $parentPage = null, $index = 0, $childrenLength = 0, $parentFull = null)
    {
        $mdata = null;

        if (isset($dataObject['attributes']) && isset($dataObject['properties'])) {
            HTMLHelper::_('bootstrap.tooltip', '.hasTooltip');

            $options = array('type' => 'normal', 'displayType' => 'breaks');
            if ($parent != null && $parent['type'] == 'section') {
                $options['type'] = $parent['bfType'];
                $options['displayType'] = $parent['displayType'];
            }
            $parentInline = false;
            $class = ' class="' . $this->fadingClass . '"';
            if ($options['displayType'] == 'inline') {
                $class = ' class="' . $this->fadingClass . '"';
                $parentInline = true;
            }

            //determine non-elements and reduce them from the children total to determine the right span sizes
            $reduce = 0;
            $parentFullChildrenLength = 0;

            if ($parentFull != null && isset($parentFull['children'])) {
                $parentFullChildrenLength = count($parentFull['children']);
                foreach ($parentFull['children'] as $child) {
                    if (!isset($child['properties']) || (isset($child['properties']) && isset($child['properties']['bfType']) && $child['properties']['bfType'] == 'bfHidden') || (isset($child['properties']) && $child['properties']['type'] != 'element' && $child['properties']['type'] != 'section')) {
                        $reduce++;
                    }
                    //if(isset($child['properties']) && isset($child['properties']['off']) && $child['properties']['off'] && $child['properties']['type'] == 'section'){
                    //    $reduce++;
                    //}
                }
            }

            $span = '';
            if (($parentFullChildrenLength - $reduce) > 0 && $parentInline) {
                switch (12 / ($parentFullChildrenLength - $reduce)) {
                    case 6:
                        $span = ' ' . $this->bsClass('span6');
                        break;
                    case 4:
                        $span = ' ' . $this->bsClass('span4');
                        break;
                    case 3:
                        $span = ' ' . $this->bsClass('span3');
                        break;
                    case 2.4:
                        $span = ' ' . $this->bsClass('span2');
                        break;
                    case 2:
                        $span = ' ' . $this->bsClass('span2');
                        break;
                }
            }

            $mdata = $dataObject['properties'];

            if ($mdata['type'] == 'page') {
                $parentPage = $mdata;
                if ($parentPage['pageNumber'] > 1) {
                    echo '</div><!-- bfPage end -->' . "\n"; // closing previous pages
                }

                $display = '';

                if ($parentPage['pageNumber'] > 1) {
                    $display = ' style="pointer-events:none;opacity:0.4"';
                }

                if ($this->p->app->getInput()->getInt('ff_page', 1) >= $parentPage['pageNumber'] && !($this->rootMdata['lastPageThankYou'] && $parentPage['pageNumber'] == count($this->dataObject['children']))) {
                    $display = ' style="pointer-events:auto;opacity:1.0"';
                } elseif ($this->p->app->getInput()->getInt('ff_page', 1) < $parentPage['pageNumber'] && !($this->rootMdata['lastPageThankYou'] && $parentPage['pageNumber'] == count($this->dataObject['children']))) {
                    $display = ' style="pointer-events:none;opacity:0.4"';
                } elseif ($this->rootMdata['lastPageThankYou'] && $parentPage['pageNumber'] == count($this->dataObject['children'])) {
                    $display = ' style="display:none;"';
                }

                echo '<div id="bfPage' . $parentPage['pageNumber'] . '" class="bfPage bfQuickModeOnePage"' . $display . '>' . "\n"; // opening current page

                /* translatables */
                if (isset($mdata['pageIntro_translation' . $this->language_tag]) && $mdata['pageIntro_translation' . $this->language_tag] != '') {
                    $mdata['pageIntro'] = $mdata['pageIntro_translation' . $this->language_tag];
                }
                /* translatables end */

                if (trim($mdata['pageIntro']) != '') {
                    echo '<div class="' . (isset($this->rootMdata['themebootstrapUseHeroUnit']) && $this->rootMdata['themebootstrapUseHeroUnit'] ? $this->bsClass('hero-unit') : '') . $this->fadingClass . '">' . "\n";

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

                    echo '</div>' . "\n";
                }

                if (!$this->useErrorAlerts) {
                    echo QuickModeErrorMessageMarkupBuilder::build(
                        $this->bsClass('alert'),
                        $this->bsClass('alert-error'),
                        "\n"
                    );
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

                $normal = false;

                if ($mdata['bfType'] == 'section') {
                    echo '<div' . (isset($mdata['off']) && $mdata['off'] ? ' style="display:none" ' : '') . '' . (isset($mdata['off']) && $mdata['off'] ? '' : ' class="' . $span . ' ' . $this->fadingClass . '"') . (isset($dataObject['properties']['name']) && $dataObject['properties']['name'] != "" ? ' id="' . $dataObject['properties']['name'] . '"' : '') . '>' . "\n";

                    if (trim($mdata['title']) != '') {
                        echo '<legend>' . htmlentities(trim($mdata['title']), ENT_QUOTES, 'UTF-8') . '</legend>' . "\n";
                    }

                    echo '<div>';
                } elseif ($mdata['bfType'] == 'normal') {
                    if (isset($dataObject['properties']['name']) && $dataObject['properties']['name'] != '') {
                        $normal = true;

                        echo '<section ' . (isset($mdata['off']) && $mdata['off'] ? 'style="display:none" ' : ' class="' . $span . ' ' . $this->fadingClass . '"') . (isset($dataObject['properties']['name']) && $dataObject['properties']['name'] != "" ? ' id="' . $dataObject['properties']['name'] . '"' : '') . '>' . "\n";
                        echo '<div>';
                    }
                }

                /* translatables */
                if (isset($mdata['description_translation' . $this->language_tag]) && $mdata['description_translation' . $this->language_tag] != '') {
                    $mdata['description'] = $mdata['description_translation' . $this->language_tag];
                }
                /* translatables end */

                if (trim($mdata['description']) != '') {
                    echo '<div class="bfSectionDescription mb-2' . (isset($this->rootMdata['themebootstrapUseWell']) && $this->rootMdata['themebootstrapUseWell'] ? ' ' . $this->bsClass('well') . ' ' . $this->bsClass('well-small') . '' : '') . '">' . "\n";

                    $regex = '/{loadposition\s+(.*?)}/i';
                    $introtext = $mdata['description'];

                    preg_match_all($regex, $introtext, $matches, PREG_SET_ORDER);

                    if ($matches) {
                        $document = $this->p->app->getDocument();
                        $renderer = $document->loadRenderer('modules');
                        $options = array('style' => 'xhtml');

                        foreach ($matches as $match) {
                            $matcheslist = explode(',', $match[1]);
                            $position = trim($matcheslist[0]);
                            $output = $renderer->render($position, $options, null);
                            $introtext = preg_replace("|$match[0]|", addcslashes($output, '\\'), $introtext, 1);
                        }
                    }

                    echo $introtext . "\n";
                    echo '</div>' . "\n";
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
                    echo '<div ' . (isset($mdata['off']) && $mdata['off'] ? 'style="display:none" ' : '') . 'class="bfElemWrap ' . $this->bsClass('control-group') . '' . $span . (isset($mdata['off']) && $mdata['off'] ? '' : $this->fadingClass) . '" id="bfElemWrap' . $mdata['dbId'] . '">' . "\n";
                }

                $label = '';
                if (!$mdata['hideLabel']) {
                    $badge = '';

                    if (isset($mdata['theme'])) {
                        $badge = str_replace('invisible_', '', trim($mdata['theme']));
                    }

                    if (!($mdata['bfType'] == 'bfReCaptcha' && isset($mdata['invisibleCaptcha']) && $mdata['invisibleCaptcha'] && $badge != 'inline')) {
                        $maxlengthCounter = '';
                        if ($mdata['bfType'] == 'bfTextarea' && isset($mdata['maxlength']) && $mdata['maxlength'] > 0 && isset($mdata['showMaxlengthCounter']) && $mdata['showMaxlengthCounter']) {
                            $maxlengthCounter = $this->quickModeMaxLengthCounterBuilder()->build((int) $mdata['dbId'], (int) $mdata['maxlength'], Text::_('COM_BREEZINGFORMSNG_CHARS_LEFT'));
                        }

                        /* translatables */
                        if (isset($mdata['label_translation' . $this->language_tag]) && $mdata['label_translation' . $this->language_tag] != '') {
                            $mdata['label'] = $mdata['label_translation' . $this->language_tag];
                        }
                        if (isset($mdata['hint_translation' . $this->language_tag]) && $mdata['hint_translation' . $this->language_tag] != '') {
                            $mdata['hint'] = $mdata['hint_translation' . $this->language_tag];
                        }
                        /* translatables end */

                        $tipOpen = '';
                        $tipClose = '';
                        $labelText = trim($mdata['label']) . str_replace("***", "\"", $maxlengthCounter);
                        if (trim($mdata['hint']) != '') {
                            if (isset($this->rootMdata['joomlaHint']) && $this->rootMdata['joomlaHint']) {
                                HTMLHelper::_('bootstrap.tooltip', '.hasTooltip');
                                $content = trim($mdata['hint']);
                                $tipOpen = '<span title="' . htmlspecialchars(trim($mdata['label']), ENT_QUOTES, 'UTF-8') . '::' . str_replace(
                                    array(
                                        "\n",
                                        "\r"
                                    ),
                                    array(
                                        "",
                                        ""
                                    ),
                                    htmlentities($content, ENT_QUOTES, 'UTF-8')
                                ) . '" class="editlinktip hasTooltip">';
                                $tipClose = '</span>';
                            } else {
                                $content = $this->quickModeHintContentResolver()->resolve(
                                    (string) $mdata['hint'],
                                    false
                                );
                                $tipOpen = '<span class="hasTooltip" title="' . HTMLHelper::tooltipText($content) . '">';
                                $tipClose = '</span>';
                            }
                        }

                        if ($tipOpen) {
                            $tipOpen = $tipOpen . '<i class="' . $this->bsClass('icon-question-sign') . '">&nbsp;</i> ';
                        }

                        $for = '';
                        if (
                            $mdata['bfType'] == 'bfTextfield' ||
                            $mdata['bfType'] == 'bfTextarea' ||
                            $mdata['bfType'] == 'bfCheckbox' ||
                            $mdata['bfType'] == 'bfNumberInput' ||
                            $mdata['bfType'] == 'bfCheckboxGroup' ||
                            $mdata['bfType'] == 'bfCalendar' ||
                            $mdata['bfType'] == 'bfCalendarResponsive' ||
                            $mdata['bfType'] == 'bfSelect' ||
                            $mdata['bfType'] == 'bfRadioGroup' ||
                            ($mdata['bfType'] == 'bfFile' && ((!isset($mdata['flashUploader']) && !isset($mdata['html5'])) || (isset($mdata['flashUploader']) && !$mdata['flashUploader']) && (isset($mdata['html5']) && !$mdata['html5'])))
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
                            $required = ' <i class="' . $this->bsClass('icon-asterisk') . '"></i> ' . "\n";
                        }
                        $label = '<label class="' . $this->bsClass('control-label') . '' . (isset($this->rootMdata['themebootstrapLabelTop']) && $this->rootMdata['themebootstrapLabelTop'] ? ' bfLabelBlock' : '') . '" id="bfLabel' . $mdata['dbId'] . '" ' . $for . '>' . $tipOpen . str_replace("***", "\"", $labelText) . $tipClose . $required . '</label>' . "\n";
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

                /* translatables */
                if (isset($mdata['value_translation' . $this->language_tag]) && $mdata['value_translation' . $this->language_tag] != '') {
                    $mdata['value'] = $mdata['value_translation' . $this->language_tag];
                }

                $mdata = QuickModeSubmittedValueHydrator::hydrate($this->p, $mdata, $this->language_tag);

                switch ($mdata['bfType']) {
                    case 'bfTextfield':
                        $this->renderBootstrapStyleTextfieldField($mdata, $label, $tabIndex, $onclick, $onblur, $onchange, $onfocus, $onselect, $readonly);
                        break;

                    case 'bfTextarea':
                        $width = '';
                        if ($mdata['width'] != '') {
                            $width = 'width:' . htmlentities(strip_tags($mdata['width'])) . ' !important; min-width:' . htmlentities(strip_tags($mdata['width'])) . ' !important;';
                        }
                        $height = '';
                        if ($mdata['height'] != '') {
                            $height = 'height:' . htmlentities(strip_tags($mdata['height'])) . ';';
                        }
                        $size = '';
                        if ($height != '' || $width != '') {
                            $size = 'style="' . $width . $height . '" ';
                        }
                        $icon = '';
                        if ($this->rootMdata['themebootstrapThemeEngine'] == 'bootstrap' && $this->rootMdata['themebootstrap'] == 'Azure') {
                            if (!isset($mdata['icon']) || $mdata['icon'] == '') {
                                $icon = '<i class="fas fa-pencil iconf--fumi" aria-hidden="true"></i>';
                            } else {
                                $icon = '<i class="fas ' . htmlentities($mdata['icon'], ENT_QUOTES, 'UTF-8') . ' iconf--fumi" aria-hidden="true"></i>';
                            }
                        }

                        $onkeyup = '';
                        if (isset($mdata['maxlength']) && $mdata['maxlength'] > 0) {
                            $onkeyup = 'onkeyup="bfCheckMaxlength(' . intval($mdata['dbId']) . ', ' . intval($mdata['maxlength']) . ', ' . (isset($mdata['showMaxlengthCounter']) && $mdata['showMaxlengthCounter'] ? 'true' : 'false') . ')" ';
                        }

                        /* translatables */
                        if (isset($mdata['placeholder_translation' . $this->language_tag]) && $mdata['placeholder_translation' . $this->language_tag] != '') {
                            $mdata['placeholder'] = $mdata['placeholder_translation' . $this->language_tag];
                        }
                        if (isset($mdata['value_translation' . $this->language_tag]) && $mdata['value_translation' . $this->language_tag] != '') {
                            $mdata['value'] = $mdata['value_translation' . $this->language_tag];
                        }
                        /* translatables end */

                        echo '<div class="' . $this->bsClass('controls') . ' ' . $this->bsClass('form-inline') . '">';
                        echo '<div class="' . $this->bsClass('form-group') . '">';
                        echo $label;
                        echo $icon;
                        if (isset($mdata['is_html']) && $mdata['is_html']) {
                            echo '<div style="display: inline-block; vertical-align: top; width: ' . strip_tags($mdata['width']) . ';">';
                            $editor = Editor::getInstance('tinymce');
                            $this->htmltextareas[] = 'ff_nm_' . $mdata['bfName'] . '[]';
                            $this->htmltextareasDbIds[] = 'ff_elem' . $mdata['dbId'];
                            echo $editor->display('ff_nm_' . $mdata['bfName'] . '[]', htmlentities(trim($mdata['value']), ENT_QUOTES, 'UTF-8'), strip_tags($mdata['width']), strip_tags($mdata['height']), '75', '20', true, 'ff_elem' . $mdata['dbId']);
                            echo '</div>';
                            echo '<style type="text/css">.toggle-editor{display: none;}</style>';
                        } else {
                            echo $this->quickModeTextareaStrategy()->build(
                                $mdata,
                                $this->language_tag,
                                $this->bsClass('form-control') . ' ff_elem inputbox',
                                $tabIndex . $onclick . $onblur . $onchange . $onfocus . $onselect . $readonly,
                                ' '
                                , true
                            );
                        }
                        echo '</div>';
                        echo '</div>';
                        break;

                    case 'bfRadioGroup':
                        $this->renderBootstrapStyleRadioGroupField($mdata, $label, $tabIndex, $onclick, $onblur, $onchange, $onfocus, $onselect, $readonly, 'bfRadioGroupWrap');
                        break;

                    case 'bfCheckboxGroup':
                        $this->renderBootstrapStyleCheckboxGroupField($mdata, $label, $tabIndex, $onclick, $onblur, $onchange, $onfocus, $onselect, $readonly, 'bfCheckboxGroupWrap');
                        break;

                    case 'bfCheckbox':
                        $this->renderBootstrapStyleCheckboxField($mdata, $label, $tabIndex, $onclick, $onblur, $onchange, $onfocus, $onselect, $readonly);
                        break;

                    case 'bfSelect':
                        $this->renderBootstrapStyleSelectField($mdata, $label, $tabIndex, $onclick, $onblur, $onchange, $onfocus, $onselect, $readonly);
                        break;

                    case 'bfFile':
                        echo '<div class="' . $this->bsClass('controls') . ' ' . $this->bsClass('form-inline') . '">';
                        echo '<div class="' . $this->bsClass('form-group') . ' ' . $this->bsClass('other-form-group') . '">';
                        echo $label;
                        echo '<span class="' . $this->bsClass('nonform-control') . '">';
                        //if( ( isset( $mdata['flashUploader'] ) && $mdata['flashUploader'] ) || ( isset( $mdata['html5'] ) && $mdata['html5'] ) ){

                        $base = (new QuickModeUploadBasePathBuilder())->build(Uri::base());

                        echo '<input type="hidden" id="flashUpload' . $mdata['bfName'] . '" name="flashUpload' . $mdata['bfName'] . '" value="bfFlashFileQueue' . $mdata['dbId'] . '"/>' . "\n";
                        $this->hasFlashUpload = true;
                        $uploadOptions = $this->quickModeUploadOptionsBuilder()->build($mdata);
                        $exts = $uploadOptions['extensions'];
                        $bytes = $uploadOptions['maxFileSize'];
                        $multiSelection = $uploadOptions['multiSelection'];
                        $runtimes = $uploadOptions['runtimes'];
                        echo "
                                                        <span id=\"bfUploadContainer" . $mdata['dbId'] . "\">
                                                            <button type=\"button\" class=\"" . $this->bsClass('btn') . " " . $this->bsClass('btn-primary') . " bfUploadButton button\" id=\"bfPickFiles" . $mdata['dbId'] . "\"><i class=\"" . $this->bsClass('icon-upload') . "\"></i></button>
							</span>
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
    (string) json_encode(Text::_('COM_BREEZINGFORMSNG_CHOOSE_FILE')),
    chr(10),
    (string) ($base . ($this->p->app->getConfig()->get('sef') && !$this->p->app->getConfig()->get('sef_rewrite') ? 'index.php/' : '') . ($this->p->app->getInput()->getCmd('lang', '') && $this->p->app->getConfig()->get('sef') ? ($this->p->app->getConfig()->get('sef_rewrite') ? 'index.php' : '') : 'index.php'))
) . "
" . QuickModeUploadEntryCallbacksBuilder::build(
    (int) $mdata['dbId'],
    (string) $this->cancelImagePath,
    true,
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
                                                                                JQuery('#bfPickFiles" . $mdata['dbId'] . "').prop('disabled',true);
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
                        //}else{
                        //    echo '<input class="ff_elem" '.$tabIndex.$onclick.$onblur.$onchange.$onfocus.$onselect.$readonly.'type="file" name="ff_nm_'.$mdata['bfName'].'[]" id="ff_elem'.$mdata['dbId'].'"/>'."\n";
                        //}
                        if ($mdata['attachToAdminMail']) {
                            echo '<input type="hidden" name="attachToAdminMail[' . $mdata['bfName'] . ']" value="true"/>' . "\n";
                        }
                        if ($mdata['attachToUserMail']) {
                            echo '<input type="hidden" name="attachToUserMail[' . $mdata['bfName'] . ']" value="true"/>' . "\n";
                        }
                        echo '</span>';
                        echo '</div>';
                        echo '</div>';
                        break;

                    case 'bfSubmitButton':
                        $this->renderBootstrapStyleSubmitButtonField($mdata, $label, $tabIndex, $onblur, $onchange, $onfocus, $onselect, $readonly);
                        break;

                    case 'bfHidden':
                        $this->renderHiddenField($mdata);
                        break;

                    case 'bfSummarize':
                        $this->renderBootstrapStyleSummarizeField($mdata, $label);
                        break;

                    case 'bfReCaptcha':
                        echo '<div class="' . $this->bsClass('controls') . ' ' . $this->bsClass('form-inline') . '' . (isset($mdata['pubkey']) && $mdata['pubkey'] ? '' : ' ' . $this->bsClass('well') . ' ' . $this->bsClass('well-small') . '') . '">';
                        echo '<div class="' . $this->bsClass('form-group') . ' ' . $this->bsClass('other-form-group') . '">';
                        echo $label;
                        echo '<span class="' . $this->bsClass('nonform-control') . '">';
                        echo $this->quickModeReCaptchaFieldBuilder()->build(
                            $mdata,
                            $this->p->app,
                            $this->hasFlashUpload,
                            false,
                            false,
                            $this->bsClass('control-group'),
                            $this->bsClass('controls'),
                            true
                        );

                        echo '</span>';
                        echo '</div>';
                        echo '</div>';

                        break;

                    case 'bfNumberInput':
                        $this->renderBootstrapStyleNumberInputField($mdata, $label, $tabIndex, $onclick, $onblur, $onchange, $onfocus, $onselect, $readonly);
                        break;

                    case 'bfCaptcha':
                        echo '<div class="' . $this->bsClass('controls') . ' ' . $this->bsClass('form-inline') . '">';
                        echo '<div class="' . $this->bsClass('form-group') . ' ' . $this->bsClass('other-form-group') . '">';
                        echo $label;
                        echo '<span class="' . $this->bsClass('nonform-control') . '">';

                        $captcha_url = $this->quickModeCaptchaUrlBuilder()->build(
                            Uri::root(true),
                            $this->p->app->isClient('administrator')
                        );

                        echo '<div style="display: inline-block;">';

                        echo $this->quickModeCaptchaMarkupBuilder()->buildImage(
                            isset($mdata['width']) && intval($mdata['width']) > 0 ? ' style="width: ' . intval($mdata['width']) . 'px !important;min-width: ' . intval($mdata['width']) . 'px !important;max-width: ' . intval($mdata['width']) . 'px !important;"' : 'style="width: 230px !important;min-width: 230px !important;max-width: 230px !important;"',
                            'ff_capimgValue',
                            'ff_capimg ' . $this->bsClass('img-thumbnail'),
                            $captcha_url
                        );
                        echo '<div style="height: 10px;"></div>';
                        echo '<div class="' . $this->bsClass('input-append') . '">';
                        echo $this->quickModeCaptchaMarkupBuilder()->buildResponseInput(
                            isset($mdata['width']) && intval($mdata['width']) > 0 ? ' style="width:' . (intval($mdata['width']) - 45) . 'px !important;min-width:' . (intval($mdata['width']) - 45) . 'px !important;max-width:' . (intval($mdata['width']) - 45) . 'px !important;"' : '',
                            $this->bsClass('form-control') . ' ' . $this->bsClass('custom-form-control') . ' ff_elem bfCaptchaField',
                            '',
                            true
                        );
                        echo '<button type="button" class="ff_elem ' . $this->bsClass('btn') . ' ' . $this->bsClass('btn-primary') . ' button" onclick="' . $this->quickModeCaptchaReloadScriptBuilder()->build($captcha_url) . '"><i class="' . $this->bsClass('icon-refresh') . '"></i></button>' . "\n";
                        echo '</div>';
                        echo '</div>';

                        echo '</span>';
                        echo '</div>';
                        echo '</div>';

                        break;

                    case 'bfCalendar':
                        $this->renderBootstrapStyleCalendarField($mdata, $label);
                        break;

                    case 'bfCalendarResponsive':
                        $this->renderBootstrapStyleCalendarResponsiveField($mdata, $label);
                        break;

                    case 'bfSignature':
                        $this->renderBootstrapStyleSignatureField($mdata, $label);
                        break;

                    case 'bfStripe':
                        $this->renderBootstrapStyleStripeField($mdata, $label, $tabIndex, $onblur, $onchange, $onfocus, $onselect, $readonly);
                        break;

                    case 'bfPayPal':
                        $this->renderBootstrapStylePayPalField($mdata, $label, $tabIndex, $onblur, $onchange, $onfocus, $onselect, $readonly);
                        break;

                    case 'bfSofortueberweisung':
                        $this->renderBootstrapStyleSofortueberweisungField($mdata, $label, $tabIndex, $onblur, $onchange, $onfocus, $onselect, $readonly);
                        break;
                }

                if (isset($mdata['bfName']) && isset($mdata['off']) && $mdata['off']) {
                    echo $this->quickModeDeactivationScriptBuilder()->field($mdata['bfName']);
                }

                if ($mdata['bfType'] == 'bfFile') {
                    echo '<span id="ff_elem' . $mdata['dbId'] . '_files"></span>';
                }

                if ($mdata['bfType'] != 'bfHidden') {
                    echo '</div>' . "\n";
                }
            }
        }

        /**
         * Paging and wrapping of inline element containers
         */
        if (isset($dataObject['properties']) && $dataObject['properties']['type'] == 'section' && $dataObject['properties']['displayType'] == 'inline') {
            echo '<div class="bfClearfix ' . $this->bsClass('row') . '">' . "\n";
        }

        if (isset($dataObject['children']) && count($dataObject['children']) != 0) {
            $childrenAmount = count($dataObject['children']);
            for ($i = 0; $i < $childrenAmount; $i++) {
                $this->process($dataObject['children'][$i], $mdata, $parentPage, $i, $childrenAmount, $dataObject);
            }
        }

        if (isset($dataObject['properties']) && $dataObject['properties']['type'] == 'section' && $dataObject['properties']['displayType'] == 'inline') {
            echo '</div>' . "\n";
        }

        if (isset($dataObject['properties']) && $dataObject['properties']['type'] == 'section' && $dataObject['properties']['bfType'] == 'section') {
            echo '</div>'; // row-fluid
            echo '</div>' . "\n";
        } elseif (isset($dataObject['properties']) && $dataObject['properties']['type'] == 'section' && $dataObject['properties']['bfType'] == 'normal') {
            if (isset($dataObject['properties']['name']) && $dataObject['properties']['name'] != '') {
                echo '</div>'; // row-fluid
                echo '</section>' . "\n";
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

                echo '<div class="bfClearfix ' . $this->bsClass('row') . '"></div>';
                echo '<div class="' . $this->bsClass('form-actions') . '">';
                echo '<div class="' . $this->bsClass('form-actions-buttons') . '">';

                //
                /*
                  if($this->rootMdata['pagingInclude'] && $dataObject['properties']['pageNumber'] > 1){

                  if(isset($this->rootMdata['pagingPrevLabel_translation'.$this->language_tag]) && $this->rootMdata['pagingPrevLabel_translation'.$this->language_tag] != ''){
                  $this->rootMdata['pagingPrevLabel'] = $this->rootMdata['pagingPrevLabel_translation'.$this->language_tag];
                  }

                  echo '<button type="button" class="bfPrevButton ' . $this->bsClass('btn') . ' ' . $this->bsClass('btn-primary') . ' ' . $this->bsClass('float-start') . ' button' . $this->fadingClass . '" type="submit" onclick="bf_validate_prevpage(' . ($dataObject['properties']['pageNumber'] - 1) . ');populateSummarizers();if(typeof bfRefreshAll != \'undefined\'){bfRefreshAll();}" value="' . htmlentities(trim($this->rootMdata['pagingPrevLabel']), ENT_QUOTES, 'UTF-8') . '"><span>' . htmlentities(trim($this->rootMdata['pagingPrevLabel']), ENT_QUOTES, 'UTF-8') . '</span></button>' . "\n";
                  } */

                if ($this->rootMdata['pagingInclude'] && $dataObject['properties']['pageNumber'] < count($this->dataObject['children']) - $last) {
                    /* translatables */
                    if (isset($this->rootMdata['pagingNextLabel_translation' . $this->language_tag]) && $this->rootMdata['pagingNextLabel_translation' . $this->language_tag] != '') {
                        $this->rootMdata['pagingNextLabel'] = $this->rootMdata['pagingNextLabel_translation' . $this->language_tag];
                    }
                    /* translatables end */
                    echo '<button type="button" class="bfNextButton ' . $this->bsClass('btn') . ' ' . $this->bsClass('btn-primary') . ' ' . $this->bsClass('float-end') . ' button' . $this->fadingClass . '" type="submit" onclick="' . $this->quickModePagingActionBuilder()->onePageNext((int) $dataObject['properties']['pageNumber'], (int) $dataObject['properties']['pageNumber'] + 1) . '" value="' . htmlentities(trim($this->rootMdata['pagingNextLabel']), ENT_QUOTES, 'UTF-8') . '"><span>' . htmlentities(trim($this->rootMdata['pagingNextLabel']), ENT_QUOTES, 'UTF-8') . '</span></button>' . "\n";
                }

                $callSubmit = $this->quickModeSubmitActionBuilder()->build(true, $this->hasFlashUpload);
                if ($this->rootMdata['submitInclude'] && $dataObject['properties']['pageNumber'] + 1 > count($this->dataObject['children']) - $last) {
                    /* translatables */
                    if (isset($this->rootMdata['submitLabel_translation' . $this->language_tag]) && $this->rootMdata['submitLabel_translation' . $this->language_tag] != '') {
                        $this->rootMdata['submitLabel'] = $this->rootMdata['submitLabel_translation' . $this->language_tag];
                    }
                    /* translatables end */
                    echo '<button data-style="zoom-in" type="button" id="bfSubmitButton" class="bfSubmitButton ' . $this->bsClass('btn') . ' ' . $this->bsClass('btn-primary') . ' ' . $this->bsClass('float-end') . ' button' . $this->fadingClass . '" onclick="this.disabled=true;if(typeof bf_htmltextareainit != \'undefined\'){ bf_htmltextareainit() }if(document.getElementById(\'bfPaymentMethod\')){document.getElementById(\'bfPaymentMethod\').value=\'\';};' . $callSubmit . ';" value="' . htmlentities(trim($this->rootMdata['submitLabel']), ENT_QUOTES, 'UTF-8') . '"><span>' . htmlentities(trim($this->rootMdata['submitLabel']), ENT_QUOTES, 'UTF-8') . '</span></button>' . "\n";
                }

                if ($this->rootMdata['cancelInclude'] && $dataObject['properties']['pageNumber'] + 1 > count($this->dataObject['children']) - $last) {
                    /* translatables */
                    if (isset($this->rootMdata['cancelLabel_translation' . $this->language_tag]) && $this->rootMdata['cancelLabel_translation' . $this->language_tag] != '') {
                        $this->rootMdata['cancelLabel'] = $this->rootMdata['cancelLabel_translation' . $this->language_tag];
                    }
                    /* translatables end */
                    echo '<button class="bfCancelButton ' . $this->bsClass('btn') . ' ' . $this->bsClass('btn-secondary') . ' ' . $this->bsClass('float-end') . ' button' . $this->fadingClass . '" type="submit" onclick="' . $this->quickModePagingActionBuilder()->cancel() . '"  value="' . htmlentities(trim($this->rootMdata['cancelLabel']), ENT_QUOTES, 'UTF-8') . '"><span>' . htmlentities(trim($this->rootMdata['cancelLabel']), ENT_QUOTES, 'UTF-8') . '</span></button>' . "\n";
                }

                echo '</div>';
                echo '</div>';
            }
        }
    }

    public function render()
    {

        if (isset($this->rootMdata['themebootstrapUseProgress']) && $this->rootMdata['themebootstrapUseProgress']) {
            echo QuickModeProgressMarkupBuilder::build(
                $this->bsClass('progress'),
                $this->bsClass('bar'),
                (bool) $this->rootMdata['lastPageThankYou']
            );
        }

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
        $area_count = count($this->htmltextareas);
        if ($area_count) {
            $editor = Editor::getInstance('tinymce');
            RuntimeAssetLoader::script(
                $this->p->app,
                Uri::root(true) . '/media/com_breezingformsng/js/site/quickmode-html-textareas.js'
            );
            for ($i = 0; $i < $area_count; $i++) {
                $htmltextarea = $this->htmltextareas[$i];
                $dbId = $this->htmltextareasDbIds[$i];
                echo $this->quickModeHtmlTextareaScriptBuilder()->build(
                    $htmltextarea,
                    $this->getEditorContent($dbId)
                );
            }
        }

        if ($this->hasFlashUpload) {
            $tickets = $this->p->app->getSession()->get('bfFlashUploadTickets', array());
            $tickets[$this->flashUploadTicket] = array(); // stores file info for later processing
            $this->p->app->getSession()->set('bfFlashUploadTickets', $tickets);
            echo '<input type="hidden" name="bfFlashUploadTicket" value="' . $this->flashUploadTicket . '"/>' . "\n";
            RuntimeAssetLoader::script($this->p->app, Uri::root(true) . '/components/com_breezingformsng/libraries/jquery/center.js');
            RuntimeAssetLoader::script($this->p->app, Uri::root(true) . '/media/com_breezingformsng/js/site/quickmode-flash-upload-onepage.js');
            echo "<div style=\"visibility:hidden;\" id=\"bfFileQueue\"></div>";
            echo "<div style=\"visibility:hidden;display:none;\" id=\"bfSubmitMessage\">" . Text::_('COM_BREEZINGFORMSNG_SUBMIT_MESSAGE') . "</div>";
        }
        echo '<noscript>' . Text::_('COM_BREEZINGFORMSNG_JAVASCRIPT_REQUIRED') . '</noscript>' . "\n";
        RuntimeAssetLoader::script($this->p->app, Uri::root(true) . '/media/com_breezingformsng/js/site/quickmode-onepage-switchpage.js');
        if ($this->rootMdata['lastPageThankYou']) {
            echo '
                        <div class="remodal" data-remodal-id="modal">
                        <button data-remodal-action="close" class="remodal-close"></button>
                        <p id="remodal-thankyou-msg"></p>
                        <br>
                        <button data-remodal-action="confirm" class="' . $this->bsClass('btn') . ' ' . $this->bsClass('btn-primary') . '">' . Text::_('COM_BREEZINGFORMSNG_OK') . '</button>
                      </div>
                      <script type="text/javascript">
                      <!--
                      JQuery(document).ready(function(){
                        JQuery(document).on("closing", ".remodal", function (e) {
                            bf_remodal_close();
                        });
                      });
                      //-->
                      </script>
                    ';
        }
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
