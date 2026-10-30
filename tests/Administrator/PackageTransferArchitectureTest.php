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

final class PackageTransferArchitectureTest extends TestCase
{
    public function testTransferUsesTheVersionedBfngFormat(): void
    {
        $source = file_get_contents(__DIR__ . '/../../administrator/components/com_breezingformsng/src/Model/PackageTransferModel.php');

        self::assertIsString($source);
        self::assertStringContainsString("private const FORMAT = 'breezingformsng-package';", $source);
        self::assertStringContainsString('private const VERSION = 2;', $source);
        self::assertStringContainsString("[1, self::VERSION]", $source);
        self::assertStringContainsString("'scripts' =>", $source);
        self::assertStringContainsString("'pieces' =>", $source);
        self::assertStringContainsString("'forms' =>", $source);
        self::assertStringContainsString("'menus' =>", $source);
        self::assertStringContainsString("'metadata' =>", $source);
        self::assertStringContainsString('includeMenuAncestors(', $source);
        self::assertStringContainsString('transactionStart()', $source);
        self::assertStringContainsString('transactionRollback()', $source);
    }

    public function testPackageTransferIsNotExposedInTheJoomlaConfigurationForm(): void
    {
        $config = file_get_contents(__DIR__ . '/../../administrator/components/com_breezingformsng/config.xml');

        self::assertIsString($config);
        self::assertStringNotContainsString('name="package_transfer"', $config);
        self::assertStringNotContainsString('COM_BREEZINGFORMSNG_IMPORT_EXPORT', $config);
    }

    public function testDeletingAPackageStartsFromTheConfigurationScreen(): void
    {
        $component = __DIR__ . '/../../administrator/components/com_breezingformsng';
        $view = (string) file_get_contents($component . '/src/View/Packages/HtmlView.php');
        $controller = (string) file_get_contents($component . '/src/Controller/PackagesController.php');
        $layout = (string) file_get_contents($component . '/tmpl/packages/delete.php');

        self::assertStringContainsString("linkButton('delete-package', 'COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_DELETE')", $view);
        self::assertStringContainsString('view=packages&layout=delete', $view);
        self::assertStringNotContainsString('confirmDelete', $view . $controller);
        self::assertStringContainsString('$model->getPackageContents($this->selectedPackage)', $view);

        foreach (['forms', 'scripts', 'pieces', 'menus'] as $section) {
            self::assertStringContainsString("'" . $section . "' => 'COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_CONTENT_", $layout);
        }
    }

    public function testDissolvingWarnsAboutDuplicatesInEveryLanguage(): void
    {
        $component = __DIR__ . '/../../administrator/components/com_breezingformsng';
        $layout = (string) file_get_contents($component . '/tmpl/packages/delete.php');

        self::assertStringContainsString("Text::_('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_DELETE_REIMPORT')", $layout);

        foreach (['en-GB', 'fr-FR', 'de-DE', 'it-IT', 'es-ES', 'hu-HU', 'nl-NL', 'tr-TR'] as $language) {
            $ini = (string) file_get_contents($component . '/language/' . $language . '/com_breezingformsng.ini');

            self::assertMatchesRegularExpression('/^COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_DELETE_REIMPORT="[^"]+"\\s*$/m', $ini, $language);
        }
    }

    public function testFrenchSaysPaquetNotPackage(): void
    {
        $ini = (string) file_get_contents(__DIR__ . '/../../administrator/components/com_breezingformsng/language/fr-FR/com_breezingformsng.ini');
        // The /packages directory and the XML root element are not words of the interface.
        $values = preg_replace('~<code>/packages</code>|FacileFormsPackage|^[A-Z0-9_]+=|^;.*$~m', '', $ini);

        self::assertIsString($values);
        self::assertDoesNotMatchRegularExpression('/package/i', $values);
    }

    public function testTheScreenOpensAsCreateOrEditAndItsButtonExports(): void
    {
        $component = __DIR__ . '/../../administrator/components/com_breezingformsng';
        $view = (string) file_get_contents($component . '/src/View/Packages/HtmlView.php');

        self::assertStringContainsString("linkButton('createpkg', 'COM_BREEZINGFORMSNG_INSTALLER_CREATEPKG')", $view);
        self::assertStringContainsString("standardButton('download', 'COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_EXPORT_PACKAGE', 'packages.export')", $view);

        foreach (['en-GB', 'fr-FR', 'de-DE', 'it-IT', 'es-ES', 'hu-HU', 'nl-NL', 'tr-TR'] as $language) {
            $ini = (string) file_get_contents($component . '/language/' . $language . '/com_breezingformsng.ini');

            self::assertMatchesRegularExpression('/^COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_EXPORT_PACKAGE="[^"]+"\\s*$/m', $ini, $language);
        }
    }

    public function testTheFormChoosesItsPackageFromAList(): void
    {
        $component = __DIR__ . '/../../administrator/components/com_breezingformsng';
        $layout = (string) file_get_contents($component . '/layouts/forms/advanced_options.php');
        $helper = (string) file_get_contents($component . '/src/Helper/FormsAdvancedOptionsHtml.php');

        self::assertStringContainsString('<select class="form-select" id="jf_package" name="package">', $layout);
        self::assertStringNotContainsString('id="jf_package" name="package" value=', $layout);
        self::assertStringContainsString('FormsAdvancedOptionsHtml::packageNames($pkg)', $layout);
        // A form keeps a package missing from the list instead of losing it on save.
        self::assertStringContainsString('$packages[] = $current;', $helper);

        foreach (['en-GB', 'fr-FR', 'de-DE', 'it-IT', 'es-ES', 'hu-HU', 'nl-NL', 'tr-TR'] as $language) {
            $ini = (string) file_get_contents($component . '/language/' . $language . '/com_breezingformsng.ini');

            self::assertMatchesRegularExpression('/^COM_BREEZINGFORMSNG_FORMS_PACKAGE_HINT="[^"]+"\\s*$/m', $ini, $language);
        }
    }
}
