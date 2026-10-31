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

/** The amount a Stripe element charges when it comes from a form field. */
final class StripeAmount
{
    /** @param array<mixed> $options */
    public function comesFromField(array $options): bool
    {
        return ($options['amountSource'] ?? 'fixed') === 'field';
    }

    /**
     * The first submitted value of the field, in cents: null when it is
     * empty, zero or not an amount such as 12.99 or 12,99.
     *
     * @param mixed $submitted
     */
    public function toCents($submitted): ?int
    {
        $value = is_array($submitted) ? reset($submitted) : $submitted;

        if (!is_scalar($value) || !preg_match('/^(\d{1,9})(?:[.,](\d{1,2}))?$/', trim((string) $value), $parts)) {
            return null;
        }

        $cents = (int) $parts[1] * 100 + (int) str_pad($parts[2] ?? '', 2, '0');

        return $cents > 0 ? $cents : null;
    }
}
