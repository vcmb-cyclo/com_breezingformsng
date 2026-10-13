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
use Vcmb\Component\BreezingformsNG\Site\Service\Rendering\CaptchaLegacyValidationScriptBuilder;
use Vcmb\Component\BreezingformsNG\Site\Service\Rendering\CaptchaReCaptchaValidationScriptBuilder;
use Vcmb\Component\BreezingformsNG\Site\Service\Rendering\CaptchaSupportBuilder;
use Vcmb\Component\BreezingformsNG\Site\Service\Rendering\CaptchaValidationRowSelector;
use Vcmb\Component\BreezingformsNG\Site\Service\Rendering\CaptchaValidationScriptBuilder;

require_once __DIR__ . '/QuickMode/joomla-route-stub.php';

final class CaptchaValidationScriptBuilderTest extends TestCase
{
    public function testUsesDefaultCallbackWhenTheFormHasNoCaptcha(): void
    {
        $script = $this->builder()->build(
            'https://example.test',
            false,
            7,
            [(object) ['type' => 'Text', 'page' => 1]],
            1,
            'Missing CAPTCHA'
        );

        self::assertSame(
            'function bfCheckCaptcha(){if(checkFileExtensions())ff_submitForm2();}',
            $script
        );
    }

    public function testBuildsLegacyCaptchaScriptForTheSelectedCaptchaRow(): void
    {
        $script = $this->builder()->build(
            'https://example.test',
            false,
            7,
            [(object) ['type' => 'Captcha', 'page' => 3]],
            1,
            'Missing CAPTCHA'
        );

        self::assertStringContainsString('function bfCheckCaptcha()', $script);
        self::assertStringContainsString('ff_switchpage(3)', $script);
        self::assertStringContainsString('https://example.test/index.php?option=com_breezingformsng&bfCaptcha=1&bfMathRandom=', $script);
    }

    public function testBuildsReCaptchaScriptForTheSelectedReCaptchaRow(): void
    {
        $script = $this->builder()->build(
            'https://example.test',
            false,
            7,
            [(object) ['type' => 'ReCaptcha', 'page' => 4]],
            1,
            'Missing CAPTCHA'
        );

        self::assertStringContainsString('var bfReCaptchaLoaded = true;', $script);
        self::assertStringContainsString('ff_switchpage(4)', $script);
        self::assertStringContainsString('form=7', $script);
    }

    private function builder(): CaptchaValidationScriptBuilder
    {
        return new CaptchaValidationScriptBuilder(
            new CaptchaSupportBuilder(),
            new CaptchaValidationRowSelector(),
            new CaptchaLegacyValidationScriptBuilder(),
            new CaptchaReCaptchaValidationScriptBuilder()
        );
    }
}
