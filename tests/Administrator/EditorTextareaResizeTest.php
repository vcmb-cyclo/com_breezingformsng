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

final class EditorTextareaResizeTest extends TestCase
{
    public function testPieceEditorResizesTextareasWithoutTheLegacyGlobalFunction(): void
    {
        $source = file_get_contents(
            __DIR__ . '/../../administrator/components/com_breezingformsng/src/View/Pieces/Renderer.php'
        );

        self::assertIsString($source);
        self::assertStringNotContainsString('textAreaResize(', $source);
        self::assertStringContainsString("document.getElementById('unit_tests').rows", $source);
    }
}
