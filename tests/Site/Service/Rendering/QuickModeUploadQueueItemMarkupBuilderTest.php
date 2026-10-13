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

namespace Vcmb\Component\BreezingformsNG\Tests\Site\Service\Rendering\QuickMode;

use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Site\Service\Rendering\QuickMode\QuickModeUploadQueueItemMarkupBuilder;

final class QuickModeUploadQueueItemMarkupBuilderTest extends TestCase
{
    public function testBuildsStandardQueueItemMarkup(): void
    {
        $script = QuickModeUploadQueueItemMarkupBuilder::build(62, '/cancel.png', false, false);

        self::assertStringContainsString("JQuery('#bfFlashFileQueue62').append(", $script);
        self::assertStringContainsString('src="/cancel.png"', $script);
        self::assertStringContainsString('style="cursor: pointer; padding-right: 10px;" />', $script);
        self::assertStringContainsString(
            "(iOS ? '' : files[i].name.replace(/[/\\?%*:|\"<>]/g, ''))",
            $script
        );
    }

    public function testBuildsConditionalNameVariant(): void
    {
        $script = QuickModeUploadQueueItemMarkupBuilder::build(63, '/cancel.png', true, false);

        self::assertStringContainsString(
            "files[i].name.replace(/[/\\?%*:|\"<>]/g, '') ? files[i].name.replace",
            $script
        );
    }

    public function testBuildsMobileBorderVariant(): void
    {
        $script = QuickModeUploadQueueItemMarkupBuilder::build(64, '/cancel.png', false, true);

        self::assertStringContainsString('style="cursor: pointer; padding-right: 10px;" border="0"/>', $script);
        self::assertStringNotContainsString('" />', $script);
    }

    public function testEscapesCancelImagePathInHtmlAndJavaScript(): void
    {
        $script = QuickModeUploadQueueItemMarkupBuilder::build(
            65,
            "/cancel\"' onerror='alert(1)",
            false,
            false
        );

        self::assertStringContainsString('src="/cancel&quot;&#039; onerror=&#039;alert(1)"', $script);
    }
}
