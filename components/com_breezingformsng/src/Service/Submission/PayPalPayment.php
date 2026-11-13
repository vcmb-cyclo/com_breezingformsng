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

namespace Vcmb\Component\BreezingformsNG\Site\Service\Submission;

/**
 * What a PayPal payment is expected to be, decided by the server.
 *
 * The browser posts the PayPal form itself, so it can change the amount, tax
 * and currency on the way. The server therefore signs what it expects in the
 * "custom" field, which PayPal returns unchanged, and compares it with what
 * PayPal reports.
 */
final class PayPalPayment
{
    /** Names of the list field that offers the payments, the current one first. */
    public const SELECT_FIELDS = ['bfPaymentSelect', 'PayPalSelect'];

    private const MONEY = '/^\d{1,9}(?:\.\d{1,2})?$/';

    /** PayPal payments are accepted only after settlement is reported. */
    public function isCompleted(string $status): bool
    {
        return $status === 'Completed';
    }

    /**
     * The payment a "TEXT1|TEXT2|AMOUNT|TAX[|SHIPPING[|CURRENCY]]" choice stands
     * for, or null when it is not a valid one.
     *
     * @return array{itemname: string, itemnumber: string, amount: string, tax: string, shipping: ?string, currencyCode: ?string}|null
     */
    public function fromSelection(string $selected): ?array
    {
        $parts = explode('|', $selected);

        if (count($parts) < 4 || count($parts) > 6) {
            return null;
        }

        $amount = trim($parts[2]);
        $tax = trim($parts[3]) === '' ? '0' : trim($parts[3]);
        $currency = null;

        if (isset($parts[5])) {
            $currency = strtoupper(trim($parts[5])) === '' ? 'USD' : strtoupper(trim($parts[5]));

            if (!preg_match('/^[A-Z]{3}$/', $currency)) {
                return null;
            }
        }

        if (!preg_match(self::MONEY, $amount) || !preg_match(self::MONEY, $tax)) {
            return null;
        }

        return [
            'itemname' => $parts[0],
            'itemnumber' => $parts[1],
            'amount' => $amount,
            'tax' => $tax,
            'shipping' => isset($parts[4]) ? ($parts[4] === '1' ? '1' : '0') : null,
            'currencyCode' => $currency,
        ];
    }

    /**
     * The fixed payment an element is configured with. An amount that is not a
     * plain number counts as zero, which lets the payer choose it.
     *
     * @param array<mixed> $options
     * @return array{amount: string, tax: string, currencyCode: string}
     */
    public function fromOptions(array $options): array
    {
        $money = static function (mixed $value): string {
            $value = str_replace(',', '.', trim((string) $value));

            return preg_match(self::MONEY, $value) ? $value : '0';
        };
        $currency = strtoupper(trim((string) ($options['currencyCode'] ?? '')));

        return [
            'amount' => $money($options['amount'] ?? ''),
            'tax' => $money($options['tax'] ?? ''),
            'currencyCode' => $currency === '' ? 'USD' : $currency,
        ];
    }

    /** The value of the "custom" field: the record and what it must cost, signed. */
    public function sign(int $recordId, string $amount, string $tax, string $currency, string $secret): string
    {
        $body = $recordId . '|' . $this->normalise($amount) . '|' . $this->normalise($tax) . '|' . strtoupper($currency);

        return $body . '|' . hash_hmac('sha256', $body, $secret);
    }

    /**
     * What the signed "custom" field says the record must cost, or null when
     * it is missing, altered or made for another record.
     *
     * @return array{amount: string, tax: string, currency: string}|null
     */
    public function verify(string $custom, int $recordId, string $secret): ?array
    {
        $parts = explode('|', $custom);

        if (count($parts) !== 5 || $secret === '' || (int) $parts[0] !== $recordId || (string) $recordId !== $parts[0]) {
            return null;
        }

        $body = implode('|', array_slice($parts, 0, 4));

        if (!hash_equals(hash_hmac('sha256', $body, $secret), $parts[4])) {
            return null;
        }

        return ['amount' => $parts[1], 'tax' => $parts[2], 'currency' => $parts[3]];
    }

    /**
     * Whether what PayPal reports is what was expected. An expected amount of
     * zero lets the payer choose the amount, as PayPal does; the currency is
     * always checked.
     *
     * @param array{amount: string, tax: string, currency: string} $expected
     */
    public function matches(array $expected, string $paidGross, string $paidCurrency): bool
    {
        if (strtoupper(trim($paidCurrency)) !== $expected['currency']) {
            return false;
        }

        $amount = $this->cents($expected['amount']);

        if ($amount === 0) {
            return true;
        }

        $paid = $this->cents($paidGross);

        return $paid !== null && $paid === $amount + (int) $this->cents($expected['tax']);
    }

    private function normalise(string $money): string
    {
        $cents = $this->cents($money) ?? 0;

        return intdiv($cents, 100) . '.' . str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }

    private function cents(string $money): ?int
    {
        $money = trim($money);

        if (!preg_match(self::MONEY, $money)) {
            return null;
        }

        [$whole, $fraction] = array_pad(explode('.', $money), 2, '');

        return (int) $whole * 100 + (int) str_pad($fraction, 2, '0');
    }
}
