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

/** Builds the JavaScript state assignments for one Query List row. */
final class QueryListRowStateBuilder
{
    /**
     * @param list<int> $columnVisibility
     */
    public function build(
        int $rowId,
        int $pageSize,
        int $checkbox,
        int $header,
        int $pageNavigation,
        array $columnVisibility,
        string $rowsJson,
        string $newline
    ): string {
        return $newline .
            'ff_queryCurrPage[' . $rowId . '] = 1;' . $newline .
            'ff_queryPageSize[' . $rowId . '] = ' . $pageSize . ';' . $newline .
            'ff_queryCheckbox[' . $rowId . '] = ' . $checkbox . ';' . $newline .
            'ff_queryHeader[' . $rowId . '] = ' . $header . ';' . $newline .
            'ff_queryPagenav[' . $rowId . '] = ' . $pageNavigation . ';' . $newline .
            'ff_queryCols[' . $rowId . '] = [' . implode(',', $columnVisibility) . '];' . $newline .
            'ff_queryRows[' . $rowId . '] = ' . $rowsJson . ';' . $newline;
    }
}
