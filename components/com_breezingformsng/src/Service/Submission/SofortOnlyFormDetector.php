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
        $types = $this->elementTypes($templateAreas);

        return in_array(self::SOFORT, $types, true) && array_intersect(self::PAYMENTS, $types) === [];
    }

    /** Whether the form keeps a supported payment element (PayPal or Stripe). */
    public function hasPayment(string $templateAreas): bool
    {
        return array_intersect(self::PAYMENTS, $this->elementTypes($templateAreas)) !== [];
    }

    public function requiresReplacementPayment(string $templateAreas): bool
    {
        $areas = json_decode($templateAreas, true);

        return $this->isSofortOnly($templateAreas)
            || (is_array($areas) && !empty($areas[0]['sofortPaymentRequired']) && !$this->hasPayment($templateAreas));
    }

    /** @return list<mixed> the internal type of every element */
    private function elementTypes(string $templateAreas): array
    {
        $areas = json_decode($templateAreas, true);
        $types = [];

        foreach (is_array($areas) ? $areas : [] as $area) {
            if (!is_array($area) || !is_array($area['elements'] ?? null)) {
                continue;
            }

            foreach ($area['elements'] as $element) {
                $types[] = is_array($element) ? ($element['internalType'] ?? null) : null;
            }
        }

        return $types;
    }
}
