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
 * Builds the header row of a classic Query List table.
 */
final class ClassicQueryListHeaderBuilder
{
    /**
     * @param iterable<QueryColumn> $columns
     * @param callable(string): string $classResolver
     * @param callable(QueryColumn): string $titleResolver
     */
    public function build(
        iterable $columns,
        int $rowId,
        int $selectionMode,
        string $headerClass,
        callable $classResolver,
        callable $titleResolver,
        string $rowIndent = "\t",
        string $cellIndent = "\t\t",
        string $newline = "\n"
    ): string {
        $header = $rowIndent . '<tr' . $headerClass . '>' . $newline;
        $skip = 0;
        $columnIndex = 0;
        foreach ($columns as $column) {
            if ($skip > 0) {
                $skip--;
                $columnIndex++;
                continue;
            }
            if ($column->thspan <= 0) {
                $columnIndex++;
                continue;
            }

            $attributes = '';
            $style = match ((int) $column->thalign) {
                1 => 'text-align:left;',
                2 => 'text-align:center;',
                3 => 'text-align:right;',
                4 => 'text-align:justify;',
                default => '',
            };
            $attributes .= match ((int) $column->thvalign) {
                1 => ' valign="top"',
                2 => ' valign="middle"',
                3 => ' valign="bottom"',
                4 => ' valign="baseline"',
                default => '',
            };
            if ($column->thwrap == 1) {
                $attributes .= ' nowrap="nowrap"';
            }
            if ($column->thspan > 1) {
                $attributes .= ' colspan="' . $column->thspan . '"';
                $skip = (int) $column->thspan - 1;
            }
            if ($column->class1 !== '') {
                $attributes .= ' class="' . $classResolver((string) $column->class1) . '"';
            }
            if ((int) $column->width > 0 && $skip === 0) {
                $style .= 'width:' . $column->width . ($column->widthmd ? '%;' : 'px;');
            }
            if ($style !== '') {
                $attributes .= ' style="' . $style . '"';
            }

            if ($columnIndex === 0 && $selectionMode > 0) {
                $cell = $selectionMode === 1
                    ? '<th' . $attributes . '><input type="checkbox" id="ff_cb' . $rowId
                        . '" onclick="ff_selectAllQueryRows(' . $rowId . ',this.checked);" /></th>'
                    : '<th' . $attributes . '></th>';
            } else {
                $cell = '<th' . $attributes . '>' . $titleResolver($column) . '</th>';
            }
            $header .= $cellIndent . $cell . $newline;
            $columnIndex++;
        }

        return $header . $rowIndent . '</tr>' . $newline;
    }
}
