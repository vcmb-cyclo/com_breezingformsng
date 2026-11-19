<?php

/**
 * BreezingForms NG - A Joomla Forms Application
 *
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 *
 * SPDX-License-Identifier: GPL-2.0-or-later
 **/

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator;

use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Administrator\Service\LibraryImportSummary;

final class LibraryImportSummaryTest extends TestCase
{
    private static function translate(string $key, int $count, string $names): string
    {
        return $key . '#' . $count . '#' . $names;
    }

    public function testAnImportThatChangedNothingHasNoSummary(): void
    {
        $importer = (object) ['createdScripts' => [], 'createdPieces' => [], 'updatedScripts' => [], 'updatedPieces' => []];

        self::assertSame([], LibraryImportSummary::parts($importer, self::translate(...)));
        self::assertSame([], LibraryImportSummary::parts(new \stdClass(), self::translate(...)));
    }

    public function testReplacedItemsSayTheirPreviousCodeIsKeptInTheHistory(): void
    {
        $importer = (object) [
            'createdScripts' => ['ff_new'],
            'createdPieces' => [],
            'updatedScripts' => ['ff_b', 'ff_a', 'ff_a', ' '],
            'updatedPieces' => ['ff_select'],
        ];

        self::assertSame([
            'COM_BREEZINGFORMSNG_INSTALL_STANDARD_LIBRARY_CREATED_SCRIPTS#1#ff_new',
            'COM_BREEZINGFORMSNG_INSTALL_STANDARD_LIBRARY_REPLACED_SCRIPTS#2#ff_a, ff_b',
            'COM_BREEZINGFORMSNG_INSTALL_STANDARD_LIBRARY_REPLACED_PIECES#1#ff_select',
        ], LibraryImportSummary::parts($importer, self::translate(...)));
    }
}
