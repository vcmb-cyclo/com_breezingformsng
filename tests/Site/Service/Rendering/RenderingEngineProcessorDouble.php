<?php

/**
 * BreezingForms NG - A Joomla Forms Application
 *
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 *
 * SPDX-License-Identifier: GPL-2.0-or-later
 **/

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Site\Service\Rendering;

use HTML_facileFormsProcessor;

require_once __DIR__ . '/../../../../components/com_breezingformsng/src/Support/processor_facade.php';

final class RenderingEngineProcessorDouble extends HTML_facileFormsProcessor
{
    public int $permissionChecks = 0;

    /** @var list<string> */
    public array $callbackNames = [];

    /** @var list<array{function: string, code: string}> */
    public array $linkedCallbacks = [];

    /** @var list<string> */
    public array $traceEvents = [];

    /** @var list<array<int, scalar>> */
    public array $queryResultRows = [];

    public bool $buryAfterFirstCallback = false;

    public bool $buryImmediately = false;

    /** Stop at the configured 1-indexed bury() call when set. */
    public ?int $buryOnCallNumber = null;

    private int $buryCallCount = 0;

    /** @var list<array{code: string, name: string, type: string, id: int, pane: int|null}> */
    public array $executedPieces = [];

    public function loadBuiltins(&$library)
    {
        $library['builtin'] = 'loaded';
    }

    public function loadScripts(&$library)
    {
        $library['script'] = 'loaded';
    }

    public function addFunction($cond, $id, $name, $code, &$library, &$linked, $type, $rowid, $pane)
    {
        $this->callbackNames[] = $name;
    }

    public function linkcode($func, &$library, &$linked, $code, $type = null, $id = null, $pane = null)
    {
        $this->linkedCallbacks[] = ['function' => $func, 'code' => $code];
    }

    public function compileQueryCol(&$elem, &$coldef)
    {
    }

    public function execQuery(&$elem, &$valrows, &$coldefs)
    {
        $valrows = $this->queryResultRows;
    }

    public function expJsValue($mixed, $indent = '')
    {
        return json_encode($mixed, JSON_THROW_ON_ERROR);
    }

    public function dumpTrace()
    {
        $this->traceEvents[] = 'dumpTrace';
        echo 'trace';
    }

    public function bury()
    {
        $this->buryCallCount++;

        if ($this->buryOnCallNumber !== null) {
            return $this->buryCallCount >= $this->buryOnCallNumber;
        }

        return $this->buryImmediately || ($this->buryAfterFirstCallback && count($this->callbackNames) >= 1);
    }

    public function getClassName($className)
    {
        return 'resolved-' . $className;
    }

    public function execPiece($code, $name, $type, $id, $pane)
    {
        $this->executedPieces[] = [
            'code' => $code,
            'name' => $name,
            'type' => $type,
            'id' => $id,
            'pane' => $pane,
        ];

        return '<piece>' . $code . '</piece>';
    }

    public function cbCheckPermissions(): array
    {
        $this->permissionChecks++;

        return [
            'form' => null,
            'record' => null,
            'frontend' => true,
            'data' => null,
            'full' => false,
        ];
    }
}
