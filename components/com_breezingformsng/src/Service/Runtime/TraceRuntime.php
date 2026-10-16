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

/** Executes the tracing operations used by instrumented stored PHP code. */
final class TraceRuntime
{
    public function __construct(private readonly HTML_facileFormsProcessor $processor)
    {
    }

    /**
     * @param mixed $msg
     */
    public function trace($msg = null): void
    {
        if (
            $this->processor->dying
            || ($this->processor->traceMode & _FF_TRACEMODE_DISABLE)
            || !($this->processor->traceMode & _FF_TRACEMODE_MESSAGE)
        ) {
            return;
        }

        $trc = str_repeat('  ', count($this->processor->traceStack));
        $trc .= Text::_('COM_BREEZINGFORMSNG_PROCESS_MSGUNKNOWN') . ": $msg\n";
        $this->processor->traceBuffer .= htmlspecialchars($trc, ENT_QUOTES);
        if ($this->processor->traceMode & _FF_TRACEMODE_DIRECT) {
            $this->processor->dumpTrace();
        }
    }

    /**
     * @param int $line
     * @param mixed $msg
     */
    public function traceLine($line, $msg = null): void
    {
        if ($this->processor->dying || ($this->processor->traceMode & _FF_TRACEMODE_DISABLE)) {
            return;
        }

        $level = count($this->processor->traceStack);
        if ($msg && ($this->processor->traceMode & _FF_TRACEMODE_MESSAGE)) {
            $trc = str_repeat('  ', $level);
            $trc .= Text::_('COM_BREEZINGFORMSNG_PROCESS_LINE') . " $line: $msg\n";
            $this->processor->traceBuffer .= htmlspecialchars($trc, ENT_QUOTES);
            if ($this->processor->traceMode & _FF_TRACEMODE_DIRECT) {
                $this->processor->dumpTrace();
            }
        }

        if ($level) {
            $this->processor->traceStack[$level - 1][3] = $line;
        }
    }

    /**
     * @param int|null $newmode
     * @param string $name
     *
     * @return int|null
     */
    public function getMode(&$newmode, &$name)
    {
        $oldmode = $this->processor->traceMode;
        if (is_null($newmode) || ($newmode & _FF_TRACEMODE_PRIORITY) < ($oldmode & _FF_TRACEMODE_PRIORITY)) {
            $newmode = $oldmode;
            return $oldmode;
        }

        $newmode = ($oldmode & ~_FF_TRACEMODE_VARIABLE) | ($newmode & _FF_TRACEMODE_VARIABLE);
        if ($oldmode != $newmode) {
            $this->processor->traceMode = $newmode;
        }

        return ($newmode & _FF_TRACEMODE_LOCAL) ? $oldmode : $newmode;
    }

    /**
     * @param int|null $newmode
     * @param string $name
     * @param int $line
     * @param string|null $type
     * @param int|string|null $id
     * @param int|null $pane
     */
    public function tracePiece($newmode, $name, $line, $type, $id, $pane): void
    {
        if ($this->processor->dying || ($this->processor->traceMode & _FF_TRACEMODE_DISABLE)) {
            return;
        }

        $oldmode = $this->getMode($newmode, $name);
        if ($newmode & _FF_TRACEMODE_PIECE) {
            $this->processor->traceBuffer .= str_repeat('  ', count($this->processor->traceStack));
            $this->processor->traceBuffer .= htmlspecialchars(
                "+" . Text::_('COM_BREEZINGFORMSNG_PROCESS_ENTER')
                    . " $name " . Text::_('COM_BREEZINGFORMSNG_PROCESS_ATLINE') . " $line\n",
                ENT_QUOTES
            );
            if ($this->processor->traceMode & _FF_TRACEMODE_DIRECT) {
                $this->processor->dumpTrace();
            }
        }

        $this->processor->traceStack[] = [$oldmode, 'p', $name, $line, $type, $id, $pane];
    }

    /**
     * @param int|null $newmode
     * @param string $name
     * @param int $line
     * @param string|null $type
     * @param int|string|null $id
     * @param int|null $pane
     * @param array<int, mixed>|null $args
     */
    public function traceFunction($newmode, $name, $line, $type, $id, $pane, &$args): void
    {
        if ($this->processor->dying || ($this->processor->traceMode & _FF_TRACEMODE_DISABLE)) {
            return;
        }

        $oldmode = $this->getMode($newmode, $name);
        if ($newmode & _FF_TRACEMODE_FUNCTION) {
            $trc = str_repeat('  ', count($this->processor->traceStack));
            $trc .= "+" . Text::_('COM_BREEZINGFORMSNG_PROCESS_ENTER') . " $name(";
            if ($args) {
                $next = false;
                foreach ($args as $arg) {
                    if ($next) {
                        $trc .= ', ';
                    } else {
                        $next = true;
                    }

                    if (is_null($arg)) {
                        $trc .= 'null';
                    } elseif (is_bool($arg)) {
                        $trc .= $arg ? 'true' : 'false';
                    } elseif (is_numeric($arg)) {
                        $trc .= $arg;
                    } elseif (is_string($arg)) {
                        $arg = preg_replace('/([\\s]+)/si', ' ', $arg);
                        if (strlen($arg) > _FF_TRACE_NAMELIMIT) {
                            $arg = substr($arg, 0, _FF_TRACE_NAMELIMIT - 3) . '...';
                        }
                        $trc .= "'$arg'";
                    } elseif (is_array($arg)) {
                        $trc .= Text::_('COM_BREEZINGFORMSNG_PROCESS_ARRAY');
                    } elseif (is_object($arg)) {
                        $trc .= Text::_('COM_BREEZINGFORMSNG_PROCESS_OBJECT');
                    } elseif (is_resource($arg)) {
                        $trc .= Text::_('COM_BREEZINGFORMSNG_PROCESS_RESOURCE');
                    } else {
                        $trc .= Text::_('COM_BREEZINGFORMSNG_PROCESS_UNKNOWN');
                    }
                }
            }

            $trc .= ") " . Text::_('COM_BREEZINGFORMSNG_PROCESS_ATLINE') . " $line\n";
            $this->processor->traceBuffer .= htmlspecialchars($trc, ENT_QUOTES);
            if ($this->processor->traceMode & _FF_TRACEMODE_DIRECT) {
                $this->processor->dumpTrace();
            }
        }

        $this->processor->traceStack[] = [$oldmode, 'f', $name, $line, $type, $id, $pane];
    }

