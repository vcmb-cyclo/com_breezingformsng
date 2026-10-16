<?php
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

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Site\Service\Scripting;

\defined('_JEXEC') or die;

use Error;
use HTML_facileFormsProcessor;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
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
use CB\Component\Contentbuilderng\Administrator\Helper\ContentbuilderngHelper;
use CB\Component\Contentbuilderng\Administrator\Helper\FormSourceFactory;
use CB\Component\Contentbuilderng\Administrator\Service\ArticleService;
use CB\Component\Contentbuilderng\Administrator\Service\ListSupportService;
use CB\Component\Contentbuilderng\Administrator\Service\PermissionService;

/**
 * Pieces, scripts, query columns, builtin JS library and code linking.
 */
final class ScriptingEngine
{
    private ?ScriptingRuntime $scriptingRuntimeService = null;

    public function __construct(private readonly HTML_facileFormsProcessor $processor)
    {
    }

    /**
     * @param int|string $id
     * @param mixed $name
     * @return string
     */
    function getPieceById($id, $name = null)
    {
        if ($this->processor->dying)
            return '';

        $piece = $this->scriptingRuntime()->findPieceById((int) $id);

        if ($piece === null) {
            return '';
        }

        $name = $piece->name;

        return $piece->code;
    }

    // getPieceById

    /**
     * @param string $name
     * @param mixed $id
     * @return string
     */
    function getPieceByName($name, $id = null)
    {
        if ($this->processor->dying)
            return '';
        $piece = $this->scriptingRuntime()->findPieceByName((string) $name);

        if ($piece === null) {
            return '';
        }

        $id = $piece->id;

        return $piece->code;
    }

    // getPieceByName

    /**
     * @phpstan-impure
     * @param string $code
     * @param string $name
     * @param string|null $type
     * @param int|string|null $id
     * @param int|null $pane
     * @return mixed
     */
    function execPiece($code, $name, $type, $id, $pane)
    {
        $ret = '';
        if ($this->processor->prepareEvalCode($code, $name, $type, $id, $pane)) {
            $this->processor->traceEval($name);
            try {
                $ret = $this->scriptingRuntime()->executePiece($this->processor, $code, $name, $type, $id, $pane);
            } catch (Error $e) {
                $this->processor->app->enqueueMessage($e->getMessage() . " in $name.", 'error');
                if ($this->processor->app->getConfig()->get('debug', false)) {
                    Log::add( "PHP piece '$name' : " .$e->getMessage(), Log::DEBUG, 'BF Piece');
                }
            }
        } // if
        return $ret;
    }

    // execPiece

    /**
     * @param int|string $id
     * @return mixed
     */
    function execPieceById($id)
    {
        $name = null;
        $code = $this->getPieceById($id, $name);
        return $this->execPiece($code, Text::_('COM_BREEZINGFORMSNG_PROCESS_PIECE') . " $name", 'p', $id, null);
    }

    // execPieceById

    /**
     * @param string $name
     * @return mixed
     */
    function execPieceByName($name)
    {
        $id = null;
        $code = $this->getPieceByName($name, $id);
        return $this->execPiece($code, Text::_('COM_BREEZINGFORMSNG_PROCESS_PIECE') . " $name", 'p', $id, null);
    }

    // execPieceByName

    /**
     * @param string $code
     * @param string $name
     * @param string|null $type
     * @param int|string|null $id
     * @param int|null $pane
     * @return string
     */
    function replaceCode($code, $name, $type, $id, $pane)
    {

        if ($this->processor->dying)
            return '';
        $p1 = 0;
        $l = strlen($code);
        $c = '';
        $n = 0;
        while ($p1 < $l) {
            $p2 = strpos($code, '<?php', $p1);
            if ($p2 === false)
                $p2 = $l;
            $c .= substr($code, $p1, $p2 - $p1);
            $p1 = $p2;
            if ($p1 < $l) {
                $p1 += 5;
                $p2 = strpos($code, '?>', $p1);
                if ($p2 === false)
                    $p2 = $l;
                $n++;
                $c .= $this->execPiece(substr($code, $p1, $p2 - $p1), $name . "[$n]", $type, $id, $pane);
                if ($this->processor->dying)
                    return '';
                $p1 = $p2 + 2;
            } // if
        } // while
        return str_replace($this->processor->findtags, $this->processor->replacetags, $c);
    }

    // replaceCode

