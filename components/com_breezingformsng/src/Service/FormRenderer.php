<?php

declare(strict_types=1);
/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Vcmb\Component\BreezingformsNG\Site\Service;

\defined('_JEXEC') or die;

use Exception;
use HTML_facileFormsProcessor;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Cache\CacheControllerFactoryInterface;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Mail\MailerFactoryInterface;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Vcmb\Component\BreezingformsNG\Site\Service\Runtime\RuntimeAssetLoader;
use Joomla\Database\ParameterType;
use Joomla\Database\DatabaseInterface;
use Vcmb\Component\BreezingformsNG\Site\Service\Runtime\RequestParameterParser;
use Vcmb\Component\BreezingformsNG\Site\Service\Upload\UploadFileCleaner;

/**
 * Renders a form (ff_task=view) or processes a submission (ff_task=submit)
 * through the native processing services and their public processor facade.
 */
final class FormRenderer
{
    public function __construct(
        private readonly CMSApplication $application,
        private readonly DatabaseInterface $database,
        private readonly MailerFactoryInterface $mailerFactory,
        private readonly CacheControllerFactoryInterface $cacheControllerFactory,
        private readonly RequestParameterParser $requestParameterParser,
        private readonly UploadFileCleaner $uploadFileCleaner,
    ) {
    }

