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

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator\Service;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Administrator\Service\SqlDate;

final class SqlDateTest extends TestCase
{
    /**
     * @return array<string, array{string, ?string}>
     */
    public static function dates(): array
    {
        return [
            'date and time' => ['2026-02-04 13:24:10', '2026-02-04 13:24:10'],
            'date only' => ['2026-07-17', '2026-07-17 00:00:00'],
            'surrounding spaces' => [' 2024-01-29 00:45:19 ', '2024-01-29 00:45:19'],
            'leap day' => ['2024-02-29', '2024-02-29 00:00:00'],
            'impossible day' => ['2024-02-31', null],
            'impossible day with time' => ['2023-02-29 10:00:00', null],
            'impossible time' => ['2024-01-01 25:00:00', null],
            'zero date' => ['0000-00-00 00:00:00', null],
            'empty' => ['', null],
            'free text' => ['31/07/2005', null],
        ];
    }

    #[DataProvider('dates')]
    public function testNormalisesOnlyRealDates(string $value, ?string $expected): void
    {
        self::assertSame($expected, SqlDate::normalise($value));
    }
}
