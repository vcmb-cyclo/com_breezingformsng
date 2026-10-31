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

/** The payment methods a Stripe element keeps off the Stripe Checkout page. */
final class StripePaymentMethods
{
    /**
     * In automatic mode Stripe decides alone. An element saved before the
     * mode existed keeps its options, as in the manual mode.
     *
     * @param array<mixed> $options
     * @return list<string>
     */
    public function excluded(array $options): array
    {
        if (($options['paymentMethodsMode'] ?? 'manual') === 'auto') {
            return [];
        }

        return array_keys(array_filter([
            'klarna' => empty($options['klarna']),
            'ideal' => empty($options['ideal']),
        ]));
    }
}
