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
        if (!is_int($mode) && !is_string($mode)) {
            return '';
        }

        $formatted = $this->traceModeFormatter()->format($mode);

        return is_string($formatted) ? $formatted : '';
    }

    // dispTraceMode

    public function trim(mixed &$code): mixed
    {
        if (!is_string($code)) {
            return false;
        }

        return $this->codeStringTools()->trimInPlace($code);
    }

    // trim

    public function nonblank(mixed &$code): int
    {
        $string = $this->legacyString($code);

        return $string !== null && $this->codeStringTools()->containsNonWhitespace($string) ? 1 : 0;
    }

    // nonblank

    public function getClassName(mixed $classdef): string
    {
        $definition = $this->legacyString($classdef);

        if ($definition === null) {
            return '';
        }

        return $this->classNameResolver()->resolve(
            $definition,
            (int) $this->processor->template,
            (string) $this->processor->suffix
        );
    }

    // getClassName

    public function expJsValue(mixed $mixed, string $indent = ''): string
    {
        return $this->javascriptValueExporter()->exportValue($mixed, $indent);
    }

    // expJsValue

    public function expJsVar(mixed $name, mixed $mixed): string
    {
        $variableName = $this->legacyString($name);

        return $this->javascriptValueExporter()->exportVariable($variableName ?? '', $mixed);
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
        if (!is_string($code) || !is_int($spos) || !is_int($offs)) {
            return '';
        }

        return $this->findTokenValue($code, $spos, $offs);
    }

    // findToken

    private function findTokenValue(string $code, int &$spos, int &$offs): string
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

    // findTokenValue

    public function findRealToken(mixed &$code, mixed &$spos, mixed &$offs, mixed &$line): string
    {
        if (!is_string($code) || !is_int($spos) || !is_int($offs) || !is_int($line)) {
            return '';
        }

        return $this->findRealTokenValue($code, $spos, $offs, $line);
    }

    // findRealToken

    private function findRealTokenValue(string $code, int &$spos, int &$offs, int &$line): string
    {
        $linecmt = $blockcmt = false;
        $quote = null;
        for (; ; ) {
            $token = preg_replace('/[ \\t]*/', '', $this->findTokenValue($code, $spos, $offs));
            switch ($token) {
                case '':
                    return '';
                case 'function':
                case 'return':
                case 'ff_trace(':
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
        $modeString = $this->legacyString($mode);
        $codeString = $this->legacyString($code);
        $nameString = $this->legacyString($name);
        $typeString = $this->legacyString($type);
        $idString = $this->legacyString($id);
        $paneString = $this->legacyString($pane);

        if (
            $modeString === null
            || $codeString === null
            || $nameString === null
            || $typeString === null
            || $idString === null
            || $paneString === null
        ) {
            return '';
        }

        $flevel = $cpos = $spos = $offs = 0;
        $bye = false;
        $fstack = array();
        $line = 1;
        if ($typeString && $idString) {
            $typeCode = "'$typeString'";
            $idCode = $idString;
            $paneCode = $paneString;
            if (!$paneCode)
                $paneCode = 'null';
        } else
            $typeCode = $idCode = $paneCode = 'null';
        $escapedName = str_replace("'", "\\'", $nameString);
        $dst = "_ff_tracePiece($modeString,'$escapedName',$line,$typeCode,$idCode,$paneCode);";
        while (!$bye) {
            switch ($this->findRealTokenValue($codeString, $spos, $offs, $line)) {
                case '':
                    $bye = true;
                    break;
                case 'function':
                    $brk = false;
                    while (!$brk) {
                        // consume tokens until finding the opening bracket
                        switch ($this->findRealTokenValue($codeString, $spos, $offs, $line)) {
                            case '':
                                $bye = $brk = true;
                                break;
                            case '{':
                                $dst .= substr($codeString, $cpos, $spos - $cpos) .
                                    '$_ff_traceArgs = func_get_args();' .
                                    '_ff_traceFunction(' . $modeString . ',__FUNCTION__,' . $line . ',' . $typeCode . ',' . $idCode . ',' . $paneCode . ',$_ff_traceArgs);' .
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
                    $dst .= substr($codeString, $cpos, $spos - $cpos);
                    $cpos = $spos;
                    $brk = false;
                    while (!$brk) {
                        // consume tokens until semicolon found
                        switch ($this->findRealTokenValue($codeString, $spos, $offs, $line)) {
                            case '':
                                $bye = $brk = true;
                                break;
                            case ';':
                                $arg = substr($codeString, $cpos, $offs - $cpos);
                                $argIsNonblank = $this->codeStringTools()->containsNonWhitespace($arg);
                                if ($argIsNonblank)
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
                    $dst .= substr($codeString, $cpos, $offs - $cpos);
                    $cpos = $spos;
                    $brk = false;
                    $lvl = 0;
                    while (!$brk) {
                        // consume tokens until finding the closing bracket
                        switch ($this->findRealTokenValue($codeString, $spos, $offs, $line)) {
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
                    $par = $offs == $cpos ? '' : substr($codeString, $cpos, $offs - $cpos);
                    $dst .= " _ff_trace($line";
                    if ($this->nonblank($par))
                        $dst .= ',';
                    break;
                case '{':
                    if ($flevel > 0)
                        $flevel++;
                    break;
                case '}':
                    if ($flevel > 0) {
                        $flevel--;
                        if (!$flevel) {
                            $dst .= substr($codeString, $cpos, $offs - $cpos) . ' _ff_traceExit(' . $line . ');}';
                            $cpos = $spos;
                            if (count($fstack))
                                $flevel = array_pop($fstack);
                        } // if
                    } // if
                    break;
                default:
            } // switch
        } // while
        $spos = strlen($codeString);
        if ($cpos < $spos)
            $dst .= substr($codeString, $cpos, $spos - $cpos);
        $line--;
        $dst .= "_ff_traceExit($line);";
        return $dst;
    }

    // patchCode

    public function prepareEvalCode(mixed &$code, mixed $name, mixed $type, mixed $id, mixed $pane): bool
    {
        if (!is_string($code)) {
            return false;
        }

        $nameString = $this->legacyString($name);
        if ($nameString === null) {
            return false;
        }

        $evalCode = $this->legacyString($code);
        if ($evalCode === null) {
            return false;
        }

        $evalName = $nameString;

        if ($this->processor->dying)
            return false;
        if (!$this->codeStringTools()->containsNonWhitespace($evalCode))
            return false;
        $evalCode .= "\n/*'/*\"/**/;"; // closes all comments and strings that my be open
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
            if (preg_match($srch, $evalCode, $match)) {
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
                if (!$evalName) {
                    $normalizedName = preg_replace('/([\\s]+)/si', ' ', $evalCode);
                    $evalName = is_string($normalizedName) ? $normalizedName : '';
                    if (strlen($evalName) > _FF_TRACE_NAMELIMIT)
                        $evalName = substr($evalCode, 0, _FF_TRACE_NAMELIMIT - 3) . '...';
                } // if
                $evalCode = $this->patchCode($mode, $evalCode, $evalName, $type, $id, $pane);
            } // if
        } // if trace not disabled
        $code = str_replace($this->processor->findtags ?? [], $this->processor->replacetags ?? [], $evalCode);
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

    private function legacyString(mixed $value): ?string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value) || is_bool($value)) {
            return (string) $value;
        }

        if ($value === null) {
            return '';
        }

        if ($value instanceof \Stringable) {
            return (string) $value;
        }

        return null;
    }

    // prepareEvalCode

}
