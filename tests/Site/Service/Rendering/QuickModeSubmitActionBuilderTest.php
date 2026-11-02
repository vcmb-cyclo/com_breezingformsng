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

namespace Vcmb\Component\BreezingformsNG\Tests\Site\Service\Rendering;

use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Site\Service\Rendering\QuickMode\QuickModeSubmitActionBuilder;

final class QuickModeSubmitActionBuilderTest extends TestCase
{
    public function testStandardSubmitActionUsesLegacyValidationFunction(): void
    {
        self::assertSame(
            "ff_validate_submit(this, 'click')",
            (new QuickModeSubmitActionBuilder())->build(false, false)
        );
    }

    public function testOnePageSubmitActionUsesOnePageValidationFunction(): void
    {
        self::assertSame(
            "bf_validate_submit(this, 'click')",
            (new QuickModeSubmitActionBuilder())->build(true, false)
        );
    }

    public function testFlashUploadWrapsStandardSubmitAction(): void
    {
        self::assertSame(
            "if(typeof bfAjaxObject101 == 'undefined' && typeof bfReCaptchaLoaded == 'undefined'){bfDoFlashUpload()}else{ff_validate_submit(this, 'click')}" ,
            (new QuickModeSubmitActionBuilder())->build(false, true)
        );
    }

    public function testFlashUploadWrapsOnePageSubmitAction(): void
    {
        self::assertSame(
            "if(typeof bfAjaxObject101 == 'undefined' && typeof bfReCaptchaLoaded == 'undefined'){bfDoFlashUpload()}else{bf_validate_submit(this, 'click')}" ,
            (new QuickModeSubmitActionBuilder())->build(true, true)
        );
    }

    public function testPaymentButtonUsesTheSubmitOfItsLayout(): void
    {
        $builder = new QuickModeSubmitActionBuilder();

        self::assertStringEndsWith("else{ff_validate_submit(this, 'click')}", $builder->buildForPaymentButton(false));
        self::assertStringEndsWith("else{bf_validate_submit(this, 'click')}", $builder->buildForPaymentButton(true));
        self::assertStringContainsString("typeof bfDoFlashUpload != 'undefined'", $builder->buildForPaymentButton(false));
    }
}
