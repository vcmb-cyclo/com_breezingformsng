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
 * The security code image is a clean JPEG that is never cached, its code is kept in the Joomla session,
 * and the browser check leaves that code for the submission.
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
        $clear = substr($source, (int) strpos($source, 'private function clearOutputBuffer'));

        // Both responses (the image and capResult) go through the buffer clearing.
        self::assertStringContainsString("ini_set('display_errors', '0');", $clear);
        self::assertSame(2, substr_count($source, '$this->clearOutputBuffer();'));
    }

    public function testTheImageIsNeverCachedAndItsCodeIsKeptInTheSession(): void
    {
        $source = $this->source();
        $image = substr($source, (int) strpos($source, 'public function image'));
        $image = substr($image, 0, (int) strpos($image, 'private function codeStore'));

        self::assertStringContainsString('no-store', $image);
        self::assertStringContainsString("'Content-Type', CaptchaImage::MIME_TYPE", $image);
        // The code is stored before the image is sent, so that the session holds it when the page is shown.
        self::assertLessThan(
            (int) strrpos($image, 'sendHeaders'),
            (int) strpos($image, '$this->codeStore()->store(')
        );
        self::assertStringNotContainsString('Securimage', $source);
    }

    public function testTheBrowserCheckLeavesTheCodeForTheSubmission(): void
    {
        $source = $this->source();
        $check = substr($source, (int) strpos($source, 'public function check'));
        $check = substr($check, 0, (int) strpos($check, 'public function image'));

        self::assertStringContainsString('->matches(', $check);
        self::assertStringContainsString(', false)', $check);

        $submission = (string) file_get_contents(
            __DIR__ . '/../../../../components/com_breezingformsng/src/Service/Submission/SubmissionEngine.php'
        );
        self::assertStringContainsString("getString('bfCaptchaEntry', ''), true)", $submission);
        self::assertStringNotContainsString('Securimage', $submission);
    }

    public function testEveryOutputBufferIsClearedBeforeTheResponse(): void
    {
        self::assertStringContainsString('while (ob_get_level() > 0)', $this->source());
    }

    public function testTheImageIsThrottledBeforeItIsDrawn(): void
    {
        $source = $this->source();
        $image = substr($source, (int) strpos($source, 'public function image'));

        self::assertStringContainsString('->allowIssue()', $image);
        self::assertStringContainsString("'status', '429'", $image);
        self::assertLessThan((int) strpos($image, 'new CaptchaImage'), (int) strpos($image, '->allowIssue()'));
    }
}
