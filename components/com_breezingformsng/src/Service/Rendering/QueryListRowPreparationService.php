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

namespace Vcmb\Component\BreezingformsNG\Site\Service\Rendering;

use HTML_facileFormsProcessor;
use Vcmb\Component\BreezingformsNG\Site\Table\QueryColumn;

/** Prepares Query List data and its client-side row state for one element. */
final class QueryListRowPreparationService
{
    public function __construct(
        private readonly HTML_facileFormsProcessor $processor,
        private readonly QueryListRowStateBuilder $stateBuilder
    ) {
    }

    /**
     * @param \stdClass $row
     * @return array{key: string, checkbox: bool|int, columns: list<QueryColumn>, rows: mixed, script: string}
     */
    public function prepare(object $row, string $newline): array
    {
        $key = 'ff_' . $row->id;
        $columns = [];

        if ($this->processor->trim($row->data3)) {
            foreach (explode("\n", $row->data3) as $definition) {
                if ($definition === '') {
                    continue;
                }

                $column = new QueryColumn();
                $column->unpack($definition);
                $this->processor->compileQueryCol($row, $column);
                $columns[] = $column;
            }
        }

        $checkbox = $row->flag2 ?: 0;
        $header = $row->flag1 ? 1 : 0;
        $pageNavigation = 1;
        $settings = explode("\n", $row->data1);

        if (count($settings) > 8 && $this->processor->trim($settings[8])) {
            $pageNavigation = $settings[8];
        }

        $rows = [];
        $this->processor->execQuery($row, $rows, $columns);

        return [
            'key' => $key,
            'checkbox' => $checkbox,
            'columns' => $columns,
            'rows' => $rows,
            'script' => $this->stateBuilder->build(
                (int) $row->id,
                (int) $row->height,
                (int) $checkbox,
                $header,
                (int) $pageNavigation,
                array_map(
                    static fn (QueryColumn $column): int => $column->thspan > 0 ? 1 : 0,
                    $columns
                ),
                $this->processor->expJsValue($rows),
                $newline
            ),
        ];
    }
}
