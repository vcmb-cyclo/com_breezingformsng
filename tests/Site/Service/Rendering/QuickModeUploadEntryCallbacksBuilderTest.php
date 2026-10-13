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
use Vcmb\Component\BreezingformsNG\Site\Service\Rendering\QuickMode\QuickModeUploadEntryCallbacksBuilder;

final class QuickModeUploadEntryCallbacksBuilderTest extends TestCase
{
    public function testComposesQueueAndFilesAddedCallbacks(): void
    {
        $script = QuickModeUploadEntryCallbacksBuilder::build(
            12,
            '/cancel.png',
            false,
            false,
            'false',
            1024,
            'jpg,png',
            ' too large',
            ' invalid',
            "\n"
        );

        self::assertStringContainsString("uploader.bind('FilesAdded'", $script);
        self::assertStringContainsString('bfFlashFileQueue12', $script);
        self::assertSame(1, substr_count($script, "uploader.bind('FilesAdded'"));
    }

    public function testBootstrapOptionIsForwardedToBothCallbacks(): void
    {
        $script = QuickModeUploadEntryCallbacksBuilder::build(
            12,
            '/cancel.png',
            true,
            true,
            'true',
            1024,
            'jpg',
            ' too large',
            ' invalid'
        );

        self::assertStringContainsString("JQuery('#bfPickFiles12').prop('disabled',false)", $script);
        self::assertStringContainsString("? files[i].name.replace(/[/\\?%*:|\"<>]/g, '') : ''", $script);
    }

    public function testQueueCallbackCanPreserveRendererSpecificBlankLine(): void
    {
        $script = QuickModeUploadEntryCallbacksBuilder::build(
            12,
            '/cancel.png',
            false,
            true,
            'false',
            1024,
            'jpg,png',
            ' too large',
            ' invalid',
            "\n"
        );

        self::assertStringContainsString("function(up, files) {\n\n", $script);
    }

    public function testBuildsClassicFilesAddedHandlerBody(): void
    {
        $script = QuickModeUploadEntryCallbacksBuilder::build(
            12,
            '/images/cancel.png',
            false,
            false,
            'true',
            2048,
            'jpg,pdf',
            '"too large"',
            '"invalid extension"'
        );

        self::assertStringContainsString('for (var i in files)', $script);
        self::assertStringContainsString("id != 'undefined'", $script);
        self::assertStringContainsString('bfFileQueueItem', $script);
        self::assertStringContainsString('2048', $script);
        self::assertStringContainsString("'jpg,pdf'", $script);
        self::assertStringNotContainsString('class="row"', $script);
    }

    public function testBuildsBootstrapFilesAddedHandlerBody(): void
    {
        $script = QuickModeUploadEntryCallbacksBuilder::build(
            13,
            '/images/cancel.png',
            true,
            false,
            'false',
            4096,
            'png',
            '"too large"',
            '"invalid extension"'
        );

        self::assertStringContainsString("files[i].name.replace(/[/\\?%*:|\"<>]/g, '') ?", $script);
        self::assertStringContainsString('4096', $script);
        self::assertStringContainsString("'png'", $script);
    }
}
