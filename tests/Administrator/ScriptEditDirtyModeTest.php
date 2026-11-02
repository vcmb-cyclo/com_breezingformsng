<?php

/**
 * BreezingForms NG - A Joomla Forms Application
 *
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 *
 * SPDX-License-Identifier: GPL-2.0-or-later
 */

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator;

use PHPUnit\Framework\TestCase;

final class ScriptEditDirtyModeTest extends TestCase
{
    public function testScriptEditorExposesAndProtectsUnsavedChanges(): void
    {
        $script = file_get_contents(__DIR__ . '/../../media/com_breezingformsng/js/admin/scripts-edit.js');
        $template = file_get_contents(__DIR__ . '/../../administrator/components/com_breezingformsng/tmpl/script/edit.php');

        self::assertIsString($script);
        self::assertIsString($template);
        self::assertStringContainsString("window.addEventListener('beforeunload'", $script);
        self::assertStringContainsString('__bfEditSubmitting', $script);
        self::assertStringContainsString('syncEditDirtyBadge()', $script);
        self::assertStringContainsString('id="bf-edit-script-dirty-badge"', $template);
        self::assertStringContainsString("COM_BREEZINGFORMSNG_QM_UNSAVED_CHANGES", $template);
    }
}
