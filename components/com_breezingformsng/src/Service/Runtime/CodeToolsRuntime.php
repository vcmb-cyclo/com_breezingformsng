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

namespace Vcmb\Component\BreezingformsNG\Site\Service\Runtime;

\defined('_JEXEC') or die;

use HTML_facileFormsProcessor;
use Vcmb\Component\BreezingformsNG\Site\Service\Rendering\ClassNameResolver;
use Vcmb\Component\BreezingformsNG\Site\Service\Rendering\JavascriptValueExporter;

/**
 * Tracing display and legacy code patching/eval preparation.
 */
final class CodeToolsRuntime
{
    private ?ClassNameResolver $classNameResolverService = null;
    private ?JavascriptValueExporter $javascriptValueExporterService = null;
    private ?CodeStringTools $codeStringToolsService = null;
    private ?TraceModeFormatter $traceModeFormatterService = null;
    private ?TraceRuntime $traceRuntimeService = null;

    public function __construct(private readonly HTML_facileFormsProcessor $processor)
    {
    }

    public function dispTraceMode(mixed $mode): string
    {
        return $this->traceModeFormatter()->format($mode);
    }

    // dispTraceMode

    public function trim(mixed &$code): mixed
    {
        return $this->codeStringTools()->trimInPlace($code);
    }

    // trim

    public function nonblank(mixed &$code): int
    {
        return $this->codeStringTools()->containsNonWhitespace((string) $code) ? 1 : 0;
    }

    // nonblank

    public function getClassName(mixed $classdef): string
    {
        return $this->classNameResolver()->resolve(
            (string) $classdef,
            (int) $this->processor->template,
            (string) $this->processor->suffix
        );
    }

    // getClassName

    public function expJsValue(mixed $mixed, string $indent = ''): string
    {
        return $this->javascriptValueExporter()->exportValue($mixed, (string) $indent);
    }

    // expJsValue

    public function expJsVar(mixed $name, mixed $mixed): string
    {
        return $this->javascriptValueExporter()->exportVariable((string) $name, $mixed);
    }

    // expJsVar

    public function dumpTrace(): void
    {
        $this->traceRuntime()->dumpTrace();
    }

    // dumpTrace

    public function traceEval(mixed $name): void
    {
        $this->traceRuntime()->traceEval($name);
    }

    // traceEval

    public function suicide(): bool
    {
        return $this->traceRuntime()->suicide();
    }

    // suicide

    public function bury(): bool
    {
        return $this->traceRuntime()->bury();
    }

    // bury

    public function findToken(mixed &$code, mixed &$spos, mixed &$offs): string
    {
        $srch = '#(\bfunction\b|return[^a-zA-Z_-]|_ff_trace|ff_trace[ \\t]*\\(|//|/\*|\*/|\\\\"|\\\\\'|{|}|\(|\)|;|"|\'|\n)#si';
        $match = array();
        if (!preg_match($srch, $code, $match, PREG_OFFSET_CAPTURE, $spos))
            return '';
        $token = strtolower($match[0][0]);
        $offs = $match[0][1];
        $spos = $offs + strlen($token);
        return $token;
    }

    // findToken

    public function findRealToken(mixed &$code, mixed &$spos, mixed &$offs, mixed &$line): string
    {
        $linecmt = $blockcmt = false;
        $quote = null;
        for (; ; ) {
            $token = preg_replace('/[ \\t]*/', '', $this->findToken($code, $spos, $offs));
            switch ($token) {
                case '':
                    return '';
                case 'function':
                case 'return';
                case 'ff_trace(';
                case '{':
                case '}':
                case '(':
                case ')':
                case ';':
                    if (!$linecmt && !$blockcmt && !$quote)
                        return $token;
                    break;
                case "\n":
                    $line++;
                    $linecmt = false;
                    break;
                case '//':
                    if (!$blockcmt && !$quote)
                        $linecmt = true;
                    break;
                case '/*':
                    if (!$linecmt && !$quote)
                        $blockcmt = true;
                    break;
                case '*/':
                    if ($blockcmt)
                        $blockcmt = false;
                    break;
                case '"':
                case "'":
                    if ($quote == $token)
                        $quote = null;
                    else
                        if (!$linecmt && !$blockcmt && !$quote)
                            $quote = $token;
                    break;
                default:
                    break;
            } // switch
        } // for
    }

    // findRealToken

