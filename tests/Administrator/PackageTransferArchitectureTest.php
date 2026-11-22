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
            self::assertStringContainsString("LayoutHelper::render('packages.audit'", $template);
            self::assertStringContainsString("LayoutHelper::render('packages.item-lists'", $template);
        }

        // Both screens share the same audit and info accordions.
        $template = (string) file_get_contents($component . '/layouts/packages/audit.php')
            . (string) file_get_contents($component . '/layouts/packages/metadata.php');
        self::assertStringContainsString('class="bf-collapse-toggle collapsed d-block text-decoration-none text-reset"', $template);
        self::assertStringContainsString('id="bf-package-audit-content" class="collapse"', $template);
        self::assertStringContainsString('id="bf-package-info-content" class="collapse"', $template);
        self::assertStringContainsString('class="table-primary"', $template);
        self::assertStringContainsString('COM_BREEZINGFORMSNG_RECORDS_AUDITINFO', $template);
        foreach (['created', 'created_by', 'modified', 'modified_by'] as $field) {
            self::assertStringContainsString("'" . $field . "'", $template);
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
        $page = (string) file_get_contents($component . '/tmpl/packages/update.php');
        $layout = $page . (string) file_get_contents($component . '/layouts/packages/item-lists.php')
            . (string) file_get_contents($component . '/layouts/packages/metadata.php');

        $tools = (string) file_get_contents($component . '/tmpl/packages/default.php');
        self::assertStringContainsString("'layout' => 'update', 'label' => 'COM_BREEZINGFORMSNG_TOOLS_TILE_MANAGE'", $tools);
        self::assertStringNotContainsString('confirmDelete', $view . $controller);
        // No read-only content table: the lists hold the items of the package, selected, and the free ones.
        self::assertStringNotContainsString('getPackageContents', $view);
        self::assertStringNotContainsString('bf-package-content', $layout . (string) file_get_contents($component . '/tmpl/packages/export.php'));
        self::assertStringContainsString("'fieldName' => 'update_select[%s][]'", $page);
        self::assertStringContainsString('name="update_parts[]"', $layout);
        // A new package is created from this screen: its identifier, then the details and the items in one save.
        self::assertStringContainsString('PackageTransferModel::NEW_PACKAGE', $layout);
        self::assertStringContainsString('name="package_new" value="1"', $layout);
        // With no package selected, the free items are not taken for its members.
        self::assertStringContainsString("if (\$selectedPackage !== '' && \$package === \$selectedPackage) {", (string) file_get_contents($component . '/src/Service/PackageListRows.php'));
        self::assertStringContainsString('name="metadata[<?= $field; ?>]"', $layout);
        self::assertStringContainsString('$model->assertNewPackageId($package)', $controller);
        self::assertStringContainsString('$model->metadataDiffers($package, $metadata)', $controller);
        self::assertStringContainsString('task" value="packages.update"', $layout);
        self::assertStringContainsString('task" value="packages.remove"', $layout);
        // One checkbox per item, in a fieldset per kind: a click changes that row only, All and None act on the list.
        self::assertStringContainsString('<fieldset class="card h-100', $layout);
        self::assertStringContainsString('type="checkbox"', $layout);
        self::assertStringNotContainsString(' multiple', $layout);
        self::assertStringContainsString('data-bf-all', $layout);
        self::assertStringContainsString('data-bf-none', $layout);
        self::assertStringContainsString('aria-live="polite"', $layout);
        self::assertStringContainsString('data-bf-saved=', $layout);
        self::assertStringContainsString('count($rows) > 15', $layout);
        self::assertStringContainsString("useScript('com_breezingformsng.package-update')", $view);
        self::assertFileExists(__DIR__ . '/../../media/com_breezingformsng/js/admin/package-update.js');
        self::assertStringNotContainsString('delete_select', $layout);
        self::assertStringNotContainsString('attach_select', $layout);
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
        // The details are saved from the management screen only: when a package is created and when they changed, in one place.
        self::assertSame(1, substr_count($controller, '$model->savePackage($package,'));

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
        $this->assertStringContainsString("\$this->draft['select']", $view);
        $this->assertStringContainsString("\$this->draft['package']", $template);
        $this->assertStringContainsString("\$this->draft['metadata']", $template);
    }

    public function testARefusedExportShowsTheScreenAgainWithTheUserInput(): void
    {
        $component = __DIR__ . '/../../administrator/components/com_breezingformsng';
        $controller = (string) file_get_contents($component . '/src/Controller/PackagesController.php');
        $view = (string) file_get_contents($component . '/src/View/Packages/HtmlView.php');
        $template = (string) file_get_contents($component . '/tmpl/packages/export.php');

        self::assertStringContainsString('setUserState(self::EXPORT_DRAFT_STATE', $controller);
        self::assertStringContainsString('getUserState(PackagesController::EXPORT_DRAFT_STATE', $view);
        self::assertStringContainsString("\$this->draft['select']", $view);
        self::assertStringContainsString("\$this->draft['package']", $template);
        self::assertStringContainsString("\$this->draft['metadata']", $template);
        self::assertStringContainsString('bfLoadProfile(bfHasExportDraft)', $template);
        self::assertStringContainsString('PackageTransferModel::NEW_PACKAGE', $template);

        foreach (['en-GB', 'fr-FR', 'de-DE', 'it-IT', 'es-ES', 'hu-HU', 'nl-NL', 'tr-TR'] as $tag) {
            $ini = (string) file_get_contents($component . '/language/' . $tag . '/com_breezingformsng.ini');
            self::assertStringContainsString('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_NEW_PACKAGE_ID_REQUIRED="', $ini, $tag);
        }
    }

    public function testTheFormListsShowHowManyIntegratorRulesEachFormHas(): void
    {
        $component = __DIR__ . '/../../administrator/components/com_breezingformsng';
        $model = (string) file_get_contents($component . '/src/Model/PackageTransferModel.php');

        self::assertStringContainsString('$this->withIntegratorRuleCounts($this->loadChoices("#__facileforms_forms", true))', $model);
        self::assertStringContainsString("'COUNT(*) AS '", $model);
        self::assertStringContainsString("\$db->quoteName('published') . ' > -2'", $model);

        // The shared list layout shows the count on both screens.
        self::assertStringContainsString('PACKAGE_TRANSFER_INTEGRATOR_RULES_COUNT', (string) file_get_contents($component . '/layouts/packages/item-lists.php'));

        foreach (['en-GB', 'fr-FR', 'de-DE', 'it-IT', 'es-ES', 'hu-HU', 'nl-NL', 'tr-TR'] as $tag) {
            $ini = (string) file_get_contents($component . '/language/' . $tag . '/com_breezingformsng.ini');
            self::assertStringContainsString('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_INTEGRATOR_RULES_COUNT="', $ini, $tag);
        }
    }

    public function testTheExportScreenListsItsItemsAsCheckboxesWithLinks(): void
    {
        $component = __DIR__ . '/../../administrator/components/com_breezingformsng';
        $export = (string) file_get_contents($component . '/tmpl/packages/export.php');
        $update = (string) file_get_contents($component . '/tmpl/packages/update.php');
        $view = (string) file_get_contents($component . '/src/View/Packages/HtmlView.php');

        $lists = (string) file_get_contents($component . '/layouts/packages/item-lists.php');
        $rows = (string) file_get_contents($component . '/src/Service/PackageListRows.php');

        // Both screens render the same shared list layout, with their own field names.
        self::assertStringContainsString("'fieldName' => '%s[]'", $export);
        self::assertStringContainsString("LayoutHelper::render('packages.item-lists'", $update);
        self::assertStringContainsString('data-bf-lists', $lists);
        self::assertStringNotContainsString(' multiple', $lists);
        self::assertStringNotContainsString('data-select-all', $export . $lists);

        // The object name itself opens its editor.
        self::assertStringContainsString('data-bf-title href=', $lists);
        self::assertStringContainsString('bf-package-object-link', $lists);
        self::assertStringContainsString('target="_blank" rel="noopener"', $lists);
        self::assertStringNotContainsString('icon-external-link', $lists);
        self::assertStringContainsString('task=quickmode.display&form=', $rows);
        self::assertStringContainsString('task=script.edit&id=', $rows);
        self::assertStringContainsString('task=piece.edit&id=', $rows);
        self::assertStringContainsString('task=menu.edit&id=', $rows);
        // An existing package with items lists only its own: they can be unticked, the others are out of reach.
        self::assertStringContainsString("const outside = assigned && box.dataset.bfPackage !== id;", $export);
        self::assertStringContainsString('box.disabled = outside;', $export);

        self::assertStringContainsString("useScript('com_breezingformsng.package-update')", $view);
    }

    public function testRemovingAPackageAsksWithTheNumberOfItsAttachedItems(): void
    {
        $component = __DIR__ . '/../../administrator/components/com_breezingformsng';
        $view = (string) file_get_contents($component . '/src/View/Packages/HtmlView.php');

        self::assertStringContainsString("Text::plural('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_REMOVE_CONFIRM', \$this->attachedCount)", $view);
        self::assertStringContainsString('$model->countPackageItems($this->selectedPackage)', $view);

        foreach (['en-GB', 'fr-FR', 'de-DE', 'it-IT', 'es-ES', 'hu-HU', 'nl-NL', 'tr-TR'] as $tag) {
            $ini = (string) file_get_contents($component . '/language/' . $tag . '/com_breezingformsng.ini');
            foreach (['', '_1', '_0'] as $suffix) {
                self::assertStringContainsString('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_REMOVE_CONFIRM' . $suffix . '="', $ini, $tag . $suffix);
            }
            self::assertMatchesRegularExpression('/PACKAGE_TRANSFER_REMOVE_CONFIRM="[^"]*%d/', $ini, $tag);
        }
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
        self::assertStringContainsString("if (\$package === '')", $export);
        self::assertStringContainsString('view=packages&layout=export&package=', $export);
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
