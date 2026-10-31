<?php

/**
 * BreezingForms NG - A Joomla Forms Application
 *
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 *
 * SPDX-License-Identifier: GPL-2.0-or-later
 **/

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator;

use PHPUnit\Framework\TestCase;

final class QuickmodeOptionsTabVisibilityTest extends TestCase
{
    public function testOnlyTheFormRootShowsTheOptionsTab(): void
    {
        $script = file_get_contents(__DIR__ . '/../../media/com_breezingformsng/js/admin/quickmode-app.js');

        self::assertIsString($script);
        self::assertStringContainsString(
            "appScope.selectedTreeElement = node;\n"
            . "                        appScope.toggleOptionsTab(appScope.getNodeClass(node) === 'bfQuickModeRootClass');",
            $script
        );
        self::assertStringContainsString("tab.parentElement.classList.toggle('d-none', !visible);", $script);
    }

    public function testTheTextEditorReadsTheSelectedNodeOfTheApp(): void
    {
        $script = (string) file_get_contents(__DIR__ . '/../../media/com_breezingformsng/js/admin/quickmode-editor.js');
        $layout = (string) file_get_contents(__DIR__ . '/../../administrator/components/com_breezingformsng/tmpl/quickmode/editor.php');

        self::assertStringNotContainsString('parent.app', $script);
        self::assertStringContainsString('parent.BFQMApp.selectedTreeElement', $script);
        self::assertStringContainsString("JoomlaEditor.get('bfEditor')", $script);
        self::assertStringContainsString("false, 'bfEditor')", $layout);
        // Without media/, Joomla finds no file and writes no script tag.
        self::assertStringContainsString(
            '"uri": "media/com_breezingformsng/js/admin/quickmode-editor.js"',
            (string) file_get_contents(__DIR__ . '/../../media/com_breezingformsng/joomla.asset.json')
        );
    }

    public function testNoModuleIsImportedWithoutItsVersion(): void
    {
        $directory = __DIR__ . '/../../media/com_breezingformsng/js';
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS));

        foreach ($files as $file) {
            if ($file->getExtension() === 'js') {
                self::assertDoesNotMatchRegularExpression(
                    '/^\s*import\b[^;]*from\s+[\'"]\.{1,2}\//m',
                    (string) file_get_contents($file->getPathname()),
                    $file->getPathname()
                );
            }
        }
    }
}
