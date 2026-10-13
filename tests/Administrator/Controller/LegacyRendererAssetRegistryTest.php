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

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator\Controller;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class LegacyRendererAssetRegistryTest extends TestCase
{
    #[DataProvider('controllerProvider')]
    public function testLegacyRendererControllersRegisterTheComponentAssetRegistry(string $controller): void
    {
        $source = file_get_contents(
            __DIR__ . '/../../../administrator/components/com_breezingformsng/src/Controller/' . $controller
        );

        self::assertIsString($source);
        self::assertStringContainsString(
            "getRegistry()->addExtensionRegistryFile('com_breezingformsng')",
            $source
        );
    }

    /**
     * @return array<string, array{string}>
     */
    public static function controllerProvider(): array
    {
        return [
            'pieces' => ['PiecesController.php'],
        ];
    }
}