    /**
     * @param int $line
     * @param mixed $retval
     */
    public function traceExit($line, $retval = null): mixed
    {
        if ($this->processor->dying || ($this->processor->traceMode & _FF_TRACEMODE_DISABLE)) {
            return null;
        }

        $info = array_pop($this->processor->traceStack);
        if ($info) {
            $oldmode = $this->processor->traceMode;
            $newmode = $info[0];
            $kind = $info[1];
            $name = $info[2];
            if ($kind == 'p') {
                $visible = $oldmode & _FF_TRACEMODE_PIECE;
            } else {
                $visible = $oldmode & _FF_TRACEMODE_FUNCTION;
            }

            if ($visible) {
                $this->processor->traceBuffer .= str_repeat('  ', count($this->processor->traceStack));
                $this->processor->traceBuffer .= htmlspecialchars(
                    "-" . Text::_('COM_BREEZINGFORMSNG_PROCESS_LEAVE')
                        . " $name " . Text::_('COM_BREEZINGFORMSNG_PROCESS_ATLINE') . " $line\n",
                    ENT_QUOTES
                );
                if ($oldmode & _FF_TRACEMODE_DIRECT) {
                    $this->processor->dumpTrace();
                }
            }

            if ($oldmode != $newmode) {
                $this->processor->traceMode = ($oldmode & ~_FF_TRACEMODE_VARIABLE)
                    | ($newmode & _FF_TRACEMODE_VARIABLE);
            }
        } else {
            $this->processor->traceBuffer .= htmlspecialchars(
                Text::_('COM_BREEZINGFORMSNG_PROCESS_WARNSTK') . "\n",
                ENT_QUOTES
            );
            if ($this->processor->traceMode & _FF_TRACEMODE_DIRECT) {
                $this->processor->dumpTrace();
            }
        }

        return $retval;
    }

    public function dumpTrace(): void
    {
        if ($this->processor->traceMode & _FF_TRACEMODE_DIRECT) {
            $html = ob_get_contents();
            ob_end_clean();
            echo htmlspecialchars($html, ENT_QUOTES) . $this->processor->traceBuffer;
            ob_start();
            $this->processor->traceBuffer = null;
            return;
        }

        if (!$this->processor->traceBuffer) {
            return;
        }

        if ($this->processor->traceMode & _FF_TRACEMODE_APPEND) {
            echo '<pre>' . $this->processor->traceBuffer . '</pre>';
            $this->processor->traceBuffer = null;
            return;
        }

        echo '<script type="text/javascript">' . nl()
            . '<!--' . nl();
        if ($this->processor->dying) {
            echo 'console.log(' . json_encode($this->processor->traceBuffer) . ')' . nl();
        }
        echo '-->' . nl() . '</script>' . nl();
        $this->processor->traceBuffer = null;
    }

    public function traceEval(mixed $name): void
    {
        if (
            ($this->processor->traceMode & _FF_TRACEMODE_DISABLE)
            || !($this->processor->traceMode & _FF_TRACEMODE_EVAL)
            || $this->processor->dying
        ) {
            return;
        }

        $this->processor->traceBuffer .= str_repeat('  ', count($this->processor->traceStack));
        $this->processor->traceBuffer .= htmlspecialchars("eval($name)\n", ENT_QUOTES);
        if ($this->processor->traceMode & _FF_TRACEMODE_DIRECT) {
            $this->dumpTrace();
        }
    }

    public function suicide(): bool
    {
        if ($this->processor->dying) {
            return false;
        }

        $this->processor->dying = true;
        $rep = 0;
        $this->processor->errrep = error_reporting($rep);

        return true;
    }

    public function bury(): bool
    {
        if (!$this->processor->dying) {
            return false;
        }

        if ($this->processor->traceMode & _FF_TRACEMODE_DIRECT) {
            $this->dumpTrace();
        }
        ob_end_clean();
        if ($this->processor->traceMode & _FF_TRACEMODE_DIRECT) {
            echo '</pre>';
        } else {
            $this->dumpTrace();
        }
        error_reporting($this->processor->errrep);
        restore_error_handler();

        return true;
    }
}
