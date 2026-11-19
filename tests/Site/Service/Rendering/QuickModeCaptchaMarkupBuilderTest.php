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

namespace Vcmb\Component\BreezingformsNG\Tests\Site\Service\Rendering\QuickMode;

use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Site\Service\Rendering\QuickMode\QuickModeCaptchaMarkupBuilder;

final class QuickModeCaptchaMarkupBuilderTest extends TestCase
{
    public function testBuildsCaptchaImageWithSuffix(): void
    {
        self::assertSame(
            '<img alt="" width="230" id="ff_capimgValue" class="ff_capimg" src="/captcha"/><br/><br/>' . "\n",
            (new QuickModeCaptchaMarkupBuilder())->buildImage('width="230" ', 'ff_capimgValue', 'ff_capimg', '/captcha', '<br/><br/>')
        );
    }

    public function testBuildsResponseInputWithLeadingStyle(): void
    {
        self::assertSame(
            '<input  style="width:200px;" autocomplete="off" class="ff_elem" type="text" name="bfCaptchaEntry" id="bfCaptchaEntry" />' . "\n",
            (new QuickModeCaptchaMarkupBuilder())->buildResponseInput(' style="width:200px;"', 'ff_elem')
        );
    }

    public function testEscapesImageAttributeValues(): void
    {
        $markup = (new QuickModeCaptchaMarkupBuilder())->buildImage(
            '',
            'captcha" onerror="alert(1)',
            'captcha" onerror="alert(1)',
            '/captcha" onerror="alert(1)'
        );

        self::assertStringNotContainsString('onerror="alert(1)', $markup);
        self::assertStringContainsString('captcha&quot; onerror=&quot;alert(1)', $markup);
    }

    public function testEscapesResponseInputClass(): void
    {
        $markup = (new QuickModeCaptchaMarkupBuilder())->buildResponseInput('', 'captcha" onfocus="alert(1)');

        self::assertStringNotContainsString('onfocus="alert(1)', $markup);
        self::assertStringContainsString('captcha&quot; onfocus=&quot;alert(1)', $markup);
    }

    public function testBuildsCaptchaImageWithEscapedAlternativeText(): void
    {
        $markup = (new QuickModeCaptchaMarkupBuilder())->buildImage('', 'ff_capimgValue', 'ff_capimg', '/captcha', '', 'Type "the" code');

        $document = new \DOMDocument();
        $document->loadHTML('<!doctype html>' . $markup);
        $image = $document->getElementsByTagName('img')->item(0);

        self::assertSame('Type "the" code', $image?->getAttribute('alt'));
    }
}