    public function render(array $context = []): void
    {
        global $ff_version, $ff_request, $ff_processor, $ff_target;

        $ff_applic = (string) ($context['ff_applic'] ?? '');
        $xModuleId = (int) ($context['module_id'] ?? 0);
        $params = $context['params'] ?? null;
        $plg_editable = (int) ($context['plg_editable'] ?? 0);
        $plg_editable_override = (int) ($context['plg_editable_override'] ?? 0);

// declare local vars
// (1) only used in component space and not plain form)
$plainform = 0;  // running as plain form by index.php?tmpl=component
$formid = null;  // form id number
$formname = null;  // form name
$task = 'view'; // either 'view' or 'submit'
$page = 1;  // page to display
$inframe = 0;  // run in iframe
$border = 0;  // show a border around the form (1)
$align = 1;  // 0-left 1-center 2-right (1)
$left = 0;  // left margin in px (1)
$top = 0;  // top margin in px (1)
$suffix = '';  // CSS class suffix
$parprv = '';  // private parameters
$runmode = 0;  // run mode
$pagetitle = true;  // set page title
$editable = 0;
$editable_override = 0;
$menu_item_title = '';
$menu_item_show_page_heading = 0;
$menu_item_page_heading = '';
$menu_item_meta_description = '';
$menu_item_meta_keywords = '';
$menu_item_robots = '';
$runmode = htmlentities((string) $this->application->getInput()->getString('ff_runmode', $runmode), ENT_QUOTES, 'UTF-8');

// check for plain form

$plainform = $this->application->getInput()->getWord('tmpl', '') == 'component';

// create target id for this form and check if ff params are ment for this target
if (!$ff_target)
    $ff_target = 1;
else
    $ff_target++;
$parent_target = $this->application->getInput()->getInt('ff_target', 1);
$my_ff_params = $plainform || $parent_target == $ff_target;

// clear list of request parameters
$ff_request = array();

    
    if ($runmode == _FF_RUNMODE_FRONTEND) {

        // is this called by a module?
        if ($ff_applic == 'mod_facileforms') {

            // get the module parameters
            $formname = $params->get('ff_mod_name');
            $page = intval($params->get('ff_mod_page', $page));
            $inframe = intval($params->get('ff_mod_frame', $inframe));
            $border = intval($params->get('ff_mod_border', $border));
            $align = intval($params->get('ff_mod_align', $align));
            $left = intval($params->get('ff_mod_left', $left));
            $top = intval($params->get('ff_mod_top', $top));
            $suffix = $params->get('ff_mod_suffix', '');
            $parprv = $params->get('ff_mod_parprv', '');
            $ff_request = array_replace(
                $ff_request,
                $this->requestParameterParser->parse((string) $params->get('ff_mod_parpub', ''))
            );
            $pagetitle = false;

            $this->application->getSession()->set('ff_editableMod' . $xModuleId . $formname, intval($params->get('ff_mod_editable', $editable)));
            $this->application->getSession()->set('ff_editable_overrideMod' . $xModuleId . $formname, intval($params->get('ff_mod_editable_override', $editable_override)));
        } else if ($ff_applic == 'plg_facileforms') {

            $formname = htmlentities($this->application->getInput()->getString('ff_name', ''), ENT_QUOTES, 'UTF-8');
            $page = $this->application->getInput()->getInt('ff_page', 1);
            $inframe = $this->application->getInput()->getInt('ff_frame', 0);
            $border = $this->application->getInput()->getInt('ff_border', 0);
            $align = $this->application->getInput()->getInt('ff_align', 1);
            $editable = intval($plg_editable);
            $editable_override = intval($plg_editable_override);
            $left = 0;
            $top = 0;
            $suffix = htmlentities($this->application->getInput()->getString('ff_suffix', ''), ENT_QUOTES, 'UTF-8');
            $parprv = '';
            $ff_request = array_replace($ff_request, $this->requestParameterParser->parse(''));
        } else {

            // is this called with an Itemid?
            if ($this->application->getInput()->getInt('Itemid', 0) > 0 && $this->application->getInput()->getString('ff_applic', '') != 'mod_facileforms' && $this->application->getInput()->getString('ff_applic', '') != 'plg_facileforms') {

                $menu = $this->application->getMenu()->getActive();
                $params = $menu->getParams();

                if ($params !== null) {

                    $menu_item_title = $params->get('page_title', '');
                    $menu_item_show_page_heading = $params->get('show_page_heading', 0);
                    $menu_item_page_heading = $params->get('page_heading', '');

                    $menu_item_meta_description = $params->get('menu-meta_description', '');
                    $menu_item_meta_keywords = $params->get('menu-meta_keywords', '');
                    $menu_item_robots = $params->get('robots', '');

                    if ($menu_item_meta_description) {
                        $this->application->getDocument()->setMetaData('description', $menu_item_meta_description);
                    }

                    if ($menu_item_meta_keywords) {
                        $this->application->getDocument()->setMetaData('keywords', $menu_item_meta_keywords);
                    }

                    if ($menu_item_robots) {
                        $this->application->getDocument()->setMetaData('robots', $menu_item_robots);
                    }

                    $formname = $params->get('ff_com_name');
                    $page = intval($params->get('ff_com_page', $page));
                    $inframe = intval($params->get('ff_com_frame', $inframe));
                    $border = intval($params->get('ff_com_border', $border));
                    $align = intval($params->get('ff_com_align', $align));
                    $left = intval($params->get('ff_com_left', $left));
                    $top = intval($params->get('ff_com_top', $top));
                    $editable = intval($params->get('ff_com_editable', $editable));
                    $editable_override = intval($params->get('ff_com_editable_override', $editable_override));
                    $suffix = $params->get('ff_com_suffix', '');
                    $parprv = $params->get('ff_com_parprv', '');
                    $ff_request = array_replace(
                        $ff_request,
                        $this->requestParameterParser->parse((string) $params->get('ff_com_parpub', ''))
                    );
                }
            } // if
        }
    } // if

    if ($my_ff_params) {
        // allow overriding by url params
        $formid = $this->application->getInput()->getString('ff_form', $formid);

        if ($formid == null)
            $formname = $this->application->getInput()->getString('ff_name', $formname);
        else
            $formname = null;

        $task = $this->application->getInput()->getString('ff_task', $task);
        $page = $this->application->getInput()->getInt('ff_page', $page);
        $inframe = $this->application->getInput()->getInt('ff_frame', $inframe);
        $border = $this->application->getInput()->getInt('ff_border', $border);
        $align1 = $this->application->getInput()->getInt('ff_align', -1);
        if ($align1 >= 0) {
            $align = $this->application->getInput()->getInt('ff_align', $align);
            $left = 0;
            if ($align > 2) {
                $left = $align;
                $align = 3;
            }
        } // if
        $top = $this->application->getInput()->getInt('ff_top', $top);
        $suffix = $this->application->getInput()->getString('ff_suffix', $suffix);
    }

    // load form
    $ok = true;
    if (is_numeric($formid)) {
        $formIdInt = (int) $formid;
        $query = $this->database->getQuery(true)
            ->select('*')
            ->from($this->database->quoteName('#__facileforms_forms'))
            ->where($this->database->quoteName('id') . ' = :formIdInt')
            ->where($this->database->quoteName('published') . ' = 1')
            ->bind(':formIdInt', $formIdInt, ParameterType::INTEGER);
        $this->database->setQuery($query);
        $forms = $this->database->loadObjectList();
        if (count($forms) < 1) {
            echo '[Form ' . intval($formid) . ' not found!]';
            $ok = false;
        } else
            $form = $forms[0];
    } else
        if ($formname != null) {
            $query = $this->database->getQuery(true)
                ->select('*')
                ->from($this->database->quoteName('#__facileforms_forms'))
                ->where($this->database->quoteName('name') . ' = :formname')
                ->where($this->database->quoteName('published') . ' = 1')
                ->order([$this->database->quoteName('ordering'), $this->database->quoteName('id')])
                ->bind(':formname', $formname, ParameterType::STRING);
            $this->database->setQuery($query);
            $forms = $this->database->loadObjectList();
            if (count($forms) < 1) {
                echo '[Form ' . htmlentities((string) $formname, ENT_QUOTES, 'UTF-8') . ' not found!]';
                $ok = false;
            } else
                $form = $forms[0];
        } else {

            if ($this->application->getInput()->getString('option', '') != 'com_breezingformsng') {
                throw new Exception(Text::_('COM_BREEZINGFORMSNG_NO_FORM_ID_PROVIDED'), 404);
            } else {
                echo '[No form id or name provided!]';
            }

            $ok = false;
        } // if

    if ($ok) {

        $session = $this->application->getSession();
        $contentId = $this->application->getInput()->getInt('ff_contentid', 0);
        $pluginEditableKey = 'ff_editablePlg' . $contentId . $form->name;
        $pluginEditableOverrideKey = 'ff_editable_overridePlg' . $contentId . $form->name;

        // set by plugin
        if ($session->get($pluginEditableKey, 0) != 0 && ($this->application->getInput()->getString('ff_applic', '') == 'plg_facileforms' || $ff_applic == 'plg_facileforms')) {
            $editable = $session->get($pluginEditableKey, 0);
        }

        // set by plugin
        if ($session->get($pluginEditableOverrideKey, 0) != 0 && ($this->application->getInput()->getString('ff_applic', '') == 'plg_facileforms' || $ff_applic == 'plg_facileforms')) {
            $editable_override = $session->get($pluginEditableOverrideKey, 0);
        }

        // set by module
        if (($this->application->getInput()->getString('ff_applic', '') == 'mod_facileforms' || $ff_applic == 'mod_facileforms')) {
            if ($this->application->getSession()->get('ff_editableMod' . $xModuleId . $form->name, 0) != 0) {
                $editable = $this->application->getSession()->get('ff_editableMod' . $xModuleId . $form->name, 0);
            } else if ($this->application->getSession()->get('ff_editableMod' . $this->application->getInput()->getInt('ff_module_id', 0) . $form->name, 0) != 0) {
                $editable = $this->application->getSession()->get('ff_editableMod' . $this->application->getInput()->getInt('ff_module_id', 0) . $form->name, 0);
            }
        }

        // set by module
        if (($this->application->getInput()->getString('ff_applic', '') == 'mod_facileforms' || $ff_applic == 'mod_facileforms')) {
            if ($this->application->getSession()->get('ff_editable_overrideMod' . $xModuleId . $form->name, 0) != 0) {
                $editable_override = $this->application->getSession()->get('ff_editable_overrideMod' . $xModuleId . $form->name, 0);
            } else if ($this->application->getSession()->get('ff_editable_overrideMod' . $this->application->getInput()->getInt('ff_module_id', 0) . $form->name, 0) != 0) {
                $editable_override = $this->application->getSession()->get('ff_editable_overrideMod' . $this->application->getInput()->getInt('ff_module_id', 0) . $form->name, 0);
            }
        }

        if ($ff_applic != 'plg_facileforms' && $pagetitle && $form->title != '' && !($this->application->getInput()->getInt('cb_form_id', 0) || $this->application->getInput()->getCmd('cb_record_id', ''))) {
            if ($menu_item_title != '') {
                $this->application->getDocument()->setTitle($menu_item_title);
            } else { // being set by module, false implies no change at all
                $this->application->getDocument()->setTitle($form->title);
            }
        }

        if ($form->name == $formname)
            $ff_request = array_replace(
                $ff_request,
                $this->requestParameterParser->parse((string) $parprv)
            );
        if ($my_ff_params) {
            foreach (array_keys($this->application->getInput()->getArray()) as $prop) {
                if (str_starts_with($prop, 'ff_param_')) {
                    $ff_request[$prop] = $this->application->getInput()->getString($prop);
                }
            }
        } // if

        if ($inframe && !$plainform) {

            // open frame and detach processing
            $divstyle = 'width:100%;';
            switch ($align) {
                case 0:
                    $divstyle .= 'text-align:left;';
                    break;
                case 1:
                    $divstyle .= 'text-align:center;';
                    break;
                case 2:
                    $divstyle .= 'text-align:right;';
                    break;
                case 3:
                    if ($left > 0)
                        $divstyle .= 'padding-left:' . htmlentities((string) $left, ENT_QUOTES, 'UTF-8') . 'px;';
                    break;
                default:
                    break;
            } // switch
            if ($top > 0)
                $divstyle .= 'padding-top:' . htmlentities((string) $top, ENT_QUOTES, 'UTF-8') . 'px;';
            $framewidth = 'width="' . htmlentities((string) ($form->width . ($form->widthmode ? '%' : '')), ENT_QUOTES, 'UTF-8') . '" ';
            $frameheight = '';
            if (!$form->heightmode)
                $frameheight = 'height="' . htmlentities((string) $form->height, ENT_QUOTES, 'UTF-8') . '" ';
            $url = rtrim(Uri::root(), '/') . '/index.php'
                . '?option=com_breezingformsng'
                . '&amp;Itemid=' . (($this->application->getInput()->getInt('Itemid', 0) > 0 && $this->application->getInput()->getInt('Itemid', 0) < 99999999) ? $this->application->getInput()->getInt('Itemid', 0) : 0)
                . '&amp;ff_form=' . htmlentities((string) $form->id, ENT_QUOTES, 'UTF-8')
                . '&amp;ff_applic=' . htmlentities((string) $ff_applic, ENT_QUOTES, 'UTF-8')
                . '&amp;ff_module_id=' . htmlentities((string) $xModuleId, ENT_QUOTES, 'UTF-8')
                . '&amp;format=html'
                . '&amp;tmpl=component'
                . '&amp;ff_frame=1';
            if ($page != 1)
                $url .= '&amp;ff_page=' . htmlentities((string) $page, ENT_QUOTES, 'UTF-8');
            if ($border)
                $url .= '&amp;ff_border=1';
            if ($parent_target > 1)
                $url .= '&amp;ff_target=' . htmlentities((string) $parent_target, ENT_QUOTES, 'UTF-8');
            reset($ff_request);

            foreach ($ff_request as $prop => $val)
                $url .= '&amp;' . htmlentities((string) $prop, ENT_QUOTES, 'UTF-8') . '=' . htmlentities(urlencode((string) $val), ENT_QUOTES, 'UTF-8');

            $params = 'id="ff_frame' . $form->id . '" ' .
                'src="' . $url . '" ' .
                $framewidth .
                $frameheight .
                'frameborder="' . htmlentities((string) $border, ENT_QUOTES, 'UTF-8') . '" ' .
                'allowtransparency="true" ' .
                'scrolling="no" ';
            if ($form->autoheight == 1) {
                $this->application->getDocument()->getWebAssetManager()->useScript('jquery');
                RuntimeAssetLoader::script($this->application, Uri::root(true) . '/components/com_breezingformsng/libraries/jquery/jq.iframeautoheight.js');
                $this->application->getDocument()->getWebAssetManager()->addInlineScript("<!--
                            jQuery(document).ready(function() {
                                //jQuery(\".breezingforms_iframe\").css(\"width\",\"100%\");
                                jQuery(\".breezingforms_iframe\").iframeAutoHeight({heightOffset: 15, debug: false, diagnostics: false});
                            });
                            //-->");
            }

            // DO NOT REMOVE OR CHANGE OR OTHERWISE MAKE INVISIBLE THE FOLLOWING COPYRIGHT MESSAGE
            // FAILURE TO COMPLY IS A DIRECT VIOLATION OF THE GNU GENERAL PUBLIC LICENSE
            // http://www.gnu.org/copyleft/gpl.html
            echo "\n<!-- BreezingForms V" . $ff_version . " Copyright(c) 2008-2013 by Markus Bopp | FacileForms Copyright 2004-2006 by Peter Koch, Chur, Switzerland.  All rights reserved. -->\n";
            // END OF COPYRIGHT
            echo '<div class="bfClearfix" style="' . $divstyle . '">' . "\n" .
                "<iframe class=\"breezingforms_iframe\" " . $params . " sandbox=\"allow-modals allow-same-origin allow-scripts allow-forms allow-pointer-lock allow-popups allow-top-navigation\">\n" .
                "<p>Sorry, your browser cannot display frames!</p>\n" .
                "</iframe>\n" .
                "</div>\n";
        } else {


            if ($menu_item_show_page_heading) {
                echo '<h1>' . ($menu_item_title != '' ? ($menu_item_page_heading != '' ? $menu_item_page_heading : $menu_item_title) : $form->title) . '</h1>' . "\n";
            }

            // process inline
            $myUser = $this->application->getIdentity();

            $username = (string) $myUser->get('username', '');
            $query = $this->database->getQuery(true)
                ->select($this->database->quoteName('id'))
                ->from($this->database->quoteName('#__users'))
                ->where('LOWER(' . $this->database->quoteName('username') . ') = LOWER(:username)')
                ->bind(':username', $username, ParameterType::STRING);
            $this->database->setQuery($query);
            $id = $this->database->loadResult();
            if ($id)
                $myUser->get('id', -1);
            require_once JPATH_SITE . '/components/com_breezingformsng/src/Support/processor_facade.php';
            if ($task == 'view') {
                $div1style = '';
                $div2style = '';
                if ($form->template_code == '') {
                    $fullwidth = $form->widthmode && $form->width >= 100;
                    if ($form->widthmode) {
                        $div1style .= 'min-width:10px;';
                        $div2style .= 'min-width:10px;';
                    } // if
                    $div2style .= 'width:' . htmlentities(($fullwidth ? '100' : $form->width) . ($form->widthmode ? '%' : 'px'), ENT_QUOTES, 'UTF-8') . ';';
                    if (!$form->heightmode)
                        $div2style .= 'height:' . htmlentities((string) $form->height, ENT_QUOTES, 'UTF-8') . 'px;';
                    if ($plainform) {
                        $div2style .= 'position:absolute;top:0px;left:0px;margin:0px;';
                    } else {
                        $div1style .= 'width:100%;';
                        $div2style .= 'position:relative;overflow:hidden;';
                        if ($border)
                            $div2style .= 'border:1px solid black;';
                        if (!$fullwidth) {
                            switch ($align) {
                                case 1:
                                    $div1style .= 'text-align:center;';
                                    $div2style .= 'text-align:left;margin-left:auto;margin-right:auto;';
                                    break;
                                case 2:
                                    $div1style .= 'text-align:right;';
                                    $div2style .= 'text-align:left;margin-left:auto;margin-right:0px;';
                                    break;
                                case 3:
                                    if ($left > 0)
                                        $div2style .= 'margin-left:' . htmlentities((string) $left, ENT_QUOTES, 'UTF-8') . 'px;';
                                default:
                                    break;
                            } // switch
                        } // if
                        if ($top > 0)
                            $div2style .= 'margin-top:' . htmlentities((string) $top, ENT_QUOTES, 'UTF-8') . 'px;';
                    } // if
                }
                ob_start();
                // DO NOT REMOVE OR CHANGE OR OTHERWISE MAKE INVISIBLE THE FOLLOWING COPYRIGHT MESSAGE
                // FAILURE TO COMPLY IS A DIRECT VIOLATION OF THE GNU GENERAL PUBLIC LICENSE
                // http://www.gnu.org/copyleft/gpl.html
                echo "\n<!-- BreezingForms V" . $ff_version . " Copyright(c) 2008-2013 by Markus Bopp | FacileForms Copyright 2004-2006 by Peter Koch, Chur, Switzerland.  All rights reserved. -->\n";
                // END OF COPYRIGHT
                if (trim((string) $form->template_code_processed) !== 'QuickMode') {
                    echo '<div class="alert alert-warning">' . Text::_('COM_BREEZINGFORMSNG_QUICKMODE_ONLY') . '</div>';
                    return;
                }

                $bfStyle = '';
                if ($form->template_code == '') {
                    $bfStyle = ' style="' . $div1style . '"';
                }
                if (!$plainform)
                    echo '<div class="bfClearfix"' . $bfStyle . '>' . "\n";
                if (trim($form->template_code_processed) == '') {
                    echo '<div class="bfClearfix" style="' . $div2style . '">' . "\n";
                }
            } // if task = view
            if ($left > 3)
                $align = $left;
            // remove temporary flash and chunked upload files if any
            $this->uploadFileCleaner->purgeTemporaryUploads(
                JPATH_SITE . '/components/com_breezingformsng/uploads/',
                '_flashtmp'
            );
            $this->uploadFileCleaner->purgeTemporaryUploads(
                JPATH_SITE . '/components/com_breezingformsng/uploads/chunks',
                '_chunktmp'
            );
            $this->uploadFileCleaner->purgePaymentCache(
                JPATH_SITE . '/media/breezingforms/payment_cache/'
            );

            $ff_processor = new HTML_facileFormsProcessor(
                $this->application,
                $this->database,
                $this->mailerFactory,
                $this->cacheControllerFactory,
                $runmode,
                $inframe,
                $form->id,
                $page,
                $border,
                $align,
                $top,
                $ff_target,
                $suffix,
                $editable,
                $editable_override
            );

            if ($task == 'submit') {
                $ff_processor->submit();
            } else {

                $ff_processor->view();

                if (trim($form->template_code_processed) == '') {
                    echo "</div>\n";
                }
                if (!$plainform)
                    echo "</div>\n";

                if ($runmode == _FF_RUNMODE_PREVIEW) {

                    $mouseOvers = '';
                    $draggableIds = '';
                    $draggableSize = count($ff_processor->draggableDivIds);
                    for ($x = 0; $x < $draggableSize; $x++) {
                        if ($x + 1 < $draggableSize) {
                            $draggableIds .= '"' . $ff_processor->draggableDivIds[$x] . '",';
                        } else {
                            $draggableIds .= '"' . $ff_processor->draggableDivIds[$x] . '"';
                        }

                        $mouseOvers .= '
						
							var ' . $ff_processor->draggableDivIds[$x] . '_paddingRTmp;
							var ' . $ff_processor->draggableDivIds[$x] . '_paddingLTmp;
							var ' . $ff_processor->draggableDivIds[$x] . '_colorTmp;
							
							' . $ff_processor->draggableDivIds[$x] . '_colorTmp    = document.getElementById("' . $ff_processor->draggableDivIds[$x] . '").style.backgroundColor;
							' . $ff_processor->draggableDivIds[$x] . '_paddingRTmp = document.getElementById("' . $ff_processor->draggableDivIds[$x] . '").style.paddingRight;
							' . $ff_processor->draggableDivIds[$x] . '_paddingLTmp = document.getElementById("' . $ff_processor->draggableDivIds[$x] . '").style.paddingLeft;
							' . $ff_processor->draggableDivIds[$x] . '_paddingTTmp = document.getElementById("' . $ff_processor->draggableDivIds[$x] . '").style.paddingTop;
							' . $ff_processor->draggableDivIds[$x] . '_paddingBTmp = document.getElementById("' . $ff_processor->draggableDivIds[$x] . '").style.paddingBottom;
							
							function bfItemOver_' . $ff_processor->draggableDivIds[$x] . '(e){
								if(document.getElementById("' . $ff_processor->draggableDivIds[$x] . '")){
									
									document.getElementById("' . $ff_processor->draggableDivIds[$x] . '").style.cursor="pointer";
								
									document.getElementById("' . $ff_processor->draggableDivIds[$x] . '").style.paddingRight = "10px";
									document.getElementById("' . $ff_processor->draggableDivIds[$x] . '").style.paddingLeft = "10px";
									document.getElementById("' . $ff_processor->draggableDivIds[$x] . '").style.paddingTop = "0px";
									document.getElementById("' . $ff_processor->draggableDivIds[$x] . '").style.paddingBottom = "0px";
									document.getElementById("' . $ff_processor->draggableDivIds[$x] . '").style.backgroundColor = "red";
									
									parent.document.getElementById("hoverItem_' . $ff_processor->draggableDivIds[$x] . '").style.backgroundColor = "#cccccc";
								}
							}
						
							function bfItemOut_' . $ff_processor->draggableDivIds[$x] . '(e){
								if(document.getElementById("' . $ff_processor->draggableDivIds[$x] . '")){
								
									document.getElementById("' . $ff_processor->draggableDivIds[$x] . '").style.cursor="";
								
									document.getElementById("' . $ff_processor->draggableDivIds[$x] . '").style.paddingRight= ' . $ff_processor->draggableDivIds[$x] . '_paddingRTmp;
									document.getElementById("' . $ff_processor->draggableDivIds[$x] . '").style.paddingLeft= ' . $ff_processor->draggableDivIds[$x] . '_paddingLTmp;
									document.getElementById("' . $ff_processor->draggableDivIds[$x] . '").style.paddingTop= ' . $ff_processor->draggableDivIds[$x] . '_paddingTTmp;
									document.getElementById("' . $ff_processor->draggableDivIds[$x] . '").style.paddingBottom= ' . $ff_processor->draggableDivIds[$x] . '_paddingBTmp;
									document.getElementById("' . $ff_processor->draggableDivIds[$x] . '").style.backgroundColor = ' . $ff_processor->draggableDivIds[$x] . '_colorTmp;
									
									parent.document.getElementById("hoverItem_' . $ff_processor->draggableDivIds[$x] . '").style.backgroundColor = "";
								}
							}
							
							if(document.getElementById("' . $ff_processor->draggableDivIds[$x] . '")){
								document.getElementById("' . $ff_processor->draggableDivIds[$x] . '").onmouseover = bfItemOver_' . $ff_processor->draggableDivIds[$x] . ';
								document.getElementById("' . $ff_processor->draggableDivIds[$x] . '").onmouseout = bfItemOut_' . $ff_processor->draggableDivIds[$x] . ';
							}
						';
                    }

                    echo '
					<script>
					
					SET_DHTML(' . $draggableIds . ');
					
					' . $mouseOvers . '
					
					function my_DragFunc(){
						parent.document.adminForm.savepos.disabled = false;
						// TODO: when undo is enabled, drag and drop is not possible. needs to be solved
						//parent.document.adminForm.restpos.disabled = false;
					}
					
					function my_DropFunc(){
					
						parent.document.getElementById("ff_itemPositions").value = "";
					
						for(var i = 0; i < parent.ff_coords.length;i++){
						
							eval("var cb = parent.document.adminForm.cb"+i+";");
							
							var itemComma = "";
							if(i+1 < parent.ff_coords.length){
								itemComma = ",";
							} else {
								itemComma = "";
							}
							
							parent.document.getElementById("ff_itemPositions").value += 
								cb.value+":"+document.getElementById("ff_div"+cb.value).style.zIndex+itemComma;
						
						}
						
						for(var i = 0; i < parent.ff_coords.length;i++){
							
							eval("var cb = parent.document.adminForm.cb"+i+";");
							
							if(document.getElementById("ff_div"+cb.value) == document.getElementById(dd.obj.id)){
								
								parent.ff_coords[i][2] = dd.obj.x;
								parent.ff_coords[i][5] = dd.obj.y;
								break;
							}
						}
					}
					
					</script>';
                }

                ob_end_flush();
            } // if
        } // if
    } // if
    }

}