    public function patchCode(mixed $mode, mixed $code, mixed $name, mixed $type, mixed $id, mixed $pane): string
    {
        $flevel = $cpos = $spos = $offs = 0;
        $bye = false;
        $fstack = array();
        $line = 1;
        if ($type && $id) {
            $type = "'$type'";
            if (!$pane)
                $pane = 'null';
        } else
            $type = $id = $pane = 'null';
        $name = str_replace("'", "\\'", $name);
        $dst = "_ff_tracePiece($mode,'$name',$line,$type,$id,$pane);";
        while (!$bye) {
            switch ($this->findRealToken($code, $spos, $offs, $line)) {
                case '':
                    $bye = true;
                    break;
                case 'function':
                    $brk = false;
                    while (!$brk) {
                        // consume tokens until finding the opening bracket
                        switch ($this->findRealToken($code, $spos, $offs, $line)) {
                            case '':
                                $bye = $brk = true;
                                break;
                            case '{':
                                $dst .= substr($code, $cpos, $spos - $cpos) .
                                    '$_ff_traceArgs = func_get_args();' .
                                    '_ff_traceFunction(' . $mode . ',__FUNCTION__,' . $line . ',' . $type . ',' . $id . ',' . $pane . ',$_ff_traceArgs);' .
                                    '$_ff_traceArgs=null;';
                                $cpos = $spos;
                                if ($flevel)
                                    array_push($fstack, $flevel);
                                $flevel = 1;
                                $brk = true;
                                break;
                            default:
                                ;
                        } // switch
                    } // while
                    break;
                case 'return':
                    $dst .= substr($code, $cpos, $spos - $cpos);
                    $cpos = $spos;
                    $brk = false;
                    while (!$brk) {
                        // consume tokens until semicolon found
                        switch ($this->findRealToken($code, $spos, $offs, $line)) {
                            case '':
                                $bye = $brk = true;
                                break;
                            case ';':
                                $arg = substr($code, $cpos, $offs - $cpos);
                                if ($this->nonblank($arg))
                                    $dst .= ' _ff_traceExit(' . $line . ',' . $arg . ');';
                                else
                                    $dst .= ' _ff_traceExit(' . $line . ');';
                                $cpos = $spos;
                                $brk = true;
                                break;
                            default:
                                ;
                        } // switch
                    } // while
                    break;
                case 'ff_trace(':
                    $dst .= substr($code, $cpos, $offs - $cpos);
                    $cpos = $spos;
                    $brk = false;
                    $lvl = 0;
                    while (!$brk) {
                        // consume tokens until finding the closing bracket
                        switch ($this->findRealToken($code, $spos, $offs, $line)) {
                            case '':
                                $bye = $brk = true;
                                break;
                            case '(':
                                $lvl++;
                                break;
                            case ')':
                                if ($lvl)
                                    $lvl--;
                                else
                                    $brk = true;
                                break;
                            default:
                                ;
                        } // switch
                    } // while
                    $par = $offs == $cpos ? '' : substr($code, $cpos, $offs - $cpos);
                    $dst .= " _ff_trace($line";
                    if ($this->nonblank($par))
                        $dst .= ',';
                    break;
                case '{':
                    if ($flevel > 0)
                        $flevel++;
                    break;
                case '}';
                    if ($flevel > 0) {
                        $flevel--;
                        if (!$flevel) {
                            $dst .= substr($code, $cpos, $offs - $cpos) . ' _ff_traceExit(' . $line . ');}';
                            $cpos = $spos;
                            if (count($fstack))
                                $flevel = array_pop($fstack);
                        } // if
                    } // if
                    break;
                default:
            } // switch
        } // while
        $spos = strlen($code);
        if ($cpos < $spos)
            $dst .= substr($code, $cpos, $spos - $cpos);
        $line--;
        $dst .= "_ff_traceExit($line);";
        return $dst;
    }

    // patchCode

