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
use Vcmb\Component\BreezingformsNG\Site\Service\Rendering\QuickMode\QuickModeReCaptchaInitScriptBuilder;

final class QuickModeReCaptchaInitScriptBuilderTest extends TestCase
{
    public function testNormalizesVisibleConfigurationDefaults(): void
    {
        self::assertSame([
            'sitekey' => 'public-key',
            'theme' => 'light',
            'size' => 'normal',
            'resetOnRerender' => false,
        ], (new QuickModeReCaptchaInitScriptBuilder())->visibleConfiguration([
            'pubkey' => 'public-key',
            'theme' => ' ',
        ], false));
    }

    public function testNormalizesInvisibleConfigurationBadgeAndFlags(): void
    {
        self::assertSame([
            'sitekey' => 'public-key',
            'badge' => '',
            'hasFlashUpload' => true,
            'resetFlagOnCallback' => true,
        ], (new QuickModeReCaptchaInitScriptBuilder())->invisibleConfiguration([
            'pubkey' => 'public-key',
            'theme' => 'invisible_red',
        ], true, true));
    }

    public function testInvisibleConfigurationNamesOnlyANonDefaultSubmitFunction(): void
    {
        $builder = new QuickModeReCaptchaInitScriptBuilder();
        $field = ['pubkey' => 'public-key', 'theme' => 'invisible_inline'];

        self::assertArrayNotHasKey('submitFunction', $builder->invisibleConfiguration($field, false, true));
        self::assertSame(
            'bf_validate_submit',
            $builder->invisibleConfiguration($field, false, true, 'bf_validate_submit')['submitFunction']
        );
    }

    public function testBuildsTheVisibleGoogleApiUrlForTheActiveLanguage(): void
    {
        $builder = new QuickModeReCaptchaInitScriptBuilder();

        self::assertSame(
            'https://www.google.com/recaptcha/api.js?hl=fr&onload=onloadBFNewRecaptchaCallback&render=explicit',
            $builder->visibleApiUrl('fr-FR')
        );
        self::assertSame(
            'https://www.google.com/recaptcha/api.js?hl=fr&onload=onloadBFNewRecaptchaCallback&render=explicit',
            $builder->visibleApiUrl('fr')
        );
        self::assertSame(
            'https://www.google.com/recaptcha/api.js?hl=zh&onload=onloadBFNewRecaptchaCallback&render=explicit',
            $builder->visibleApiUrl('zh-Hant')
        );
    }

    public function testBuildsTheInvisibleGoogleApiUrl(): void
    {
        self::assertSame(
            'https://www.google.com/recaptcha/api.js?onload=onloadBFNewRecaptchaCallback&render=explicit',
            (new QuickModeReCaptchaInitScriptBuilder())->invisibleApiUrl()
        );
    }

    public function testBuildsVisibleRecaptchaInitializationScript(): void
    {
        self::assertSame(
            '<script data-usercentrics="reCAPTCHA" type="text/javascript">'
            . 'bfInitVisibleReCaptcha({"sitekey":"public-key","theme":"light","size":"normal",'
            . '"resetOnRerender":false});</script>',
            (new QuickModeReCaptchaInitScriptBuilder())->visible([
                'sitekey' => 'public-key',
                'theme' => 'light',
                'size' => 'normal',
                'resetOnRerender' => false,
            ])
        );
    }

    public function testJsonEncodingProtectsConfigurationValues(): void
    {
        $script = (new QuickModeReCaptchaInitScriptBuilder())->visible([
            'sitekey' => 'key";alert(1);//',
            'theme' => 'dark',
        ]);

        self::assertStringContainsString('key\\";alert(1);\\/\\/', $script);
        self::assertStringNotContainsString('key";alert(1);//', $script);
    }

    public function testEncodesInvisibleRecaptchaConfiguration(): void
    {
        self::assertSame(
            '{"sitekey":"public-key","badge":"bottomright","hasFlashUpload":false,"resetFlagOnCallback":true}',
            (new QuickModeReCaptchaInitScriptBuilder())->encode([
                'sitekey' => 'public-key',
                'badge' => 'bottomright',
                'hasFlashUpload' => false,
                'resetFlagOnCallback' => true,
            ])
        );
    }
}
