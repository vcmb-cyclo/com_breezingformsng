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

namespace Vcmb\Component\BreezingformsNG\Tests\Site\Service\Callback;

use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Site\Service\Callback\PaymentDownloadPolicy;

final class PaymentDownloadPolicyTest extends TestCase
{
    public function testAllowsAttemptsBelowConfiguredLimit(): void
    {
        self::assertTrue((new PaymentDownloadPolicy())->canDownload(0, 3));
        self::assertTrue((new PaymentDownloadPolicy())->canDownload(2, 3));
    }

    public function testRejectsAttemptsAtOrAboveConfiguredLimit(): void
    {
        $policy = new PaymentDownloadPolicy();

        self::assertFalse($policy->canDownload(3, 3));
        self::assertFalse($policy->canDownload(4, 3));
    }
}
