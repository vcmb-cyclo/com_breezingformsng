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

namespace Vcmb\Component\BreezingformsNG\Tests\Compatibility;

use PHPUnit\Framework\TestCase;

final class ManifestMediaTest extends TestCase
{
    public function testDeclaresTheBreezingFormsThemeDirectoryForInstallation(): void
    {
        $manifest = simplexml_load_file(__DIR__ . '/../../breezingformsng.xml');

        self::assertInstanceOf(\SimpleXMLElement::class, $manifest);

        $folders = $manifest->xpath('/extension/media[@folder="media"]/folder');

        self::assertIsArray($folders);
        self::assertCount(2, $folders);
        self::assertSame('com_breezingformsng', (string) $folders[0]);
        self::assertSame('breezingforms/themes', (string) $folders[1]);
    }
}