    public function prepareEvalCode(mixed &$code, mixed $name, mixed $type, mixed $id, mixed $pane): bool
    {
        if ($this->processor->dying)
            return false;
        if (!$this->nonblank($code))
            return false;
        $code .= "\n/*'/*\"/**/;"; // closes all comments and strings that my be open
        $disable = ($this->processor->traceMode & _FF_TRACEMODE_DISABLE) ? true : false;
        if (!$disable) {
            $mode = 'null';
            $srch = '#' .
                '^[\\s]*(//\+trace|/\*\+trace)' .
                '[ \\t]*([\\w]+)?' .
                '[ \\t]*([\\w]+)?' .
                '[ \\t]*([\\w]+)?' .
                '[ \\t]*([\\w]+)?' .
                '[ \\t]*([\\w]+)?' .
                '[ \\t]*([\\w]+)?' .
                '[ \\t]*(\\*/|\\r\\n)?' .
                '#';
            $match = array();
            if (preg_match($srch, $code, $match)) {
                $mode = 2;
                $append = $direct = $xeval = $piece = $func = $msg = false;
                $local = $def = true;
                for ($m = 2; $m < count($match); $m++)
                    switch ($match[$m]) {
                        // disable
                        case 'dis':
                        case 'disable':
                            $disable = true;
                            break;
                        // mode
                        case 'pop':
                        case 'popup':
                            $direct = $append = false;
                            break;
                        case 'app':
                        case 'append':
                            $append = true;
                            $direct = false;
                            break;
                        case 'dir':
                        case 'direct':
                            $direct = true;
                            $append = false;
                            break;
                        // priority
                        case 'min':
                        case 'minimum':
                            $mode = 0;
                            break;
                        case 'low':
                            $mode = 1;
                            break;
                        case 'nor':
                        case 'normal':
                            $mode = 2;
                            break;
                        case 'hig':
                        case 'high':
                            $mode = 3;
                            break;
                        case 'max':
                        case 'maximum':
                            $mode = 4;
                            break;
                        // scope
                        case 'glo':
                        case 'global':
                            $local = false;
                            break;
                        case 'loc':
                        case 'local':
                            $local = true;
                            break;
                        // topics
                        case 'all':
                            $def = false;
                            $xeval = $piece = $func = $msg = true;
                            break;
                        case 'non':
                        case 'none':
                            $def = $xeval = $piece = $func = $msg = false;
                            break;
                        case 'eva':
                        case 'eval':
                            $def = false;
                            $xeval = true;
                            break;
                        case 'pie':
                        case 'piece':
                            $def = false;
                            $piece = true;
                            break;
                        case 'fun':
                        case 'function':
                            $def = false;
                            $func = true;
                            break;
                        case 'mes':
                        case 'message':
                            $def = false;
                            $msg = true;
                            break;
                        default:
                            break;
                    } // switch

                if ($def) {
                    $xeval = false;
                    $piece = $func = $msg = true;
                }
                if ($xeval)
                    $mode |= _FF_TRACEMODE_EVAL;
                if ($piece)
                    $mode |= _FF_TRACEMODE_PIECE;
                if ($func)
                    $mode |= _FF_TRACEMODE_FUNCTION;
                if ($msg)
                    $mode |= _FF_TRACEMODE_MESSAGE;
                if ($local)
                    $mode |= _FF_TRACEMODE_LOCAL;

                $first = ($this->processor->traceMode & _FF_TRACEMODE_FIRST) ? true : false;
                if ($first) {
                    $oldMode = $this->processor->traceMode;
                    $this->processor->traceMode = 0;
                    if ($disable)
                        $this->processor->traceMode |= _FF_TRACEMODE_DISABLE;
                    if ($append)
                        $this->processor->traceMode |= _FF_TRACEMODE_APPEND;
                    if ($direct) {
                        $this->processor->traceMode |= _FF_TRACEMODE_DIRECT;
                        $html = ob_get_contents();
                        ob_end_clean();
                        echo '<pre>' . htmlspecialchars((string) $html, ENT_QUOTES);
                        ob_start();
                    } // if
                } else
                    $disable = false;
            } // if trace directive
            if (!$disable) {
                if (!$name) {
                    $name = preg_replace('/([\\s]+)/si', ' ', $code);
                    if (strlen($name ?? '') > _FF_TRACE_NAMELIMIT)
                        $name = substr($code, 0, _FF_TRACE_NAMELIMIT - 3) . '...';
                } // if
                $code = $this->patchCode($mode, $code, $name, $type, $id, $pane);
            } // if
        } // if trace not disabled
        $code = str_replace($this->processor->findtags ?? [], $this->processor->replacetags ?? [], $code);
        return true;
    }

    private function classNameResolver(): ClassNameResolver
    {
        return $this->classNameResolverService ??= new ClassNameResolver();
    }

    private function javascriptValueExporter(): JavascriptValueExporter
    {
        return $this->javascriptValueExporterService ??= new JavascriptValueExporter();
    }

    private function codeStringTools(): CodeStringTools
    {
        return $this->codeStringToolsService ??= new CodeStringTools();
    }

    private function traceModeFormatter(): TraceModeFormatter
    {
        return $this->traceModeFormatterService ??= new TraceModeFormatter();
    }

    private function traceRuntime(): TraceRuntime
    {
        return $this->traceRuntimeService ??= new TraceRuntime($this->processor);
    }

    // prepareEvalCode

}
