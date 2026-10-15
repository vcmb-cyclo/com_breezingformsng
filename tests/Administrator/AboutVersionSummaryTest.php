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

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator;

use PHPUnit\Framework\TestCase;

final class AboutVersionSummaryTest extends TestCase
{
    public function testVersionSummaryDisplaysBuildAndPlatformMetadata(): void
    {
        $template = file_get_contents(__DIR__ . '/../../administrator/components/com_breezingformsng/tmpl/about/default.php');
        $manifest = file_get_contents(__DIR__ . '/../../breezingformsng.xml');
        $buildScript = file_get_contents(__DIR__ . '/../../scripts/build-package.sh');

        self::assertIsString($template);
        self::assertIsString($manifest);
        self::assertIsString($buildScript);
        self::assertStringContainsString("'buildType' => ''", $template);
        self::assertStringContainsString('COM_BREEZINGFORMSNG_ABOUT_PLATFORM_JOOMLA_6', $template);
        self::assertStringContainsString('COM_BREEZINGFORMSNG_ABOUT_PLATFORM_PHP_83', $template);
        self::assertStringContainsString('<buildType>development</buildType>', $manifest);
        self::assertStringContainsString('<buildType>production</buildType>', $buildScript);
    }
}
