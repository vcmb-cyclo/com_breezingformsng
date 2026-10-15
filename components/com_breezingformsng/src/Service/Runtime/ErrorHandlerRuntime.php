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
use Joomla\Database\ParameterType;

/**
 * Formats and records errors raised while executing stored form code.
 */
final class ErrorHandlerRuntime
{
    public function __construct(
        private readonly HTML_facileFormsProcessor $processor,
        private readonly string $site
    ) {
    }

    public function handle(int $errno, string $errstr, string $errfile, int $errline): void
    {
        $database = $this->processor->database;

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
            $info = $this->processor->traceStack[$n - 1];
            $name = htmlspecialchars(
                $info[2] . ' ' . Text::_('COM_BREEZINGFORMSNG_PROCESS_ATLINE') . ' ' . $info[3],
                ENT_QUOTES
            );
            $type = $info[4];
            $id = $info[5];
            $pane = $info[6];
            if ($type && $id && $this->processor->runmode != _FF_RUNMODE_FRONTEND) {
                $url = $this->site . '/administrator/index.php?option=com_breezingformsng&format=html&tmpl=component';
                $what = $id;
                switch ($type) {
                    case 'f':
                        $url .= '&task=quickmode.display' .
                            '&form=' . $this->processor->form;
                        if ($this->processor->formrow->package != '') {
                            $url .= '&pkg=' . urlencode($this->processor->formrow->package);
                        }
                        if ($pane > 0) {
                            $url .= '&tabpane=' . $pane;
                        }
                        $what = 'form ' . $this->processor->formrow->name;
                        break;
                    case 'e':
                        $page = 1;
                        foreach ($this->processor->rows as $row) {
                            if ($row->id == $id) {
                                $page = $row->page;
                                $what = $row->name;
                                break;
                            }
                        }
                        $what = 'element ' . $what;
                        $url .= '&task=quickmode.display' .
                            '&form=' . $this->processor->form .
                            '&page=' . $page;
                        if ($this->processor->formrow->package != '') {
                            $url .= '&pkg=' . urlencode($this->processor->formrow->package);
                        }
                        if ($pane > 0) {
                            $url .= '&tabpane=' . $pane;
                        }
                        break;
                    case 'p':
                        $package = '';
                        $piecesQuery = $database->createQuery()
                            ->select(['name', 'package'])
                            ->from('#__facileforms_pieces')
                            ->where($database->quoteName('id') . ' = :id')
                            ->bind(':id', $id, ParameterType::INTEGER);
                        $database->setQuery($piecesQuery);
                        $rows = $database->loadObjectList();
                        if (count($rows)) {
                            $package = $rows[0]->package;
                            $what = $rows[0]->name;
                        }
                        $what = 'piece ' . $what;
                        $url .= '&task=piece.edit' .
                            '&id=' . $id;
                        break;
                    case 's':
                        $package = '';
                        $scriptsQuery = $database->createQuery()
                            ->select(['name', 'package'])
                            ->from('#__facileforms_scripts')
                            ->where($database->quoteName('id') . ' = :id')
                            ->bind(':id', $id, ParameterType::INTEGER);
                        $database->setQuery($scriptsQuery);
                        $rows = $database->loadObjectList();
                        if (count($rows)) {
                            $package = $rows[0]->package;
                            $what = $rows[0]->name;
                        }
                        $what = 'script ' . $what;
                        $url .= '&task=script.edit' .
                            '&id=' . $id;
                        break;
                    default:
                        $url = null;
                }
                if ($url) {
                    $name = '<a href="#" ' .
                        'onMouseOver="window.status=\'Open ' . $what . '\';return true;" ' .
                        'onMouseOut="window.status=\'\';return true;" ' .
                        'onClick="ff_redirectParent(\'' . htmlspecialchars($url, ENT_QUOTES) . '\');return true;"' .
                        '>' . $name . '</a>';
                }
            }
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
