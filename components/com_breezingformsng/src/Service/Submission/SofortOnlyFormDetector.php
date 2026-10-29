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
 * Detects a QuickMode form whose only payment element is the removed Sofort
 * element: without it, such a form would be accepted without any payment.
 */
final class SofortOnlyFormDetector
{
    private const SOFORT = 'bfSofortueberweisung';

    private const PAYMENTS = ['bfPayPal', 'bfStripe'];

    public function isSofortOnly(string $templateAreas): bool
    {
        $areas = json_decode($templateAreas, true);

        if (!is_array($areas)) {
            return false;
        }

        $hasSofort = false;

        foreach ($areas as $area) {
            if (!is_array($area) || !is_array($area['elements'] ?? null)) {
                continue;
            }

            foreach ($area['elements'] as $element) {
                $type = is_array($element) ? ($element['internalType'] ?? null) : null;

                if (in_array($type, self::PAYMENTS, true)) {
                    return false;
                }

                $hasSofort = $hasSofort || $type === self::SOFORT;
            }
        }

        return $hasSofort;
    }
}
