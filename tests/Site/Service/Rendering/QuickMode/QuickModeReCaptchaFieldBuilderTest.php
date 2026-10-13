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

namespace Vcmb\Component\BreezingformsNG\Tests\Site\Service\Rendering\QuickMode {

require_once __DIR__ . '/joomla-uri-stub.php';
require_once __DIR__ . '/joomla-cmsapplication-stub.php';

use Joomla\CMS\Application\CMSApplication;
use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Site\Service\Rendering\QuickMode\QuickModeReCaptchaFieldBuilder;

final class QuickModeReCaptchaFieldBuilderTest extends TestCase
{
    public function testReportsAReCaptchaFieldWithoutAPublicKey(): void
    {
        $html = (new QuickModeReCaptchaFieldBuilder())->build(
            [],
            new CMSApplication(),
            false,
            false,
            false
        );

        self::assertSame(
            '<span class="bfCaptcha">' . "\n"
            . 'WARNING: No public key given for ReCaptcha element!'
            . '</span>' . "\n",
            $html
        );
    }

    public function testBuildsVisibleReCaptchaMarkupAndRegistersItsScripts(): void
    {
        $application = new CMSApplication();

        $html = (new QuickModeReCaptchaFieldBuilder())->build(
            [
                'pubkey' => 'public-key',
                'theme' => '',
                'size' => '',
                'invisibleCaptcha' => false,
            ],
            $application,
            false,
            false,
            false,
            'control-group',
            'controls',
            true
        );

        self::assertStringContainsString('<div id="newrecaptcha"></div>', $html);
        self::assertStringContainsString('<div class="g-recaptcha" data-sitekey="public-key"></div>', $html);
        self::assertStringContainsString(
            'bfInitVisibleReCaptcha({"sitekey":"public-key","theme":"light","size":"normal","resetOnRerender":false});',
            $html
        );
        self::assertSame(2, $this->registeredScriptCount($application));
    }

    public function testBuildsInvisibleInlineReCaptchaWithCallbackFlags(): void
    {
        $application = new CMSApplication();

        $html = (new QuickModeReCaptchaFieldBuilder())->build(
            [
                'pubkey' => 'public-key',
                'theme' => 'invisible_inline',
                'invisibleCaptcha' => true,
            ],
            $application,
            true,
            false,
            true,
            'control-group',
            'controls'
        );

        self::assertStringContainsString('bfInvisibleReCaptchaContainer', $html);
        self::assertStringContainsString('bfInvisibleReCaptcha', $html);
        self::assertStringContainsString(
            'bfInitInvisibleReCaptcha({"sitekey":"public-key","badge":"inline","hasFlashUpload":true,"resetFlagOnCallback":true});',
            $html
        );
        self::assertSame(1, $this->registeredScriptCount($application));
        self::assertStringContainsString('api.js?onload=onloadBFNewRecaptchaCallback&render=explicit', $html);
    }

    private function registeredScriptCount(CMSApplication $application): int
    {
        return count($this->registeredAssets($application));
    }

    /**
     * @return array<string, true>
     */
    private function registeredAssets(CMSApplication $application): array
    {
        $manager = $application->getDocument()->getWebAssetManager();
        $reflection = new \ReflectionProperty($manager, 'registered');
        $reflection->setAccessible(true);

        /** @var array<string, true> $registered */
        $registered = $reflection->getValue($manager);

        return $registered;
    }
}
}