    /**
     * @param object $elem
     * @param object $coldef
     * @return mixed
     */
    function compileQueryCol(&$elem, &$coldef)
    {
        $coldef->comp = array();
        $columnValue = str_replace($this->processor->findtags, $this->processor->replacetags, $coldef->value);
        if ($this->processor->trim($columnValue)) {
            $c = $p1 = 0;
            $l = strlen($coldef->value);
            while ($p1 < $l) {
                $p2 = strpos($coldef->value, '<?php', $p1);
                if ($p2 === false)
                    $p2 = $l;
                $coldef->comp[$c] = array(
                    false,
                    str_replace(
                        $this->processor->findtags,
                        $this->processor->replacetags,
                        trim(substr($coldef->value, $p1, $p2 - $p1))
                    )
                );
                if ($this->processor->trim($coldef->comp[$c][1]))
                    $c++;
                $p1 = $p2;
                if ($p1 < $l) {
                    $p1 += 5;
                    $p2 = strpos($coldef->value, '?>', $p1);
                    if ($p2 === false)
                        $p2 = $l;
                    $coldef->comp[$c] = array(true, substr($coldef->value, $p1, $p2 - $p1));
                    if (
                        $this->processor->prepareEvalCode(
                            $coldef->comp[$c][1],
                            Text::_('COM_BREEZINGFORMSNG_PROCESS_QVALUEOF') . " " . $elem->name . "::" . $coldef->name,
                            'e',
                            $elem->id,
                            2
                        )
                    )
                        $c++;
                    $p1 = $p2 + 2;
                } // if
            } // while
            if ($c > count($coldef->comp))
                array_pop($coldef->comp);
        } // if non-empty
    }

    // compileQueryCol

    /**
     * @param string $code
     * @param object $elem
     * @param object $row
     * @param object $coldef
     * @param mixed $value
     * @return mixed
     */
    function execQueryValue($code, &$elem, &$row, &$coldef, $value)
    {
        $this->processor->traceEval(Text::_('COM_BREEZINGFORMSNG_PROCESS_QVALUEOF') . " " . $elem->name . "::" . $coldef->name);
        try {
            return $this->scriptingRuntime()->executeQueryValue(
                $this->processor,
                $code,
                $elem,
                $row,
                $coldef,
                $value
            );
        } catch (Error $e) {
            if ($this->processor->app->getConfig()->get('debug', false)) {
                $this->processor->app->enqueueMessage($e->getMessage() . " in {$elem->name}.", 'error');
                Log::add("Piece PHP '{$elem->name}' invalid :" . $e->getMessage(), Log::DEBUG, 'BF Piece');
            }
        }
    }

    // execQueryValue

    /**
     * @param object $elem
     * @param array<int, mixed> $valrows
     * @param array<int, object> $coldefs
     * @return mixed
     */
    function execQuery(&$elem, &$valrows, &$coldefs)
    {
        $ret = null;
        $code = $elem->data2;
        if ($this->processor->prepareEvalCode($code, Text::_('COM_BREEZINGFORMSNG_PROCESS_QPIECEOF') . " " . $elem->name, 'e', $elem->id, 1)) {
            $rows = [];
            $this->processor->traceEval(Text::_('COM_BREEZINGFORMSNG_PROCESS_QPIECEOF') . " " . $elem->name);

            try {
                $rows = $this->scriptingRuntime()->executeQuery(
                    $this->processor,
                    $code,
                    $elem,
                    $valrows,
                    $coldefs
                );
            } catch (Error $e) {
                if ($this->processor->app->getConfig()->get('debug', false)) {
                    $this->processor->app->enqueueMessage($e->getMessage() . " in {$elem->name}.", 'error');
                    Log::add("PHP piece '{$elem->name}' : " . $e->getMessage(), Log::DEBUG, 'BF Piece');
                }
            }

            $rcnt = count($rows);
            $ccnt = count($coldefs);
            $valrows = array();
            for ($r = 0; $r < $rcnt; $r++) {
                $row = &$rows[$r];
                $valrow = array();
                for ($c = 0; $c < $ccnt; $c++) {
                    $coldef = &$coldefs[$c];
                    $cname = $coldef->name;
                    $value = isset($row->$cname) ? str_replace($this->processor->findtags, $this->processor->replacetags, $row->$cname) : '';
                    $xcnt = count($coldef->comp);
                    if (!$xcnt)
                        $valrow[] = $value;
                    else {
                        $val = '';
                        for ($x = 0; $x < $xcnt; $x++) {
                            $val .= $coldef->comp[$x][0] ? $this->execQueryValue($coldef->comp[$x][1], $elem, $row, $coldef, $value) : $coldef->comp[$x][1];
                            if ($this->processor->dying)
                                break;
                        } // for
                        $valrow[] = str_replace($this->processor->findtags, $this->processor->replacetags, $val);
                    } // if
                    unset($coldef);
                    if ($this->processor->dying)
                        break;
                } // for
                $valrows[] = $valrow;
                unset($row);
                if ($this->processor->dying)
                    break;
            } // for
            $rows = null;
        } // if
    }

