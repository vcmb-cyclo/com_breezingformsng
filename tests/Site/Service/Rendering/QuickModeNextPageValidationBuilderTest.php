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
use Vcmb\Component\BreezingformsNG\Site\Service\Rendering\QuickMode\QuickModeNextPageValidationBuilder;

final class QuickModeNextPageValidationBuilderTest extends TestCase
{
    public function testBuildsNextPageValidationCallback(): void
    {
        $script = (new QuickModeNextPageValidationBuilder())->build("\n");

        self::assertStringContainsString('function bf_validate_nextpage(page)', $script);
        self::assertStringContainsString('error = ff_validation(ff_currentpage);', $script);
        self::assertStringContainsString('bfSubmitSpinner.stop();', $script);
        self::assertStringContainsString('JQuery("#bfPage"+(page)).ScrollTo({offsetTop: 50});', $script);
        self::assertStringContainsString('ff_initialize("pageentry");', $script);
        self::assertStringContainsString('css("opacity","1")', $script);
    }

    public function testTheScriptHoldsNoLiteralEscapeSequence(): void
    {
        // A "\t" written in a single-quoted PHP string reaches the page as a
        // backslash and a letter, which is a JavaScript syntax error.
        self::assertStringNotContainsString('\\', (new QuickModeNextPageValidationBuilder())->build("\n"));
    }
}
