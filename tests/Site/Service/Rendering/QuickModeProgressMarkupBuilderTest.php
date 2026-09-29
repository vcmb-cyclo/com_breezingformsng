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
use Vcmb\Component\BreezingformsNG\Site\Service\Rendering\QuickMode\QuickModeProgressMarkupBuilder;

final class QuickModeProgressMarkupBuilderTest extends TestCase
{
    public function testBuildsProgressWidgetWithThankYouPageAdjustment(): void
    {
        $markup = QuickModeProgressMarkupBuilder::build('progress', 'bar', true);

        self::assertStringContainsString('class="progress"', $markup);
        self::assertStringContainsString('class="bar"', $markup);
        self::assertStringContainsString('var pages = JQuery(".bfPage").length-1;', $markup);
        self::assertStringContainsString('function bfUpdateProgress()', $markup);
        self::assertStringContainsString('setInterval("bfUpdateProgress()", 500);', $markup);
    }

    public function testBuildsProgressWidgetWithoutThankYouPageAdjustment(): void
    {
        self::assertStringContainsString(
            'var pages = JQuery(".bfPage").length;',
            QuickModeProgressMarkupBuilder::build('progress', 'bar', false)
        );
    }
}