    // execQuery

    /**
     * @param object $row
     * @return string
     */
    function script2clause(&$row)
    {
        if ($this->processor->dying)
            return '';
        
        $funcname = '';
        switch ($row->script2cond) {
            case 1:
                    $funcname = $this->scriptingRuntime()
                        ->findScriptById((int) $row->script2id)->name ?? '';
                break;
            case 2:
                $funcname = 'ff_' . $row->name . '_action';
                break;
            default:
                break;
        } // switch
        $attribs = '';
        if ($funcname != '') {
            if ($row->script2flag1)
                $attribs .= ' onclick="' . $funcname . '(this,\'click\');"';
            if ($row->script2flag2)
                $attribs .= ' onblur="' . $funcname . '(this,\'blur\');"';
            if ($row->script2flag3)
                $attribs .= ' onchange="' . $funcname . '(this,\'change\');"';
            if ($row->script2flag4)
                $attribs .= ' onfocus="' . $funcname . '(this,\'focus\');"';
            if ($row->script2flag5)
                $attribs .= ' onselect="' . $funcname . '(this,\'select\');"';
        } // if
        return $attribs;
    }

    // script2clause

    /**
     * @param array<int, array<int, mixed>> $library
     * @return mixed
     */
    function loadBuiltins(&$library)
    {
        global $ff_config, $ff_request;
        if ($this->processor->dying)
            return;
        $library[] = array('FF_STATUS_OK', 'var FF_STATUS_OK = ' . _FF_STATUS_OK . ';');
        $library[] = array('FF_STATUS_UNPUBLISHED', 'var FF_STATUS_UNPUBLISHED = ' . _FF_STATUS_UNPUBLISHED . ';');
        $library[] = array('FF_STATUS_SAVERECORD_FAILED', 'var FF_STATUS_SAVERECORD_FAILED = ' . _FF_STATUS_SAVERECORD_FAILED . ';');
        $library[] = array('FF_STATUS_SAVESUBRECORD_FAILED', 'var FF_STATUS_SAVESUBRECORD_FAILED = ' . _FF_STATUS_SAVESUBRECORD_FAILED . ';');
        $library[] = array('FF_STATUS_UPLOAD_FAILED', 'var FF_STATUS_UPLOAD_FAILED = ' . _FF_STATUS_UPLOAD_FAILED . ';');
        $library[] = array('FF_STATUS_SENDMAIL_FAILED', 'var FF_STATUS_SENDMAIL_FAILED = ' . _FF_STATUS_SENDMAIL_FAILED . ';');
        $library[] = array('FF_STATUS_ATTACHMENT_FAILED', 'var FF_STATUS_ATTACHMENT_FAILED = ' . _FF_STATUS_ATTACHMENT_FAILED . ';');

        $library[] = array('ff_homepage', "var ff_homepage = '" . $this->processor->homepage . "';");
        $library[] = array('ff_currentpage', "var ff_currentpage = " . $this->processor->page . ";");
        $library[] = array('ff_lastpage', "var ff_lastpage = " . $this->processor->formrow->pages . ";");
        $library[] = array('ff_images', "var ff_images = '" . $this->processor->images . "';");
        $library[] = array('ff_validationFocusName', "var ff_validationFocusName = '';");
        $library[] = array('ff_currentheight', "var ff_currentheight = 0;");

        $code = "var ff_elements = [" . nl();
        for ($i = 0; $i < $this->processor->rowcount; $i++) {
            $row = $this->processor->rows[$i];
            $endline = "," . nl();
            if ($i == $this->processor->rowcount - 1)
                $endline = nl();
            switch ($row->type) {
                case "Hidden Input":
                    $code .= "    ['ff_elem" . $row->id . "', 'ff_elem" . $row->id . "', '" . $row->name . "', " . $row->page . ", " . $row->id . "]" . $endline;
                    break;
                case "Static Text":
                case "Rectangle":
                case "Tooltip":
                case "Icon":
                    $code .= "    ['ff_div" . $row->id . "', 'ff_div" . $row->id . "', '" . $row->name . "', " . $row->page . ", " . $row->id . "]" . $endline;
                    break;
                default:
                    $code .= "    ['ff_elem" . $row->id . "', 'ff_div" . $row->id . "', '" . $row->name . "', " . $row->page . ", " . $row->id . "]" . $endline;
            } // switch
        } // for
        $code .= "];";
        $library[] = array('ff_elements', $code);

        $code = "var ff_param = new Object();";
        // reset($ff_request);
        foreach ($ff_request as $prop => $val) {
            if (substr($prop, 0, 9) == 'ff_param_')
                $code .= nl() . "ff_param." . substr($prop, 9) . " = '" . $val . "';";
        }
        // while (list($prop, $val) = each($ff_request))

        $library[] = array('ff_param', $code);

        $library[] = array(
            'ff_getElementByIndex',
            "function ff_getElementByIndex(index)" . nl() .
            "{" . nl() .
            "    if (index >= 0 && index < ff_elements.length)" . nl() .
            "        return eval('document." . $this->processor->form_id . ".'+ff_elements[index][0]);" . nl() .
            "    return null;" . nl() .
            "} // ff_getElementByIndex"
        );

        $library[] = array(
            'ff_getElementByName',
            "function ff_getElementByName(name)" . nl() .
            "{" . nl() .
            "    if (name.substr(0,6) == 'ff_nm_') name = name.substring(6,name.length-2);" . nl() .
            "    for (var i = 0; i < ff_elements.length; i++)" . nl() .
            "        if (ff_elements[i][2]==name)" . nl() .
            "            return eval('document." . $this->processor->form_id . ".'+ff_elements[i][0]);" . nl() .
            "    return null;" . nl() .
            "} // ff_getElementByName"
        );

        $library[] = array(
            'ff_getPageByName',
            "function ff_getPageByName(name)" . nl() .
            "{" . nl() .
            "    if (name.substr(0,6) == 'ff_nm_') name = name.substring(6,name.length-2);" . nl() .
            "    for (var i = 0; i < ff_elements.length; i++)" . nl() .
            "        if (ff_elements[i][2]==name)" . nl() .
            "            return ff_elements[i][3];" . nl() .
            "    return 0;" . nl() .
            "} // ff_getPageByName"
        );

        $library[] = array(
            'ff_getDivByName',
            "function ff_getDivByName(name)" . nl() .
            "{" . nl() .
            "    if (name.substr(0,6) == 'ff_nm_') name = name.substring(6,name.length-2);" . nl() .
            "    for (var i = 0; i < ff_elements.length; i++)" . nl() .
            "        if (ff_elements[i][2]==name)" . nl() .
            "            return document.getElementById(ff_elements[i][1]);" . nl() .
            "    return null;" . nl() .
            "} // ff_getDivByName"
        );

        $library[] = array(
            'ff_getIdByName',
            "function ff_getIdByName(name)" . nl() .
            "{" . nl() .
            "    if (name.substr(0,6) == 'ff_nm_') name = name.substring(6,name.length-2);" . nl() .
            "    for (var i = 0; i < ff_elements.length; i++)" . nl() .
            "        if (ff_elements[i][2]==name)" . nl() .
            "            return ff_elements[i][4];" . nl() .
            "    return null;" . nl() .
            "} // ff_getIdByName"
        );

        $library[] = array(
            'ff_getForm',
            "function ff_getForm()" . nl() .
            "{" . nl() .
            "    return document." . $this->processor->form_id . ";" . nl() .
            "} // ff_getForm"
        );

        $code = "function ff_submitForm()" . nl() .
            "{if(document.getElementById('bfSubmitButton')){document.getElementById('bfSubmitButton').disabled = true;} if(typeof JQuery != 'undefined'){JQuery('.bfCustomSubmitButton').prop('disabled', true);} bfCheckCaptcha();}" . nl();
        $code .= "function ff_submitForm2()" . nl() .
            "{if(document.getElementById('bfSubmitButton')){document.getElementById('bfSubmitButton').disabled = true;} if(typeof JQuery != 'undefined'){JQuery('.bfCustomSubmitButton').prop('disabled', true);} " . nl();
        if ($this->processor->inline)
            $code .= " if(typeof bf_ajax_submit != 'undefined') { bf_ajax_submit() } else { submitform('submit'); }" . nl();
        else
            $code .= " if(typeof bf_ajax_submit != 'undefined') { bf_ajax_submit() } else { document." . $this->processor->form_id . ".submit(); }" . nl();
        $code .= "} // ff_submitForm";
        $library[] = array('ff_submitForm', $code);

        $library[] = array(
            'ff_validationFocus',
            "function ff_validationFocus(name)" . nl() .
            "{" . nl() .
            "    if (name==undefined || name=='') {" . nl() .
            "        // set focus if name of first failing element was set" . nl() .
            "        if (ff_validationFocusName!='') {" . nl() .
            "            ff_switchpage(ff_getPageByName(ff_validationFocusName));" . nl() .
            "            if(ff_getElementByName(ff_validationFocusName).focus){" . nl() .
            "	            ff_getElementByName(ff_validationFocusName).focus();" . nl() .
            "			 }" . nl() .
            "        } // if" . nl() .
            "    } else {" . nl() .
            "        // store name if this is the first failing element" . nl() .
            "        if (ff_validationFocusName=='')" . nl() .
            "            ff_validationFocusName = name;" . nl() .
            "    } // if" . nl() .
            "} // ff_validationFocus"
        );

        $code = "function ff_validation(page)" . nl() .
            "{" . nl() .
            "    if(typeof inlineErrorElements != 'undefined') inlineErrorElements = new Array();" . nl() .
            "    error = '';" . nl() .
            "    ff_validationFocusName = '';" . nl();
        $curr = -1;
        for ($i = 0; $i < $this->processor->rowcount; $i++) {
            $row = $this->processor->rows[$i];
            $funcname = '';
            switch ($row->script3cond) {
                case 1:
                    $funcname = $this->scriptingRuntime()
                        ->findScriptById((int) $row->script3id)->name ?? '';
                    break;
                case 2:
                    $funcname = 'ff_' . $row->name . '_validation';
                    break;
                default:
                    break;
            } // switch
            if ($funcname != '') {
                if ($row->page != $curr) {
                    if ($curr > 0)
                        $code .= "    } // if" . nl();
                    $code .= "    if (page==" . $row->page . " || page==0) {" . nl();
                    $curr = $row->page;
                } // if
                if ($this->processor->trim($row->script3msg)) {
                    $message = (string) $row->script3msg;
                    $res_msg = '';
                    $this->processor->getFieldTranslated('validationMessage', $row->name, $res_msg);
                    if ($res_msg != '') {
                        $message = $res_msg;
                    }
                    $msg = json_encode(
                        $message . "\n",
                        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE
                    );
                } else {
                    $msg = '""';
                }
                $code .= " if( typeof bfDeactivateField == 'undefined' || !bfDeactivateField['ff_nm_" . $row->name . "[]'] ){ " . nl();
                $code .= "        errorout = " . $funcname . "(document." . $this->processor->form_id . "['ff_nm_" . $row->name . "[]']," . $msg . ");" . nl();
                $code .= "        error += errorout" . nl();
                $code .= "        if(typeof inlineErrorElements != 'undefined'){" . nl();
                $code .= "             inlineErrorElements.push([\"" . $row->name . "\",errorout]);" . nl();
                $code .= "        }" . nl();
                $code .= "}" . nl();
            } // if
        } // for
        if ($curr > 0)
            $code .= "    } // if" . nl();
        $code .= 'if(error != "" && document.getElementById(\'ff_capimgValue\')){
                 document.getElementById(\'ff_capimgValue\').src = \'' . Uri::root(true) . ($this->processor->app->isClient('administrator') ? '/administrator' : '') . '/index.php?option=com_breezingformsng&bfCaptcha=1&bfMathRandom=\' + Math.random();
                 document.getElementById(\'bfCaptchaEntry\').value = "";
            }';
        $code .= 'if(error!="" && document.getElementById("bfSubmitButton")){document.getElementById("bfSubmitButton").disabled = false;}' . nl();
        $code .= 'if(error!="" && typeof JQuery != "undefined"){JQuery(".bfCustomSubmitButton").prop("disabled", false);}' . nl();
        $code .= "    return error;" . nl() .
            "} // ff_validation";
        $library[] = array('ff_validation', $code);

        // ff_initialize
        $code = "function ff_initialize(condition)" . nl() .
            "{" . nl();
        $formentry = false;
        $funcname = '';
        switch ($this->processor->formrow->script1cond) {
            case 1:
                $funcname = $this->scriptingRuntime()
                    ->findScriptById((int) $this->processor->formrow->script1id)->name ?? '';
                break;
            case 2:
                $funcname = 'ff_' . $this->processor->formrow->name . '_init';
                break;
            default:
                break;
        } // switch
        if ($funcname != '') {
            $code .= "    if (condition=='formentry') {" . nl() .
                "        " . $funcname . "();" . nl();
            $formentry = true;
        } // if
        for ($i = 0; $i < $this->processor->rowcount; $i++) {
            $row = $this->processor->rows[$i];
            $funcname = '';
            switch ($row->script1cond) {
                case 1:
                    $funcname = $this->scriptingRuntime()
                        ->findScriptById((int) $row->script1id)->name ?? '';
                    break;
                case 2:
                    $funcname = 'ff_' . $row->name . '_init';
                    break;
                default:
                    break;
            } // switch
            if ($funcname != '') {
                if ($row->script1flag1) {
                    if (!$formentry) {
                        $code .= "    if (condition=='formentry') {" . nl();
                        $formentry = true;
                    } // if
                    $code .= "        " . $funcname . "(document." . $this->processor->form_id . "['ff_nm_" . $row->name . "[]'], condition);" . nl();
                } // if
            } // if
        } // for
        $pageentry = false;
        $curr = -1;
        for ($i = 0; $i < $this->processor->rowcount; $i++) {
            $row = $this->processor->rows[$i];
            $funcname = '';
            switch ($row->script1cond) {
                case 1:
                    $funcname = $this->scriptingRuntime()
                        ->findScriptById((int) $row->script1id)->name ?? '';
                    break;
                case 2:
                    $funcname = 'ff_' . $row->name . '_init';
                    break;
                default:
                    break;
            } // switch
            if ($funcname != '') {
                if ($row->script1flag2) { // page entry
                    if ($formentry) {
                        $code .= "    } else" . nl();
                        $formentry = false;
                    } // if
                    if (!$pageentry) {
                        $code .= "    if (condition=='pageentry') {" . nl();
                        $pageentry = true;
                    } // if
                    if ($curr != $row->page) {
                        if ($curr > 0)
                            $code .= "        } // if" . nl();
                        $code .= "        if (ff_currentpage==" . $row->page . ") {" . nl();
                        $curr = $row->page;
                    } // if
                    $code .= "            " . $funcname . "(document." . $this->processor->form_id . ".ff_elem" . $row->id . ", condition);" . nl();
                } // if
            } // if
        } // for
        if ($curr > 0)
            $code .= "        } // if" . nl();
        if ($formentry || $pageentry)
            $code .= "    } // if" . nl();
        $code .= "} // ff_initialize";
        $library[] = array('ff_initialize', $code);

        if ($this->processor->showgrid) {
            if ($this->processor->formrow->widthmode)
                $width = $this->processor->formrow->prevwidth;
            else
                $width = $this->processor->formrow->width;
            $library[] = array(
                'ff_showgrid',
                "var ff_gridvcnt = 0;" . nl() .
                "var ff_gridhcnt = 0;" . nl() .
                "var ff_gridheight = " . $this->processor->formrow->height . ";" . nl() .
                nl() .
                "function ff_showgrid()" . nl() .
                "{" . nl() .
                "   var i, e, s;" . nl() .
                "   var hcnt = parseInt(ff_gridheight / " . $ff_config->gridsize . ")+1;" . nl() .
                "   var vcnt = parseInt(" . $width . " / " . $ff_config->gridsize . ")+1;" . nl() .
                "   var formdiv = document.getElementById('ff_formdiv" . $this->processor->form . "');" . nl() .
                "   var firstelem = formdiv.firstChild;" . nl() .
                "   for (i = ff_gridhcnt; i < hcnt; i++) {" . nl() .
                "       e = document.createElement('div');" . nl() .
                "       e.id = 'ff_gridh'+i;" . nl() .
                "       s = e.style;" . nl() .
                "       s.position = 'absolute';" . nl() .
                "       s.left = '0px';" . nl() .
                "       s.top = (i*" . $ff_config->gridsize . ")+'px';" . nl() .
                "       s.width = '" . $width . "px';" . nl() .
                "       s.fontSize = '0px';" . nl() .
                "       s.lineHeight = '1px';" . nl() .
                "       s.height = '1px';" . nl() .
                "       if (i % 2)" . nl() .
                "           s.background = '" . $ff_config->gridcolor2 . "';" . nl() .
                "       else" . nl() .
                "           s.background = '" . $ff_config->gridcolor1 . "';" . nl() .
                "       formdiv.insertBefore(e,firstelem);" . nl() .
                "   } // for" . nl() .
                "   if (hcnt > ff_gridhcnt) ff_gridhcnt = hcnt;" . nl() .
                "   for (i = 0; i < ff_gridvcnt; i++)" . nl() .
                "       document.getElementById('ff_gridv'+i).style.height = ff_gridheight+'px';" . nl() .
                "   for (i = ff_gridvcnt; i < vcnt; i++) {" . nl() .
                "       e = document.createElement('div');" . nl() .
                "       e.id = 'ff_gridv'+i;" . nl() .
                "       s = e.style;" . nl() .
                "       s.position = 'absolute';" . nl() .
                "       s.left = (i*" . $ff_config->gridsize . ")+'px';" . nl() .
                "       s.top = '0px';" . nl() .
                "       s.width = '1px';" . nl() .
                "       s.height = ff_gridheight+'px';" . nl() .
                "       if (i % 2)" . nl() .
                "           s.background = '" . $ff_config->gridcolor2 . "';" . nl() .
                "       else" . nl() .
                "           s.background = '" . $ff_config->gridcolor1 . "';" . nl() .
                "       formdiv.insertBefore(e,firstelem);" . nl() .
                "   } // for" . nl() .
                "   if (vcnt > ff_gridvcnt) ff_gridvcnt = vcnt;" . nl() .
                "} // ff_showgrid"
            );
        } // if
        // ff_resizePage
        $code = "function ff_resizepage(mode, value)" . nl() .
            "{" . nl() .
            "    var height = 0;" . nl() .
            "    if (mode > 0) {" . nl() .
            "        for (var i = 0; i < ff_elements.length; i++) {" . nl() .
            "            if (mode==2 || ff_elements[i][3]==ff_currentpage) {" . nl() .
            "                e = document.getElementById(ff_elements[i][1]);" . nl() .
            "                if(e){" . nl() .
            "                	h = e.offsetTop+e.offsetHeight;" . nl() .
            "                	if (h > height) height = h;" . nl() .
            "                }" . nl() .
            "            } // if" . nl() .
            "        } // for" . nl() .
            "    } // if" . nl() .
            "    var totheight = height+value;" . nl() .
            "    if ((mode==2 && totheight>ff_currentheight) || (mode!=2 && totheight!=ff_currentheight)) {" . nl();
        if ($this->processor->inframe) {
            $fn = ($this->processor->runmode == _FF_RUNMODE_PREVIEW) ? 'ff_prevframe' : ('ff_frame' . $this->processor->form);
            $code .= "        parent.document.getElementById('" . $fn . "').style.height = totheight+'px';" . nl() .
                "        parent.window.scrollTo(0,0);" . nl() .
                "        document.getElementById('ff_formdiv" . $this->processor->form . "').style.height = height+'px';" . nl() .
                "        window.scrollTo(0,0);" . nl();
        } // if
        else
            $code .= "        document.getElementById('ff_formdiv" . $this->processor->form . "').style.height = totheight+'px';" . nl() .
                "        window.scrollTo(0,0);" . nl();
        $code .= "        ff_currentheight = totheight;" . nl();
        if ($this->processor->showgrid) {
            $code .= "        ff_gridheight = totheight;" . nl() .
                "        ff_showgrid();" . nl();
        } // if
        $code .= "    } // if" . nl() .
            "} // ff_resizepage";
        $library[] = array('ff_resizepage', $code);

        if ($this->processor->formrow->template_code_processed == '') {

            // ff_switchpage
            $code = "function ff_switchpage(page)" . nl() .
                "{;" . nl() .
                "    if (page>=1 && page<=ff_lastpage && page!=ff_currentpage) {" . nl() .
                "        vis = 'visible';" . nl();
            $curr = -1;
            for ($i = 0; $i < $this->processor->rowcount; $i++) {
                $row = $this->processor->rows[$i];
                if ($row->type != "Hidden Input") {
                    if ($row->page != $curr) {
                        if ($curr >= 1)
                            $code .= "        } // if" . nl();
                        $code .= "        if (page==" . $row->page . " || ff_currentpage==" . $row->page . ") {" . nl() .
                            "            if (page==" . $row->page . ") vis = 'visible';  else vis = 'hidden';" . nl();
                        $curr = $row->page;
                    } // if
                    $code .= "            document.getElementById('ff_div" . $row->id . "').style.visibility=vis;" . nl();
                } // if
            } // for
            if ($curr >= 1)
                $code .= "        } // if" . nl();
            $code .= "        ff_currentpage = page;" . nl();
            if ($this->processor->formrow->heightmode == 1)
                $code .= "        ff_resizepage(" . $this->processor->formrow->heightmode . ", " . $this->processor->formrow->height . ");" . nl();
            $code .= "        ff_initialize('pageentry');" . nl() .
                "    } // if" . nl() .
                "} // ff_switchpage";
        } else {
            $visPages = '';
            $pagesSize = isset($this->processor->formrow->pages) ? intval($this->processor->formrow->pages) : 1;
            for ($pageCnt = 1; $pageCnt <= $pagesSize; $pageCnt++) {
                $visPages .= 'if(document.getElementById("bfPage' . $pageCnt . '"))document.getElementById("bfPage' . $pageCnt . '").style.display = "none";';
            }

            $code = 'function ff_switchpage(page){
				' . $visPages . '
				if(document.getElementById("bfPage"+page))document.getElementById("bfPage"+page).style.display = "";
				ff_currentpage = page;
				' . ($this->processor->formrow->heightmode == 1 ? "ff_resizepage(" . $this->processor->formrow->heightmode . ", " . $this->processor->formrow->height . ");" : "") . '
				ff_initialize("pageentry");
			}';
        }

        $library[] = array('ff_switchpage', $code);
    }

