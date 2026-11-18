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

/**
 * The captcha image is a PNG: the deprecations Securimage raises under PHP 8
 * must never be written into it, or the browser shows no security code.
 */
final class CaptchaCallbackTest extends TestCase
{
    private function source(): string
    {
        return (string) file_get_contents(
            __DIR__ . '/../../../../components/com_breezingformsng/src/Service/Callback/CaptchaCallback.php'
        );
    }

    public function testNoErrorTextIsWrittenIntoTheCaptchaResponse(): void
    {
        $source = $this->source();
        $create = substr($source, (int) strpos($source, 'private function createSecurimage'));

        self::assertStringContainsString("ini_set('display_errors', '0');", $create);
        self::assertStringContainsString('~E_DEPRECATED', $create);
        // Both settings come before the library is loaded and the image drawn.
        self::assertLessThan(
            (int) strpos($create, 'VendorHelper::load()'),
            (int) strpos($create, "ini_set('display_errors', '0');")
        );
    }

    public function testTheCodeIsKeptInTheJoomlaSessionAndTheBrowserCheckLeavesIt(): void
    {
        $source = $this->source();
        $check = substr($source, (int) strpos($source, 'public function check'));
        $check = substr($check, 0, (int) strpos($check, 'public function image'));

        self::assertStringContainsString("'captchaId' => CaptchaCodeStore::CAPTCHA_ID", $source);
        self::assertStringContainsString('new SecurimageSessionAdapter(', $source);
        // Without no_exit, Securimage exits before Joomla saves the session holding the code.
        self::assertStringContainsString("'no_exit' => true", $source);
        self::assertStringContainsString('->matches(', $check);
        self::assertStringContainsString(', false)', $check);
        self::assertStringNotContainsString('->check(', $check);

        $submission = (string) file_get_contents(
            __DIR__ . '/../../../../components/com_breezingformsng/src/Service/Submission/SubmissionEngine.php'
        );
        self::assertStringContainsString("getString('bfCaptchaEntry', ''), true)", $submission);
        self::assertStringNotContainsString('new Securimage(', $submission);
    }

    public function testEveryOutputBufferIsClearedBeforeTheResponse(): void
    {
        self::assertStringContainsString('while (ob_get_level() > 0)', $this->source());
    }
}
