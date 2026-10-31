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

namespace Vcmb\Component\BreezingformsNG\Tests\Site\Service\Submission;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Site\Service\Submission\StripeAmount;

final class StripeAmountTest extends TestCase
{
    /** @return array<string, array{mixed, ?int}> */
    public static function values(): array
    {
        return [
            'dot' => ['12.99', 1299],
            'comma' => ['12,99', 1299],
            'whole' => ['45', 4500],
            'one decimal' => ['12.5', 1250],
            'spaces' => [' 8.00 ', 800],
            'first of a list' => [['7.50', '99'], 750],
            'empty' => ['', null],
            'empty list' => [[], null],
            'zero' => ['0.00', null],
            'negative' => ['-5', null],
            'text' => ['free', null],
            'text after' => ['12abc', null],
            'three decimals' => ['1.999', null],
            'thousands' => ['1,000.50', null],
            'placeholder' => ['{Montant:value}', null],
        ];
    }

    #[DataProvider('values')]
    public function testToCents(mixed $submitted, ?int $expected): void
    {
        self::assertSame($expected, (new StripeAmount())->toCents($submitted));
    }

    public function testAnElementWithoutTheSettingKeepsItsFixedAmount(): void
    {
        $amount = new StripeAmount();

        self::assertFalse($amount->comesFromField(['amount' => '10']));
        self::assertFalse($amount->comesFromField(['amountSource' => 'fixed']));
        self::assertTrue($amount->comesFromField(['amountSource' => 'field']));
    }
}
