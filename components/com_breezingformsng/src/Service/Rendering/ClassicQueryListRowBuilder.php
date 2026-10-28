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

use Vcmb\Component\BreezingformsNG\Site\Table\QueryColumn;

/**
 * Builds one data row of a classic Query List table.
 */
final class ClassicQueryListRowBuilder
{
    public function __construct(
        private readonly ClassicQueryListCellBuilder $cellBuilder = new ClassicQueryListCellBuilder()
    ) {
    }

    /**
     * @param list<QueryColumn> $columns
     * @param list<string|int|float|null> $values
     * @param callable(string): string $classResolver
     * @param callable(): bool $shouldStop
     */
    public function build(
        array $columns,
        array $values,
        int $rowId,
        int $rowIndex,
        string $rowName,
        string $rowClass,
        int $selectionMode,
        bool $firstDataRow,
        callable $classResolver,
        callable $shouldStop,
        string $rowIndent = "\t",
        string $cellIndent = "\t\t",
        string $compactNewline = "\n",
        string $newline = "\n"
    ): string {
        $row = $rowIndent . '<tr' . $rowClass . '>' . $compactNewline;
        $skip = 0;
        foreach ($columns as $columnIndex => $column) {
            $row .= $this->cellBuilder->build(
                $column,
                $values[$columnIndex] ?? null,
                $columnIndex,
                $rowIndex,
                $rowId,
                $rowName,
                $selectionMode,
                $firstDataRow,
                $skip,
                $classResolver,
                $cellIndent,
                $compactNewline
            );
            if ($shouldStop()) {
                break;
            }
        }

        return $row . $rowIndent . '</tr>' . $newline;
    }
}
