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
use Joomla\Registry\Registry;
use Vcmb\Component\BreezingformsNG\Site\Service\Runtime\RuntimeAssetLoader;
use Joomla\Database\ParameterType;
use Joomla\Database\DatabaseInterface;
use Vcmb\Component\BreezingformsNG\Site\Service\Runtime\RequestParameterParser;
use Vcmb\Component\BreezingformsNG\Site\Service\Notification\PaymentNotificationCache;
use Vcmb\Component\BreezingformsNG\Site\Service\Upload\TemporaryUploadDirectory;
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

    /**
     * @param array<string, mixed> $context
     */
    public function render(array $context = []): void
    {
        global $ff_version, $ff_request, $ff_processor, $ff_target;

        $document = $this->application->getDocument() ?? throw new \RuntimeException('The document is not available.');

        $ff_applic = self::stringValue($context['ff_applic'] ?? '');
        $xModuleId = self::intValue($context['module_id'] ?? 0);
        $params = $context['params'] instanceof Registry ? $context['params'] : null;
        $plg_editable = self::intValue($context['plg_editable'] ?? 0);
        $plg_editable_override = self::intValue($context['plg_editable_override'] ?? 0);

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
$runmode = _FF_RUNMODE_FRONTEND;  // run mode: the site always runs in frontend mode, never from the request
$pagetitle = true;  // set page title
$editable = 0;
$editable_override = 0;
$menu_item_title = '';
$menu_item_show_page_heading = 0;
$menu_item_page_heading = '';
$menu_item_meta_description = '';
$menu_item_meta_keywords = '';
$menu_item_robots = '';

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

    
    // is this called by a module?
    if ($ff_applic == 'mod_facileforms') {

        // get the module parameters
        $formname = self::stringValue($params?->get('ff_mod_name', ''));
        $page = self::intValue($params?->get('ff_mod_page', $page));
        $inframe = self::intValue($params?->get('ff_mod_frame', $inframe));
        $border = self::intValue($params?->get('ff_mod_border', $border));
        $align = self::intValue($params?->get('ff_mod_align', $align));
        $left = self::intValue($params?->get('ff_mod_left', $left));
        $top = self::intValue($params?->get('ff_mod_top', $top));
        $suffix = self::stringValue($params?->get('ff_mod_suffix', ''));
        $parprv = self::stringValue($params?->get('ff_mod_parprv', ''));
        $ff_request = array_replace(
            $ff_request,
            $this->requestParameterParser->parse(self::stringValue($params?->get('ff_mod_parpub', '')))
        );
        $pagetitle = false;

        $this->application->getSession()->set('ff_editableMod' . $xModuleId . $formname, self::intValue($params?->get('ff_mod_editable', $editable)));
        $this->application->getSession()->set('ff_editable_overrideMod' . $xModuleId . $formname, self::intValue($params?->get('ff_mod_editable_override', $editable_override)));
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
            $params = $menu?->getParams();

            if ($params !== null) {

                $menu_item_title = self::stringValue($params->get('page_title', ''));
                $menu_item_show_page_heading = self::intValue($params->get('show_page_heading', 0));
                $menu_item_page_heading = self::stringValue($params->get('page_heading', ''));

                $menu_item_meta_description = self::stringValue($params->get('menu-meta_description', ''));
                $menu_item_meta_keywords = self::stringValue($params->get('menu-meta_keywords', ''));
                $menu_item_robots = self::stringValue($params->get('robots', ''));

                if ($menu_item_meta_description) {
                    $document->setMetaData('description', $menu_item_meta_description);
                }

                if ($menu_item_meta_keywords) {
                    $document->setMetaData('keywords', $menu_item_meta_keywords);
                }

                if ($menu_item_robots) {
                    $document->setMetaData('robots', $menu_item_robots);
                }

                $formname = self::stringValue($params->get('ff_com_name', ''));
                $page = self::intValue($params->get('ff_com_page', $page));
                $inframe = self::intValue($params->get('ff_com_frame', $inframe));
                $border = self::intValue($params->get('ff_com_border', $border));
                $align = self::intValue($params->get('ff_com_align', $align));
                $left = self::intValue($params->get('ff_com_left', $left));
                $top = self::intValue($params->get('ff_com_top', $top));
                $editable = self::intValue($params->get('ff_com_editable', $editable));
                $editable_override = self::intValue($params->get('ff_com_editable_override', $editable_override));
                $suffix = self::stringValue($params->get('ff_com_suffix', ''));
                $parprv = self::stringValue($params->get('ff_com_parprv', ''));
                $ff_request = array_replace(
                    $ff_request,
            $this->requestParameterParser->parse(self::stringValue($params->get('ff_com_parpub', '')))
                );
            }
        } // if
    }

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
        $query = $this->database->createQuery()
            ->select('*')
            ->from($this->database->quoteName('#__facileforms_forms'))
            ->where($this->database->quoteName('id') . ' = :formIdInt')
            ->where($this->database->quoteName('published') . ' = 1')
            ->bind(':formIdInt', $formIdInt, ParameterType::INTEGER);
        $this->database->setQuery($query);
        $forms = $this->loadFormRows();
        if (count($forms) < 1) {
            echo '[Form ' . intval($formid) . ' not found!]';
            $ok = false;
        } else
            $form = $forms[0];
    } else
        if ($formname != null) {
            $query = $this->database->createQuery()
                ->select('*')
                ->from($this->database->quoteName('#__facileforms_forms'))
                ->where($this->database->quoteName('name') . ' = :formname')
                ->where($this->database->quoteName('published') . ' = 1')
                ->order([$this->database->quoteName('ordering'), $this->database->quoteName('id')])
                ->bind(':formname', $formname, ParameterType::STRING);
            $this->database->setQuery($query);
            $forms = $this->loadFormRows();
            if (count($forms) < 1) {
                echo '[Form ' . htmlentities(self::stringValue($formname), ENT_QUOTES, 'UTF-8') . ' not found!]';
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
        $formName = self::formString($form, 'name');
        $formTitle = self::formString($form, 'title');
        $pluginEditableKey = 'ff_editablePlg' . $contentId . $formName;
        $pluginEditableOverrideKey = 'ff_editable_overridePlg' . $contentId . $formName;

        // set by plugin
        if ($session->get($pluginEditableKey, 0) != 0 && ($this->application->getInput()->getString('ff_applic', '') == 'plg_facileforms' || $ff_applic == 'plg_facileforms')) {
            $editable = self::intValue($session->get($pluginEditableKey, 0));
        }

        // set by plugin
        if ($session->get($pluginEditableOverrideKey, 0) != 0 && ($this->application->getInput()->getString('ff_applic', '') == 'plg_facileforms' || $ff_applic == 'plg_facileforms')) {
            $editable_override = self::intValue($session->get($pluginEditableOverrideKey, 0));
        }

        // set by module
        if (($this->application->getInput()->getString('ff_applic', '') == 'mod_facileforms' || $ff_applic == 'mod_facileforms')) {
            if ($this->application->getSession()->get('ff_editableMod' . $xModuleId . $formName, 0) != 0) {
                $editable = self::intValue($this->application->getSession()->get('ff_editableMod' . $xModuleId . $formName, 0));
            } else if ($this->application->getSession()->get('ff_editableMod' . $this->application->getInput()->getInt('ff_module_id', 0) . $formName, 0) != 0) {
                $editable = self::intValue($this->application->getSession()->get('ff_editableMod' . $this->application->getInput()->getInt('ff_module_id', 0) . $formName, 0));
            }
        }

        // set by module
        if (($this->application->getInput()->getString('ff_applic', '') == 'mod_facileforms' || $ff_applic == 'mod_facileforms')) {
            if ($this->application->getSession()->get('ff_editable_overrideMod' . $xModuleId . $formName, 0) != 0) {
                $editable_override = self::intValue($this->application->getSession()->get('ff_editable_overrideMod' . $xModuleId . $formName, 0));
            } else if ($this->application->getSession()->get('ff_editable_overrideMod' . $this->application->getInput()->getInt('ff_module_id', 0) . $formName, 0) != 0) {
                $editable_override = self::intValue($this->application->getSession()->get('ff_editable_overrideMod' . $this->application->getInput()->getInt('ff_module_id', 0) . $formName, 0));
            }
        }

        if ($ff_applic != 'plg_facileforms' && $pagetitle && $formTitle != '' && !($this->application->getInput()->getInt('cb_form_id', 0) || $this->application->getInput()->getCmd('cb_record_id', ''))) {
            if ($menu_item_title != '') {
                $document->setTitle($menu_item_title);
            } else { // being set by module, false implies no change at all
                $document->setTitle($formTitle);
            }
        }

        if ($formName == $formname)
            $ff_request = array_replace(
                $ff_request,
                $this->requestParameterParser->parse((string) $parprv)
            );
        if ($my_ff_params) {
            $inputValues = $this->application->getInput()->getArray();
            if (!is_array($inputValues)) {
                $inputValues = [];
            }
            foreach (array_keys($inputValues) as $prop) {
                if (str_starts_with((string) $prop, 'ff_param_')) {
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
            $formWidth = self::formString($form, 'width');
            $formWidthMode = self::formInt($form, 'widthmode');
            $formHeightMode = self::formInt($form, 'heightmode');
            $framewidth = 'width="' . htmlentities($formWidth . ($formWidthMode ? '%' : ''), ENT_QUOTES, 'UTF-8') . '" ';
            $frameheight = '';
            if (!$formHeightMode)
                $frameheight = 'height="' . htmlentities(self::formString($form, 'height'), ENT_QUOTES, 'UTF-8') . '" ';
            $url = rtrim(Uri::root(), '/') . '/index.php'
                . '?option=com_breezingformsng'
                . '&amp;Itemid=' . (($this->application->getInput()->getInt('Itemid', 0) > 0 && $this->application->getInput()->getInt('Itemid', 0) < 99999999) ? $this->application->getInput()->getInt('Itemid', 0) : 0)
                . '&amp;ff_form=' . htmlentities((string) self::formInt($form, 'id'), ENT_QUOTES, 'UTF-8')
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

            $formId = self::formInt($form, 'id');
            $params = 'id="ff_frame' . $formId . '" ' .
                'src="' . $url . '" ' .
                $framewidth .
                $frameheight .
                'frameborder="' . htmlentities((string) $border, ENT_QUOTES, 'UTF-8') . '" ' .
                'allowtransparency="true" ' .
                'scrolling="no" ';
            if (self::formInt($form, 'autoheight') === 1) {
                $document->getWebAssetManager()->useScript('jquery');
                RuntimeAssetLoader::script($this->application, Uri::root(true) . '/components/com_breezingformsng/libraries/jquery/jq.iframeautoheight.js');
                $document->getWebAssetManager()->addInlineScript("<!--
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
                echo \Vcmb\Component\BreezingformsNG\Site\Service\Support\PageHeading::html(
                    (string) $menu_item_title,
                    (string) $menu_item_page_heading,
                    $formTitle
                ) . "\n";
            }

            // process inline
            $myUser = $this->application->getIdentity()
                    ?? throw new \RuntimeException('The current user is not loaded.');

            $username = self::stringValue($myUser->get('username', ''));
            $query = $this->database->createQuery()
                ->select($this->database->quoteName('id'))
                ->from($this->database->quoteName('#__users'))
                ->where('LOWER(' . $this->database->quoteName('username') . ') = LOWER(:username)')
                ->bind(':username', $username, ParameterType::STRING);
            $this->database->setQuery($query);
            $id = $this->database->loadResult();
            if ($id)
                $myUser->get('id', -1);
            require_once JPATH_SITE . '/components/com_breezingformsng/src/Support/processor_facade.php';
            $templateCode = self::formString($form, 'template_code');
            $templateCodeProcessed = self::formString($form, 'template_code_processed');
            $formWidth = self::formString($form, 'width');
            $formWidthMode = self::formInt($form, 'widthmode');
            $formHeightMode = self::formInt($form, 'heightmode');
            $formHeight = self::formString($form, 'height');
            $formId = self::formInt($form, 'id');
            if ($task == 'view') {
                $div1style = '';
                $div2style = '';
                if ($templateCode == '') {
                    $fullwidth = $formWidthMode && self::intValue($formWidth) >= 100;
                    if ($formWidthMode) {
                        $div1style .= 'min-width:10px;';
                        $div2style .= 'min-width:10px;';
                    } // if
                    $div2style .= 'width:' . htmlentities(($fullwidth ? '100' : $formWidth) . ($formWidthMode ? '%' : 'px'), ENT_QUOTES, 'UTF-8') . ';';
                    if (!$formHeightMode)
                        $div2style .= 'height:' . htmlentities($formHeight, ENT_QUOTES, 'UTF-8') . 'px;';
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
                if (trim($templateCodeProcessed) !== 'QuickMode') {
                    echo '<div class="alert alert-warning">' . Text::_('COM_BREEZINGFORMSNG_QUICKMODE_ONLY') . '</div>';
                    return;
                }

                $bfStyle = '';
                if ($templateCode == '') {
                    $bfStyle = ' style="' . $div1style . '"';
                }
                if (!$plainform)
                    echo '<div class="bfClearfix"' . $bfStyle . '>' . "\n";
            } // if task = view
            if ($left > 3)
                $align = $left;
            // remove temporary flash and chunked upload files if any
            $temporaryPath = self::stringValue($this->application->get('tmp_path'));
            $temporaryUploads = new TemporaryUploadDirectory($temporaryPath);
            $this->uploadFileCleaner->purgeTemporaryUploads($temporaryUploads->path(), '_flashtmp');
            $this->uploadFileCleaner->purgeTemporaryUploads($temporaryUploads->chunksPath(), '_chunktmp');
                    PaymentNotificationCache::fromTemporaryPath(
                        $temporaryPath,
                        JPATH_SITE . '/media/breezingforms/payment_cache/'
                    )->purgeExpired();

            $ff_processor = new HTML_facileFormsProcessor(
                $this->application,
                $this->database,
                $this->mailerFactory,
                $this->cacheControllerFactory,
                $runmode,
                $inframe,
                $formId,
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

                if (trim($templateCodeProcessed) == '') {
                    echo "</div>\n";
                }
                if (!$plainform)
                    echo "</div>\n";

                ob_end_flush();
            } // if
        } // if
    } // if
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function loadFormRows(): array
    {
        $rows = $this->database->loadAssocList();
        if (!is_array($rows)) {
            return [];
        }

        $forms = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $form = [];
            foreach ($row as $key => $value) {
                if (is_string($key)) {
                    $form[$key] = $value;
                }
            }
            $forms[] = $form;
        }

        return $forms;
    }

    /**
     * @param array<string, mixed> $form
     */
    private static function formString(array $form, string $key): string
    {
        return self::stringValue($form[$key] ?? '');
    }

    /**
     * @param array<string, mixed> $form
     */
    private static function formInt(array $form, string $key): int
    {
        return self::intValue($form[$key] ?? 0);
    }

    private static function intValue(mixed $value): int
    {
        if (is_int($value)) {
            return $value;
        }
        if (is_bool($value)) {
            return $value ? 1 : 0;
        }
        if (is_float($value)) {
            return (int) $value;
        }

        return is_string($value) && is_numeric($value) ? (int) $value : 0;
    }

    private static function stringValue(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }

        return is_int($value) || is_float($value) || is_bool($value) ? (string) $value : '';
    }
}
