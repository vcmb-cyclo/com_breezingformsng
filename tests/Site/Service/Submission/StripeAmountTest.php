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
    /** @return array<string, array{mixed, string, ?int}> */
    public static function values(): array
    {
        return [
            'dot' => ['12.99', 'EUR', 1299],
            'comma' => ['12,99', 'EUR', 1299],
            'whole' => ['45', 'EUR', 4500],
            'one decimal' => ['12.5', 'EUR', 1250],
            'spaces' => [' 8.00 ', 'EUR', 800],
            'first of a list' => [['7.50', '99'], 'EUR', 750],
            'empty' => ['', 'EUR', null],
            'empty list' => [[], 'EUR', null],
            'zero' => ['0.00', 'EUR', null],
            'negative' => ['-5', 'EUR', null],
            'text' => ['free', 'EUR', null],
            'text after' => ['12abc', 'EUR', null],
            'three decimals' => ['1.999', 'EUR', null],
            'thousands' => ['1,000.50', 'EUR', null],
            'placeholder' => ['{Montant:value}', 'EUR', null],
            'under the euro minimum' => ['0.49', 'EUR', null],
            'euro minimum' => ['0.50', 'EUR', 50],
            'under the pound minimum' => ['0.29', 'GBP', null],
            'pound minimum' => ['0.30', 'GBP', 30],
            'over the maximum' => ['1000000.00', 'EUR', null],
            'maximum' => ['999999.99', 'EUR', 99999999],
            'zero decimal' => ['1000', 'JPY', 1000],
            'zero decimal with zeros' => ['1000.00', 'jpy', 1000],
            'zero decimal with cents' => ['1000.50', 'JPY', null],
            'three decimal' => ['5.25', 'KWD', 5250],
            'other currency' => ['0.10', 'CHF', 10],
        ];
    }

    #[DataProvider('values')]
    public function testToMinorUnits(mixed $submitted, string $currency, ?int $expected): void
    {
        self::assertSame($expected, (new StripeAmount())->toMinorUnits($submitted, $currency));
    }

    public function testAFixedAmountFollowsTheCurrency(): void
    {
        $amount = new StripeAmount();

        self::assertSame(1299, $amount->fixedToMinorUnits('12.99', 'EUR'));
        self::assertSame(1000, $amount->fixedToMinorUnits('1000', 'JPY'));
        self::assertSame(5250, $amount->fixedToMinorUnits('5.25', 'KWD'));
    }

    public function testACurrencyLeftEmptyIsTheEuro(): void
    {
        self::assertSame('EUR', (new StripeAmount())->currency(['currencyCode' => ' ']));
        self::assertSame('USD', (new StripeAmount())->currency(['currencyCode' => 'usd']));
    }

    public function testOnlyAValueOfTheListIsAccepted(): void
    {
        $amount = new StripeAmount();
        $list = "0;Small;10.00\n1;Large;20.00";

        self::assertTrue($amount->isListed(['20.00'], $list));
        self::assertFalse($amount->isListed(['0.50'], $list));
        self::assertFalse($amount->isListed([], $list));
    }

    public function testAnElementWithoutTheSettingKeepsItsFixedAmount(): void
    {
        $amount = new StripeAmount();

        self::assertFalse($amount->comesFromField(['amount' => '10']));
        self::assertFalse($amount->comesFromField(['amountSource' => 'fixed']));
        self::assertTrue($amount->comesFromField(['amountSource' => 'field']));
    }
}
