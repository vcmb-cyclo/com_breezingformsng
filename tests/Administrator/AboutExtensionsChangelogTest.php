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

final class AboutExtensionsChangelogTest extends TestCase
{
    public function testTheVersionOpensTheInstallerChangelogDialogOnlyForThoseWhoMayManageIt(): void
    {
        $base = __DIR__ . '/../../administrator/components/com_breezingformsng';
        $template = file_get_contents($base . '/tmpl/about/extensions.php');
        $view = file_get_contents($base . '/src/View/About/HtmlView.php');

        self::assertIsString($template);
        self::assertIsString($view);
        // Same dialog and endpoint as the Installer's Manage screen, behind the right to manage the Installer.
        self::assertStringContainsString('data-joomla-dialog', $template);
        self::assertStringContainsString('task=manage.loadChangelogRaw', $template);
        self::assertStringContainsString('$this->canViewChangelog', $template);
        self::assertStringContainsString("authorise('core.manage', 'com_installer')", $view);
        self::assertStringContainsString("useScript('joomla.dialog-autocreate')", $view);
        // No separate changelog column or external link any more: the version is the entry point.
        self::assertStringNotContainsString('ABOUT_EXTENSION_CHANGELOG', $template);
        self::assertStringNotContainsString('target="_blank"', $template);
    }

    public function testTheNameColumnIsWideAndTheDescriptionIsCutWithATooltip(): void
    {
        $template = file_get_contents(
            __DIR__ . '/../../administrator/components/com_breezingformsng/tmpl/about/extensions.php'
        );
        $css = file_get_contents(__DIR__ . '/../../media/com_breezingformsng/css/about.css');

        self::assertIsString($template);
        self::assertIsString($css);
        self::assertStringContainsString('class="bf-extension-name"', $template);
        self::assertStringContainsString('class="bf-extension-description" title="', $template);
        self::assertStringContainsString('-webkit-line-clamp: 2;', $css);
        self::assertStringContainsString('#bf-extensions-table th.bf-extension-name', $css);
    }
}
