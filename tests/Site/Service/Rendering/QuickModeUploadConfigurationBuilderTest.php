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
use Vcmb\Component\BreezingformsNG\Site\Service\Rendering\QuickMode\QuickModeUploadConfigurationBuilder;

final class QuickModeUploadConfigurationBuilderTest extends TestCase
{
    public function testBuildContainsStablePluploadConfiguration(): void
    {
        $script = (new QuickModeUploadConfigurationBuilder())->build(
            12,
            'upload',
            'ticket',
            '/site/',
            44,
            'jpg,png',
            'true',
            '"Choose file"'
        );

        self::assertStringContainsString('max_retries: 10', $script);
        self::assertStringContainsString("runtimes : 'html5',", $script);
        self::assertStringNotContainsString('swf', $script);
        self::assertStringContainsString("multipart_params: { form: 12, itemName : 'upload'", $script);
        self::assertStringContainsString("url : '/site/index.php'", $script);
        self::assertStringContainsString('{title : "Choose file", extensions : \'jpg,png\'}', $script);
    }

    public function testEscapesValuesInsertedIntoJavaScriptStrings(): void
    {
        $script = (new QuickModeUploadConfigurationBuilder())->build(
            12,
            "field'Name",
            "ticket'Value",
            "/site/'",
            44,
            "jpg');alert(1);//",
            'true',
            '"Choose file"'
        );

        self::assertStringContainsString("itemName : 'field\\'Name'", $script);
        self::assertStringContainsString("bfFlashUploadTicket: 'ticket\\'Value'", $script);
        self::assertStringContainsString("extensions : 'jpg\\');alert(1);//'", $script);
        self::assertStringNotContainsString("extensions : 'jpg');alert", $script);
    }
}
