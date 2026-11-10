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
use Vcmb\Component\BreezingformsNG\Site\Service\Rendering\QuickMode\QuickModeUploadValidationScriptBuilder;

final class QuickModeUploadValidationScriptBuilderTest extends TestCase
{
    public function testBuildsSizeAndExtensionValidation(): void
    {
        $script = QuickModeUploadValidationScriptBuilder::build(2048, 'jpg,png', 'Too large', 'Bad type', "\n");

        self::assertStringContainsString('var thebytes = 2048;', $script);
        self::assertStringContainsString("var exts = 'jpg,png'.split(',');", $script);
        self::assertStringContainsString('alert("Too large".replace(', $script);
        self::assertStringContainsString('alert("Bad type".replace(', $script);
        self::assertStringContainsString("JQuery('#'+files[i].id+'queueitem').remove();", $script);
        self::assertStringContainsString('bfFlashUploadersLength++;', $script);
    }

    public function testEscapesValidationValuesForJavaScript(): void
    {
        $script = QuickModeUploadValidationScriptBuilder::build(
            2048,
            "jpg,po'wn</script>",
            "Too large');alert(1);//",
            "Bad type');alert(2);//",
            "\n"
        );

        self::assertStringContainsString('alert("Too large\\u0027);alert(1);\\/\\/".replace(', $script);
        self::assertStringContainsString('alert("Bad type\\u0027);alert(2);\\/\\/".replace(', $script);
        self::assertStringContainsString("var exts = 'jpg,po\\u0027wn\\u003C/script\\u003E'.split(',');", $script);
    }
}
