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
    public function testBothExportPathsExcludeTrashedFormsScriptsAndPieces(): void
    {
        $source = (string) file_get_contents(
            __DIR__ . '/../../administrator/components/com_breezingformsng/src/Model/PackageTransferModel.php'
        );

        foreach (['forms', 'scripts', 'pieces'] as $type) {
            self::assertStringContainsString("\$this->loadPackageRows('#__facileforms_$type', \$package, true)", $source);
            $ids = '$' . substr($type, 0, -1) . 'Ids';
            self::assertStringContainsString("\$this->loadRowsByIds('#__facileforms_$type', $ids, true)", $source);
        }

        self::assertStringContainsString("\$db->quoteName('published') . ' >= 0'", $source);
    }

    public function testPackageAuditIsCollapsedOnExportAndManagement(): void
    {
        $component = __DIR__ . '/../../administrator/components/com_breezingformsng';
        $view = (string) file_get_contents($component . '/src/View/Packages/HtmlView.php');

        self::assertStringContainsString('$this->profiles = $model->getPackageProfiles();', $view);
        self::assertStringContainsString('$this->timezone = $this->getCurrentUser()->getTimezone();', $view);

        foreach (['export', 'update'] as $layout) {
            $template = (string) file_get_contents($component . '/tmpl/packages/' . $layout . '.php');
            self::assertStringContainsString('class="bf-collapse-toggle collapsed d-block text-decoration-none text-reset"', $template);
            self::assertStringContainsString('id="bf-package-audit-content" class="collapse"', $template);
            self::assertStringContainsString('id="bf-package-info-content" class="collapse"', $template);
            self::assertStringContainsString('class="table-primary"', $template);
            self::assertStringContainsString('COM_BREEZINGFORMSNG_RECORDS_AUDITINFO', $template);
            foreach (['created', 'created_by', 'modified', 'modified_by'] as $field) {
                self::assertStringContainsString("\$profile['" . $field . "']", $template);
            }
        }
    }

    public function testTransferUsesTheVersionedBfngFormat(): void
    {
        $source = file_get_contents(__DIR__ . '/../../administrator/components/com_breezingformsng/src/Model/PackageTransferModel.php');

        self::assertIsString($source);
        self::assertStringContainsString("private const FORMAT = 'breezingformsng-package';", $source);
        self::assertStringContainsString('private const VERSION = 3;', $source);
        self::assertStringContainsString("[1, 2, self::VERSION]", $source);
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

    public function testPackageManagementStartsFromTheConfigurationScreen(): void
    {
        $component = __DIR__ . '/../../administrator/components/com_breezingformsng';
        $view = (string) file_get_contents($component . '/src/View/Packages/HtmlView.php');
        $controller = (string) file_get_contents($component . '/src/Controller/PackagesController.php');
        $layout = (string) file_get_contents($component . '/tmpl/packages/update.php');

        $tools = (string) file_get_contents($component . '/tmpl/packages/default.php');
        self::assertStringContainsString("'layout' => 'update', 'label' => 'COM_BREEZINGFORMSNG_TOOLS_TILE_MANAGE'", $tools);
        self::assertStringNotContainsString('confirmDelete', $view . $controller);
        // No read-only content table: the lists hold the items of the package, selected, and the free ones.
        self::assertStringNotContainsString('getPackageContents', $view);
        self::assertStringNotContainsString('bf-package-content', $layout . (string) file_get_contents($component . '/tmpl/packages/export.php'));
        self::assertStringContainsString('name="update_select[<?= $section; ?>][]"', $layout);
        self::assertStringContainsString('name="update_parts[]"', $layout);
        // A new package is created from this screen: its identifier, then the details and the items in one save.
        self::assertStringContainsString('PackageTransferModel::NEW_PACKAGE', $layout);
        self::assertStringContainsString('name="package_new" value="1"', $layout);
        // With no package selected, the free items are not taken for its members.
        self::assertStringContainsString("if (\$existing && (string) \$item['package'] === \$this->selectedPackage) {", $layout);
        self::assertStringContainsString('name="metadata[<?= $field; ?>]"', $layout);
        self::assertStringContainsString('$model->assertNewPackageId($package)', $controller);
        self::assertStringContainsString('$model->metadataDiffers($package, $metadata)', $controller);
        self::assertStringContainsString('task" value="packages.update"', $layout);
        self::assertStringContainsString('task" value="packages.remove"', $layout);
        self::assertStringNotContainsString('delete_select', $layout);
        self::assertStringNotContainsString('attach_select', $layout);
        self::assertStringContainsString("(string) \$item['package'] === ''", $layout);
        self::assertStringContainsString("'packages.update'", $view);
        self::assertStringContainsString("'packages.remove'", $view);
        self::assertStringNotContainsString("case 'delete':", $view);
        foreach (['delete', 'attach'] as $task) {
            self::assertStringNotContainsString('public function ' . $task . '(', $controller);
        }
        self::assertStringContainsString('public function update(', $controller);
        self::assertStringContainsString('layout=update', $controller);
        self::assertStringNotContainsString('layout=delete', $controller);
    }

    public function testPackageManagementExplainsThatContentsAreKeptInEveryLanguage(): void
    {
        $component = __DIR__ . '/../../administrator/components/com_breezingformsng';
        $layout = (string) file_get_contents($component . '/tmpl/packages/update.php');

        self::assertStringContainsString("Text::_('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_MANAGE_HINT')", $layout);

        foreach (['en-GB', 'fr-FR', 'de-DE', 'it-IT', 'es-ES', 'hu-HU', 'nl-NL', 'tr-TR'] as $language) {
            $ini = (string) file_get_contents($component . '/language/' . $language . '/com_breezingformsng.ini');

            self::assertMatchesRegularExpression('/^COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_MANAGE_HINT="[^"]+"\\s*$/m', $ini, $language);
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

    public function testTheScreenOpensForExportAndItsButtonExportsWithoutSaving(): void
    {
        $component = __DIR__ . '/../../administrator/components/com_breezingformsng';
        $view = (string) file_get_contents($component . '/src/View/Packages/HtmlView.php');

        $tools = (string) file_get_contents($component . '/tmpl/packages/default.php');
        $controller = (string) file_get_contents($component . '/src/Controller/PackagesController.php');
        self::assertStringContainsString("'layout' => 'export', 'label' => 'COM_BREEZINGFORMSNG_TOOLS_TILE_EXPORT'", $tools);
        self::assertStringContainsString("standardButton('download', 'COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_EXPORT_PACKAGE', 'packages.export')", $view);
        // The details are saved from the management screen only: when a package is created and when they changed.
        self::assertSame(2, substr_count($controller, '$model->savePackage($package,'));

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
        self::assertStringContainsString('FormsAdvancedOptionsHtml::packageNames($package)', $layout);
        // A form keeps a package missing from the list instead of losing it on save.
        self::assertStringContainsString('$packages[$current] = $current;', $helper);
        // The list shows the package name; the identifier stays the stored value.
        self::assertStringContainsString('$name . \' (\' . $id . \')\'', $helper);
        self::assertStringContainsString('as $packageId => $packageLabel', $layout);

        foreach (['en-GB', 'fr-FR', 'de-DE', 'it-IT', 'es-ES', 'hu-HU', 'nl-NL', 'tr-TR'] as $language) {
            $ini = (string) file_get_contents($component . '/language/' . $language . '/com_breezingformsng.ini');

            self::assertMatchesRegularExpression('/^COM_BREEZINGFORMSNG_FORMS_PACKAGE_HINT="[^"]+"\\s*$/m', $ini, $language);
        }
    }

    public function testARefusedSaveShowsTheScreenAgainWithTheUserInput(): void
    {
        $component = __DIR__ . '/../../administrator/components/com_breezingformsng';
        $controller = (string) file_get_contents($component . '/src/Controller/PackagesController.php');
        $view = (string) file_get_contents($component . '/src/View/Packages/HtmlView.php');
        $template = (string) file_get_contents($component . '/tmpl/packages/update.php');

        $this->assertStringContainsString('setUserState(self::UPDATE_DRAFT_STATE', $controller);
        $this->assertStringContainsString('getUserState(PackagesController::UPDATE_DRAFT_STATE', $view);
        $this->assertStringContainsString("\$this->draft['select'][\$section]", $template);
        $this->assertStringContainsString("\$this->draft['package']", $template);
        $this->assertStringContainsString("\$this->draft['metadata'][\$field]", $template);
    }

    public function testTheExportScreenOnlyDownloadsAFile(): void
    {
        $component = __DIR__ . '/../../administrator/components/com_breezingformsng';
        $view = (string) file_get_contents($component . '/src/View/Packages/HtmlView.php');
        $controller = (string) file_get_contents($component . '/src/Controller/PackagesController.php');
        $layout = (string) file_get_contents($component . '/tmpl/packages/export.php');

        // Packages are created and edited from the management screen; exporting creates nothing on the site.
        self::assertStringNotContainsString('packages.save', $view . $layout);
        self::assertStringNotContainsString('public function save(', $controller);
        self::assertStringContainsString("'packages.export'", $view);
        self::assertStringContainsString("'export' => Text::_('COM_BREEZINGFORMSNG_TOOLS_TILE_EXPORT')", $view);
        self::assertStringContainsString('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_NEW_PACKAGE_EXPORT', $layout);
        $export = substr($controller, (int) strpos($controller, 'public function export('), 2500);
        self::assertStringNotContainsString('savePackage', $export);
    }

    public function testAnExportCarriesTheActiveScriptsAndPiecesItsFormsCall(): void
    {
        $model = (string) file_get_contents(__DIR__ . '/../../administrator/components/com_breezingformsng/src/Model/PackageTransferModel.php');

        // Both exports go through buildPayload(), which adds the libraries the forms and elements call.
        self::assertSame(2, substr_count($model, 'return $this->buildPayload('));
        self::assertStringContainsString('[$scripts, $pieces] = $this->withReferencedLibraries($forms, $elements, $scripts, $pieces);', $model);
        // Only active references, from non-trashed items, and before the names are checked.
        self::assertStringContainsString("(int) (\$row[\$kind . \$number . 'cond'] ?? 0) === 1", $model);
        self::assertStringContainsString("loadRowsByIds('#__facileforms_scripts', \$missingScripts, true)", $model);
        self::assertLessThan(
            strpos($model, "\$this->assertImportableNames('#__facileforms_scripts', \$scripts, true);"),
            strpos($model, '$this->withReferencedLibraries($forms')
        );
    }

    public function testAPackageRefusesItemsItCouldNotImportByName(): void
    {
        $component = __DIR__ . '/../../administrator/components/com_breezingformsng';
        $model = (string) file_get_contents($component . '/src/Model/PackageTransferModel.php');

        // The export of a whole package and of a selection, and the import of
        // forms and of script or piece libraries, all check the names.
        // Both exports go through buildPayload(), which checks the three kinds.
        self::assertSame(3, substr_count($model, "\$this->assertImportableNames('#__facileforms_") - 1);
        self::assertSame(2, substr_count($model, 'return $this->buildPayload('));
        self::assertSame(2, substr_count($model, '$this->assertImportableNames(\'#__facileforms_forms\', $this->fileNames(') + substr_count($model, '$this->assertImportableNames($table, $this->fileNames('));
        // Names the database holds as equal must not overwrite an item written by the same import.
        self::assertSame(2, substr_count($model, '$this->assertNotWrittenTwice('));
        self::assertStringContainsString('$written[$id] = true;', $model);

        // A form replaced by an import: versions noted, Integrator rules following its new elements.
        self::assertStringContainsString('$versioning->keepOrigin($id, $this->originNote);', $model);
        self::assertStringContainsString('$versioning->record($id, $this->importedNote);', $model);
        self::assertStringContainsString('$this->relinkIntegratorRules($id, $replacedElements);', $model);

        foreach (['en-GB', 'fr-FR', 'de-DE', 'it-IT', 'es-ES', 'hu-HU', 'nl-NL', 'tr-TR'] as $language) {
            $ini = (string) file_get_contents($component . '/language/' . $language . '/com_breezingformsng.ini');

            foreach (['ORIGIN', 'COPY', 'RESTORED_DATE', 'IMPORTED', 'BEFORE_IMPORT'] as $note) {
                self::assertMatchesRegularExpression('/^COM_BREEZINGFORMSNG_FORM_VERSION_NOTE_' . $note . '="[^"]+"\s*$/m', $ini, $language);
            }
            self::assertMatchesRegularExpression('/^COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_INTEGRATOR_UNLINKED="[^"]*%d[^"]*"\s*$/m', $ini, $language);

            foreach (['UNNAMED', 'DUPLICATE'] as $problem) {
                foreach (['FORMS', 'SCRIPTS', 'PIECES'] as $kind) {
                    self::assertMatchesRegularExpression('/^COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_' . $problem . '_' . $kind . '="[^"]*%s[^"]*"\s*$/m', $ini, $language);
                }
            }
        }
    }

    public function testAPackageWithoutAConnectorGetsTheDefaultOneOnImport(): void
    {
        $model = (string) file_get_contents(
            __DIR__ . '/../../administrator/components/com_breezingformsng/src/Model/PackageTransferModel.php'
        );

        // The column defaults to AND: a missing or empty connector is completed, a listed one kept,
        // and any other value still refuses the package.
        self::assertStringContainsString("\$connector = \$childData['andor'] ?? '';", $model);
        self::assertStringContainsString("\$connector = \$connector === '' ? 'AND' : \$connector;", $model);
        self::assertStringContainsString("\$childData['andor'] = \$connector;", $model);
        self::assertStringContainsString('IntegratorCriteriaPolicy::allowsConnector($connector)', $model);
    }
}
