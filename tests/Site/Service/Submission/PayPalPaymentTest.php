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
use Vcmb\Component\BreezingformsNG\Site\Service\Submission\PayPalPayment;

final class PayPalPaymentTest extends TestCase
{
    private const SECRET = 'server-secret';

    /** @return array<string, array{string, bool}> */
    public static function selections(): array
    {
        return [
            'four parts' => ['Shirt|SKU1|12.50|2.00', true],
            'with shipping' => ['Shirt|SKU1|12.50|2.00|1', true],
            'with currency' => ['Shirt|SKU1|12.50|2.00|0|eur', true],
            'empty tax' => ['Shirt|SKU1|12.50|', true],
            'too few parts' => ['Shirt|12.50', false],
            'too many parts' => ['a|b|1|0|0|EUR|x', false],
            'text amount' => ['Shirt|SKU1|free|0', false],
            'negative amount' => ['Shirt|SKU1|-1|0', false],
            'three decimals' => ['Shirt|SKU1|1.999|0', false],
            'comma amount' => ['Shirt|SKU1|1,50|0', false],
            'bad currency' => ['Shirt|SKU1|1|0|0|EURO', false],
        ];
    }

    #[DataProvider('selections')]
    public function testFromSelection(string $selected, bool $valid): void
    {
        self::assertSame($valid, (new PayPalPayment())->fromSelection($selected) !== null);
    }

    public function testFromSelectionDefaultsTheCurrencyAndNormalisesShipping(): void
    {
        $choice = (new PayPalPayment())->fromSelection('A|B|5|1|7|');

        self::assertSame('USD', $choice['currencyCode']);
        self::assertSame('0', $choice['shipping']);
        self::assertNull((new PayPalPayment())->fromSelection('A|B|5|1')['currencyCode']);
    }

    public function testFromOptionsTreatsAnUnreadableAmountAsAFreeOne(): void
    {
        $payment = new PayPalPayment();

        self::assertSame(
            ['amount' => '10.5', 'tax' => '0', 'currencyCode' => 'EUR'],
            $payment->fromOptions(['amount' => '10,5', 'tax' => 'x', 'currencyCode' => 'EUR'])
        );
        self::assertSame('USD', $payment->fromOptions([])['currencyCode']);
        self::assertSame('0', $payment->fromOptions(['amount' => '1e9'])['amount']);
    }

    public function testASignedPaymentIsReadBack(): void
    {
        $payment = new PayPalPayment();
        $custom = $payment->sign(42, '12.5', '2', 'eur', self::SECRET);

        self::assertSame(
            ['amount' => '12.50', 'tax' => '2.00', 'currency' => 'EUR'],
            $payment->verify($custom, 42, self::SECRET)
        );
    }

    public function testAPaymentSignedForAnotherRecordIsRefused(): void
    {
        $payment = new PayPalPayment();

        self::assertNull($payment->verify($payment->sign(42, '12.50', '0', 'EUR', self::SECRET), 43, self::SECRET));
    }

    /** @return array<string, array{string}> */
    public static function tamperings(): array
    {
        return [
            'lower amount' => ['42|0.01|0.00|EUR|'],
            'other currency' => ['42|12.50|0.00|JPY|'],
            'no tax' => ['42|12.50|2.00|EUR|'],
        ];
    }

    #[DataProvider('tamperings')]
    public function testAnAlteredPaymentIsRefused(string $prefix): void
    {
        $payment = new PayPalPayment();
        $signature = substr($payment->sign(42, '12.50', '0', 'EUR', self::SECRET), -64);

        self::assertNull($payment->verify($prefix . $signature, 42, self::SECRET));
    }

    public function testMissingOrForeignSignaturesAreRefused(): void
    {
        $payment = new PayPalPayment();
        $custom = $payment->sign(42, '12.50', '0', 'EUR', self::SECRET);

        self::assertNull($payment->verify('', 42, self::SECRET));
        self::assertNull($payment->verify('42', 42, self::SECRET));
        self::assertNull($payment->verify($custom, 42, 'another-secret'));
        self::assertNull($payment->verify($custom, 42, ''));
        self::assertNull($payment->verify(' ' . $custom, 42, self::SECRET));
        self::assertNull($payment->verify($payment->sign(42, '12.50', '0', 'EUR', ''), 42, ''));
    }

    /** @return array<string, array{string, string, bool}> */
    public static function reports(): array
    {
        return [
            'exact total' => ['14.50', 'EUR', true],
            'currency in lower case' => ['14.50', 'eur', true],
            'less' => ['0.01', 'EUR', false],
            'more' => ['20.00', 'EUR', false],
            'amount without tax' => ['12.50', 'EUR', false],
            'other currency' => ['14.50', 'USD', false],
            'no gross' => ['', 'EUR', false],
            'text gross' => ['abc', 'EUR', false],
        ];
    }

    #[DataProvider('reports')]
    public function testMatches(string $gross, string $currency, bool $expected): void
    {
        self::assertSame(
            $expected,
            (new PayPalPayment())->matches(['amount' => '12.50', 'tax' => '2.00', 'currency' => 'EUR'], $gross, $currency)
        );
    }

    public function testAFreeAmountStillChecksTheCurrency(): void
    {
        $free = ['amount' => '0.00', 'tax' => '0.00', 'currency' => 'EUR'];

        self::assertTrue((new PayPalPayment())->matches($free, '5.00', 'EUR'));
        self::assertFalse((new PayPalPayment())->matches($free, '5.00', 'USD'));
    }
}
