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

namespace Vcmb\Component\BreezingformsNG\Tests\Site\Service\Rendering;

use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Site\Service\Rendering\PaymentProviderDetector;

final class PaymentProviderDetectorTest extends TestCase
{
    public function testDetectsEachSupportedProvider(): void
    {
        $detector = new PaymentProviderDetector();

        foreach (['PayPal', 'Stripe'] as $type) {
            self::assertTrue($detector->hasSupportedProvider([(object) ['type' => $type]]));
        }
    }

    public function testIgnoresUnsupportedAndEmptyRows(): void
    {
        self::assertFalse((new PaymentProviderDetector())->hasSupportedProvider([
            (object) ['type' => 'Text'],
            (object) [],
        ]));
        self::assertFalse((new PaymentProviderDetector())->hasSupportedProvider([]));
    }
}
