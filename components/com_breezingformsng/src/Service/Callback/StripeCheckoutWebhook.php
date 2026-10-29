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

namespace Vcmb\Component\BreezingformsNG\Site\Service\Callback;

/**
 * Decides which record a verified Stripe Checkout webhook event pays.
 */
final class StripeCheckoutWebhook
{
    /** Events after which a Checkout Session can be paid; the second one ends delayed methods. */
    private const PAID_EVENTS = [
        'checkout.session.completed',
        'checkout.session.async_payment_succeeded',
    ];

    public function matchesReturnRecord(?string $clientReferenceId, int $recordId): bool
    {
        return $recordId > 0 && $clientReferenceId === (string) $recordId;
    }

    /**
     * Returns the record a paid Checkout Session of this form was opened for,
     * or null when the event must not record a payment.
     */
    public function paidRecordId(
        string $eventType,
        ?string $paymentStatus,
        ?string $clientReferenceId,
        ?string $formIdMetadata,
        int $formId
    ): ?int {
        if (!in_array($eventType, self::PAID_EVENTS, true) || $paymentStatus !== 'paid') {
            return null;
        }

        if ($formIdMetadata !== (string) $formId || $clientReferenceId === null) {
            return null;
        }

        $recordId = filter_var($clientReferenceId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return $recordId === false ? null : $recordId;
    }
}
