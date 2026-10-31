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

/** The amount a Stripe element charges, in the smallest unit of its currency. */
final class StripeAmount
{
    private const ZERO_DECIMAL = ['BIF', 'CLP', 'DJF', 'GNF', 'JPY', 'KMF', 'KRW', 'MGA', 'PYG', 'RWF', 'UGX', 'VND', 'VUV', 'XAF', 'XOF', 'XPF'];

    private const THREE_DECIMAL = ['BHD', 'JOD', 'KWD', 'OMR', 'TND'];

    /** Stripe's lowest charge, in the smallest unit, for the usual currencies. */
    private const MINIMUM = ['EUR' => 50, 'USD' => 50, 'GBP' => 30];

    private const MAXIMUM = 99999999;

    /** @param array<mixed> $options */
    public function comesFromField(array $options): bool
    {
        return ($options['amountSource'] ?? 'fixed') === 'field';
    }

    /** @param array<mixed> $options */
    public function currency(array $options): string
    {
        return strtoupper(trim((string) ($options['currencyCode'] ?? ''))) ?: 'EUR';
    }

    /**
     * The first submitted value in the smallest unit of the currency: null
     * when it is empty, not an amount such as 12.99 or 12,99, or outside what
     * Stripe charges.
     *
     * @param mixed $submitted
     */
    public function toMinorUnits($submitted, string $currency): ?int
    {
        $value = is_array($submitted) ? reset($submitted) : $submitted;

        if (!is_scalar($value) || !preg_match('/^(\d{1,9})(?:[.,](\d{1,2}))?$/', trim((string) $value), $parts)) {
            return null;
        }

        $cents = str_pad($parts[2] ?? '', 2, '0');

        if ($this->isZeroDecimal($currency) && (int) $cents !== 0) {
            return null;
        }

        $units = (int) $parts[1] * $this->factor($currency) + (int) $cents * (int) ($this->factor($currency) / 100);

        return $units >= (self::MINIMUM[strtoupper($currency)] ?? 1) && $units <= self::MAXIMUM ? $units : null;
    }

    /** The smallest units of a fixed amount, whatever Stripe will say of it. */
    public function fixedToMinorUnits(mixed $amount, string $currency): int
    {
        return (int) round(floatval($amount) * $this->factor($currency));
    }

    /**
     * Whether a list of "selected;title;value" lines offers this value: what
     * the server can check of a value that comes from the browser.
     */
    public function isListed(mixed $submitted, string $list): bool
    {
        $value = is_array($submitted) ? reset($submitted) : $submitted;

        if (!is_scalar($value)) {
            return false;
        }

        foreach (preg_split('/\r\n|\r|\n/', $list) ?: [] as $line) {
            $parts = explode(';', $line, 3);

            if (trim($parts[2] ?? '') === trim((string) $value)) {
                return true;
            }
        }

        return false;
    }

    private function isZeroDecimal(string $currency): bool
    {
        return in_array(strtoupper($currency), self::ZERO_DECIMAL, true);
    }

    private function factor(string $currency): int
    {
        if ($this->isZeroDecimal($currency)) {
            return 1;
        }

        return in_array(strtoupper($currency), self::THREE_DECIMAL, true) ? 1000 : 100;
    }
}
