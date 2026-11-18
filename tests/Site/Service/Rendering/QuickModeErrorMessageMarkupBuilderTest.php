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
use Vcmb\Component\BreezingformsNG\Site\Service\Rendering\QuickMode\QuickModeErrorMessageMarkupBuilder;

final class QuickModeErrorMessageMarkupBuilderTest extends TestCase
{
    public function testBuildsHiddenBootstrapErrorMessageContainer(): void
    {
        self::assertSame(
            '<div class="bfErrorMessage alert alert-error" role="alert" aria-live="assertive" style="display:none">'
                . '<strong class="bfErrorSummaryTitle" data-title-singular="1 field to correct" data-title-plural="%d fields to correct"></strong>'
                . '<ul class="bfErrorSummaryList"></ul></div>' . "\n",
            QuickModeErrorMessageMarkupBuilder::build('alert', 'alert-error', '1 field to correct', '%d fields to correct', "\n")
        );
    }

    public function testEscapesTranslatedSummaryTitles(): void
    {
        $markup = QuickModeErrorMessageMarkupBuilder::build('alert', 'alert-error', '1 "field"', '<script>%d</script>', "\n");

        self::assertStringContainsString('data-title-singular="1 &quot;field&quot;"', $markup);
        self::assertStringContainsString('data-title-plural="&lt;script&gt;%d&lt;/script&gt;"', $markup);
    }
}