    // loadBuiltins

    /**
     * @param array<int, array<int, mixed>> $library
     * @return mixed
     */
    function loadScripts(&$library)
    {
        if ($this->processor->dying)
            return;
        foreach ($this->scriptingRuntime()->publishedScripts() as $script) {
            $library[] = [trim($script->name), $script->code, 's', $script->id, null];
        }
    }

    // loadScripts

    /**
     * @param mixed $str
     * @return string
     */
    function compressJavascript($str)
    {
        if ($this->processor->dying)
            return '';
        return $this->scriptingRuntime()->compress((string) $str, _FF_PACKBREAKAFTER, nl());
    }

    private function scriptingRuntime(): ScriptingRuntime
    {
        return $this->scriptingRuntimeService ??= new ScriptingRuntime($this->processor->database);
    }

    // compressJavascript

    /**
     * @phpstan-impure
     * @param string $func
     * @param array<int, array<int, mixed>> $library
     * @param array<int, string> $linked
     * @param string $code
     * @param string|null $type
     * @param int|string|null $id
     * @param int|null $pane
     * @return mixed
     */
    function linkcode($func, &$library, &$linked, $code, $type = null, $id = null, $pane = null)
    {
        global $ff_config;

        if ($this->processor->dying)
            return;
        if ($func != '#scanonly') {
            // check if function allready linked
            if (in_array($func, $linked))
                return;
            // remember me
            $linked[] = $func;
        } // if
        // scan the code for library identifiers
        preg_match_all("/[A-Za-z0-9_]+/s", $code, $matches, PREG_PATTERN_ORDER);
        $idents = $matches[0];
        $cnt = count($library);
        for ($i = 0; $i < $cnt; $i++) {
            $libname = $library[$i][0];
            if ($libname != '' && in_array($libname, $idents)) {
                $library[$i][0] = ''; // invalidate
                $ltype = $lid = $lpane = null;
                if (count($library[$i]) > 4) {
                    $ltype = $library[$i][2];
                    $lid = $library[$i][3];
                    $lpane = $library[$i][4];
                } // if
                $this->linkcode($libname, $library, $linked, $library[$i][1], $ltype, $lid, $lpane);
                if ($this->processor->dying)
                    return '';
            } // if
        } // for

        if ($func != '#scanonly') {
            // emit the code
            if ($ff_config->compress)
                echo $this->compressJavascript(
                    $this->replaceCode($code, Text::_('COM_BREEZINGFORMSNG_PROCESS_SCRIPT') . " $func", $type, $id, $pane)
                );
            else
                echo $this->replaceCode($code, Text::_('COM_BREEZINGFORMSNG_PROCESS_SCRIPT') . " $func", $type, $id, $pane) . nl() . nl();
        } // if
    }

