<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Site\Service\Runtime;

\defined('_JEXEC') or die;

use HTML_facileFormsProcessor;
use Joomla\CMS\Language\Text;

/**
 * Formats and records errors raised while executing stored form code.
 */
final class ErrorHandlerRuntime
{
    public function __construct(
        private readonly HTML_facileFormsProcessor $processor
    ) {
    }

    public function handle(int $errno, string $errstr, string $errfile, int $errline): void
    {
        if ($this->processor->dying) {
            return;
        }

        $msg = "\n<strong>*** " . htmlspecialchars(
            Text::_('COM_BREEZINGFORMSNG_PROCESS_EXCAUGHT'),
            ENT_QUOTES
        ) . " ***</strong>\n" .
            htmlspecialchars(Text::_('COM_BREEZINGFORMSNG_PROCESS_PHPLEVEL') . ' ', ENT_QUOTES);
        $fail = false;
        switch ($errno) {
            case E_WARNING:
                $msg .= "E_WARNING";
                break;
            case E_NOTICE:
                $msg .= "E_NOTICE";
                break;
            case E_USER_ERROR:
                $msg .= "E_USER_ERROR";
                $fail = true;
                break;
            case E_USER_WARNING:
                $msg .= "E_USER_WARNING";
                break;
            case E_USER_NOTICE:
                $msg .= "E_USER_NOTICE";
                break;
            case E_DEPRECATED:
                $msg .= "E_DEPRECATED";
                break;
            case 2048:
            case 16384:
                return;
            default:
                $msg .= $errno;
                $fail = true;
        }
        $msg .= htmlspecialchars(
            "\n" . Text::_('COM_BREEZINGFORMSNG_PROCESS_PHPFILE') . " $errfile\n" .
            Text::_('COM_BREEZINGFORMSNG_PROCESS_PHPLINE') . " $errline\n",
            ENT_QUOTES
        );

        $n = is_countable($this->processor->traceStack) ? count($this->processor->traceStack) : 1;

        if ($n) {
            $info = $this->processor->traceStack[$n - 1] ?? [];
            $name = htmlspecialchars(
                $info[2] . ' ' . Text::_('COM_BREEZINGFORMSNG_PROCESS_ATLINE') . ' ' . $info[3],
                ENT_QUOTES
            );
            $msg .= htmlspecialchars(Text::_('COM_BREEZINGFORMSNG_PROCESS_LASTPOS'), ENT_QUOTES) . ' ' . $name . "\n";
        }
        $msg .= htmlspecialchars(Text::_('COM_BREEZINGFORMSNG_PROCESS_ERRMSG') . " $errstr\n\n", ENT_QUOTES);
        if ($fail) {
            $this->processor->traceBuffer .= $msg;
            $this->processor->suicide();
        } elseif (($this->processor->traceMode & _FF_TRACEMODE_DISABLE) == 0) {
            $this->processor->traceBuffer .= $msg;
            if ($this->processor->traceMode & _FF_TRACEMODE_DIRECT) {
                $this->processor->dumpTrace();
            }
        }
    }
}
