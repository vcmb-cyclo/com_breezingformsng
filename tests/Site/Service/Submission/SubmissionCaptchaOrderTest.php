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

/**
 * Characterisation of the order of the steps of a submission. submit() cannot be run without a
 * Joomla runtime, so the order is read from the source: a failed captcha must come before any
 * upload or signature is written (SEC-006).
 */
final class SubmissionCaptchaOrderTest extends TestCase
{
    public function testTheCaptchaIsCheckedBeforeTheDataAndFilesAreCollected(): void
    {
        $source = (string) file_get_contents(
            dirname(__DIR__, 4) . '/components/com_breezingformsng/src/Service/Submission/SubmissionEngine.php'
        );

        $captcha = strpos($source, '$captchaCodes->matches(');
        $recaptcha = strpos($source, '$this->recaptchaVerifier->verify(');
        $collect = strpos($source, '$this->collectSubmitdata($cbResult);');

        self::assertNotFalse($captcha);
        self::assertNotFalse($recaptcha);
        self::assertNotFalse($collect);
        self::assertLessThan($collect, $captcha);
        self::assertLessThan($collect, $recaptcha);
    }
}
