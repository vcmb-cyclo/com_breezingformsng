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
use Vcmb\Component\BreezingformsNG\Site\Service\Callback\OptConfirmationPage;

if (!defined('_JEXEC')) {
    define('_JEXEC', 1);
}

final class OptConfirmationPageTest extends TestCase
{
    private const CSRF = '<input type="hidden" name="0123456789abcdef0123456789abcdef" value="1">';

    public function testThePageHoldsOnePostFormWithOneLabelledButton(): void
    {
        $html = (new OptConfirmationPage())->render(OptConfirmationPage::IN, '/index.php?option=com_breezingformsng', '42', 'aB3dE5fG7hJ9kL1mN3pQ', 'Please confirm.', 'Verify', self::CSRF);

        self::assertSame(1, substr_count($html, '<form '));
        self::assertStringContainsString('method="post"', $html);
        self::assertSame(1, substr_count($html, '<button '));
        self::assertStringContainsString('>Verify</button>', $html);
        self::assertStringContainsString('aria-labelledby="bf-opt-text"', $html);
        self::assertStringContainsString('<p id="bf-opt-text">Please confirm.</p>', $html);
    }

    public function testTheFormPostsTheLinkFieldsAndTheFormToken(): void
    {
        $html = (new OptConfirmationPage())->render(OptConfirmationPage::OUT, '/index.php', '42', 'aB3dE5fG7hJ9kL1mN3pQ', 't', 'b', self::CSRF);

        self::assertStringContainsString('<input type="hidden" name="opt_out" value="true">', $html);
        self::assertStringNotContainsString('name="opt_in"', $html);
        self::assertStringContainsString('name="id" value="42"', $html);
        self::assertStringContainsString('name="token" value="aB3dE5fG7hJ9kL1mN3pQ"', $html);
        self::assertStringContainsString(self::CSRF, $html);
    }

    public function testEveryValueIsEscaped(): void
    {
        $evil = '"><script>alert(1)</script>';
        $html = (new OptConfirmationPage())->render(OptConfirmationPage::IN, $evil, $evil, $evil, $evil, $evil, self::CSRF);

        self::assertStringNotContainsString('<script>', $html);
        self::assertStringContainsString('&lt;script&gt;', $html);
    }

    public function testAnUnknownModeFallsBackToTheOptIn(): void
    {
        $html = (new OptConfirmationPage())->render('x', '/', '1', 'aB3dE5fG7hJ9kL1mN3pQ', 't', 'b', self::CSRF);

        self::assertStringContainsString('name="opt_in"', $html);
    }
}
