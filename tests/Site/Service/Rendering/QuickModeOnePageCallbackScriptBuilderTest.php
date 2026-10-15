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
use Vcmb\Component\BreezingformsNG\Site\Service\Rendering\QuickMode\QuickModeOnePageCallbackScriptBuilder;

final class QuickModeOnePageCallbackScriptBuilderTest extends TestCase
{
    public function testRestoreSubmitButtonMatchesTheExistingCallback(): void
    {
        $script = (new QuickModeOnePageCallbackScriptBuilder())->restoreSubmitButton("\n");

        self::assertStringContainsString('function bf_restore_submitbutton(){', $script);
        self::assertStringContainsString('replaceWith( JQuery(cloned_submit) );', $script);
        self::assertStringContainsString('bfSubmitSpinner.bind("#bfSubmitButton");', $script);
    }

    public function testCloseRemodalUsesCartUrlOrTheEncodedRedirect(): void
    {
        $script = (new QuickModeOnePageCallbackScriptBuilder())->closeRemodal('"https://example.test/form"', "\n");

        self::assertStringContainsString('location.href = crbc_cart_url;', $script);
        self::assertStringContainsString('location.href = "https://example.test/form";', $script);
    }
}
