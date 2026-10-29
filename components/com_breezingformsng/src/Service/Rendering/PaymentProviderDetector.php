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

/**
 * Detects whether a form contains a supported payment provider field.
 */
final class PaymentProviderDetector
{
    /**
     * @param iterable<object> $rows
     */
    public function hasSupportedProvider(iterable $rows): bool
    {
        foreach ($rows as $row) {
            if (in_array($row->type ?? null, ['PayPal', 'Stripe'], true)) {
                return true;
            }
        }

        return false;
    }
}
