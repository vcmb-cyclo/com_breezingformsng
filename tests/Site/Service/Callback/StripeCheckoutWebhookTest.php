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

namespace Vcmb\Component\BreezingformsNG\Tests\Site\Service\Callback;

use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Site\Service\Callback\StripeCheckoutWebhook;

final class StripeCheckoutWebhookTest extends TestCase
{
    public function testPaidSessionEventsGiveTheirRecord(): void
    {
        $webhook = new StripeCheckoutWebhook();

        self::assertSame(42, $webhook->paidRecordId('checkout.session.completed', 'paid', '42', '7', 7));
        self::assertSame(42, $webhook->paidRecordId('checkout.session.async_payment_succeeded', 'paid', '42', '7', 7));
    }

    public function testUnpaidOrOtherEventsRecordNothing(): void
    {
        $webhook = new StripeCheckoutWebhook();

        // A delayed method completes the session unpaid first.
        self::assertNull($webhook->paidRecordId('checkout.session.completed', 'unpaid', '42', '7', 7));
        self::assertNull($webhook->paidRecordId('checkout.session.async_payment_failed', 'unpaid', '42', '7', 7));
        self::assertNull($webhook->paidRecordId('payment_intent.succeeded', 'paid', '42', '7', 7));
    }

    public function testSessionsOfAnotherFormOrWithoutRecordAreIgnored(): void
    {
        $webhook = new StripeCheckoutWebhook();

        self::assertNull($webhook->paidRecordId('checkout.session.completed', 'paid', '42', '8', 7));
        self::assertNull($webhook->paidRecordId('checkout.session.completed', 'paid', '42', null, 7));
        self::assertNull($webhook->paidRecordId('checkout.session.completed', 'paid', null, '7', 7));
        self::assertNull($webhook->paidRecordId('checkout.session.completed', 'paid', '0', '7', 7));
        self::assertNull($webhook->paidRecordId('checkout.session.completed', 'paid', '4x', '7', 7));
    }
}