    // linkcode

    /**
     * @param int|string $cond
     * @param int|string|null $id
     * @param string $name
     * @param string $code
     * @param array<int, array<int, mixed>> $library
     * @param array<int, string> $linked
     * @param string|null $type
     * @param int|string|null $rowid
     * @param int|null $pane
     * @return mixed
     */
    function addFunction($cond, $id, $name, $code, &$library, &$linked, $type, $rowid, $pane)
    {
        if ($this->processor->dying)
            return;
        switch ($cond) {
            case 1:
                $script = $this->scriptingRuntime()->findScriptById((int) $id);
                if ($script !== null) {
                    $scriptName = $script->name;
                    $scriptCode = $script->code;

                    if ($this->processor->trim($scriptName) && $this->processor->nonblank($scriptCode)) {
                        $this->linkcode($scriptName, $library, $linked, $scriptCode, 's', $id, null);
                        if ($this->processor->dying)
                            return;
                    } // if
                } // if
                break;
            case 2:
                if ($this->processor->trim($name) && $this->processor->nonblank($code)) {
                    $this->linkcode($name, $library, $linked, $code, $type, $rowid, $pane);
                    if ($this->processor->dying)
                        return;
                } // if
                break;
            default:
                break;
        } // switch
    }

    // addFunction

}
