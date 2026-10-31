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

final class AdminRunModeRemovalTest extends TestCase
{
    private const ADMIN = __DIR__ . '/../../administrator/components/com_breezingformsng/';

    public function testNothingLinksToTheRemovedRoute(): void
    {
        self::assertStringNotContainsString('act=run', $this->read('src/Model/MenuModel.php'));
        self::assertStringNotContainsString('function sync', $this->read('src/Controller/MenusController.php'));
        self::assertStringNotContainsString('menus.sync', $this->read('src/View/Menus/HtmlView.php'));
        self::assertStringNotContainsString('function run', $this->read('src/Controller/FormsController.php'));
    }

    public function testFormOptionsNoLongerOfferOrAcceptTheRunMode(): void
    {
        self::assertStringNotContainsString('name="runmode"', $this->read('layouts/forms/advanced_options.php'));
        self::assertStringNotContainsString("'runmode'", $this->read('src/Controller/FormsController.php'));
    }

    public function testModuleAndPluginApplyTheRuleOfTheSite(): void
    {
        $paths = [
            'modules/mod_breezingforms/src/Dispatcher/Dispatcher.php',
            'plugins/breezingforms/src/Extension/Breezingforms.php',
        ];

        foreach ($paths as $path) {
            $source = $this->read($path);

            self::assertStringContainsString("quoteName('runmode') . ' <> 1')", $source);
            self::assertStringNotContainsString('ff_runmode', $source);
        }
    }

    public function testPackageImportKeepsBackendOnlyFormsOffTheSite(): void
    {
        $source = $this->read('src/Model/PackageTransferModel.php');

        self::assertMatchesRegularExpression(
            "/\\['runmode'\\] \\?\\? 0\\) === 1\\) \\{\\s+"
            . "\\\$data\\['published'\\] = 0;\\s+\\\$data\\['runmode'\\] = 0;/",
            $source
        );
    }

    public function testRestoringAVersionLeavesTheRunModeAlone(): void
    {
        self::assertMatchesRegularExpression(
            "/KEPT_COLUMNS = \\[[^\\]]*'runmode'/",
            $this->read('src/Service/FormVersioning.php')
        );
    }

    public function testUpdateRemovesRunMenuEntriesAndRetiresBackendOnlyForms(): void
    {
        $source = file_get_contents(__DIR__ . '/../../script.php');

        self::assertIsString($source);
        self::assertStringContainsString('$this->removeAdminRunMenuEntries();', $source);
        self::assertStringContainsString('$this->retireBackendOnlyForms();', $source);
        self::assertStringContainsString("'&act=run%'", $source);
        self::assertStringContainsString("quoteName('client_id') . ' = 1'", $source);
    }

    public function testStoredScriptsKeepThePublicRunModeSymbols(): void
    {
        $source = file_get_contents(
            __DIR__ . '/../../components/com_breezingformsng/src/Support/runtime_bootstrap.php'
        );

        self::assertIsString($source);

        foreach (['_FF_RUNMODE_FRONTEND', '_FF_RUNMODE_BACKEND', '_FF_RUNMODE_PREVIEW'] as $constant) {
            self::assertStringContainsString("DEFINE('" . $constant . "'", $source);
        }
    }

    private function read(string $path): string
    {
        $source = file_get_contents(self::ADMIN . $path);

        self::assertIsString($source);

        return $source;
    }
}
