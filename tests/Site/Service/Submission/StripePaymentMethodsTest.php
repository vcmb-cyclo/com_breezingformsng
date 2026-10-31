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

use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Site\Service\Submission\StripePaymentMethods;

final class StripePaymentMethodsTest extends TestCase
{
    public function testAutomaticModeExcludesNothing(): void
    {
        self::assertSame([], (new StripePaymentMethods())->excluded(['paymentMethodsMode' => 'auto', 'klarna' => false]));
    }

    public function testManualModeExcludesTheMethodsLeftOff(): void
    {
        $methods = new StripePaymentMethods();

        self::assertSame(['ideal'], $methods->excluded(['paymentMethodsMode' => 'manual', 'klarna' => true]));
        self::assertSame([], $methods->excluded(['paymentMethodsMode' => 'manual', 'klarna' => true, 'ideal' => true]));
    }

    public function testAnElementSavedBeforeTheModeKeepsItsOptions(): void
    {
        self::assertSame(['klarna', 'ideal'], (new StripePaymentMethods())->excluded([]));
    }
}
