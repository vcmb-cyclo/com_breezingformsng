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

final class AboutAuditPresentationTest extends TestCase
{
    public function testTheTableInventoryMovedFromTheAuditToTheStatisticsScreen(): void
    {
        $base = __DIR__ . '/../../administrator/components/com_breezingformsng';
        $audit = (string) file_get_contents($base . '/tmpl/about/audit.php');
        $statistics = (string) file_get_contents($base . '/tmpl/about/statistics.php');

        self::assertStringNotContainsString('COM_BREEZINGFORMSNG_ABOUT_AUDIT_TABLE_INVENTORY', $audit);
        self::assertStringContainsString('COM_BREEZINGFORMSNG_ABOUT_AUDIT_TABLE_INVENTORY', $statistics);
        self::assertStringContainsString('data-bf-sortable-table', $statistics);
        self::assertStringContainsString("aboutUrl('statistics')", (string) file_get_contents($base . '/tmpl/packages/default.php'));
    }

    public function testAuditIsTheOnlyGlobalMaintenanceEntryPoint(): void
    {
        $controller = file_get_contents(__DIR__ . '/../../administrator/components/com_breezingformsng/src/Controller/AboutController.php');
        $view = file_get_contents(__DIR__ . '/../../administrator/components/com_breezingformsng/src/View/About/HtmlView.php');
        $template = file_get_contents(__DIR__ . '/../../administrator/components/com_breezingformsng/tmpl/about/audit.php');
        $auditService = file_get_contents(__DIR__ . '/../../administrator/components/com_breezingformsng/src/Service/DatabaseAuditService.php');
        $repairService = file_get_contents(__DIR__ . '/../../administrator/components/com_breezingformsng/src/Service/DatabaseRepairService.php');

        self::assertIsString($controller);
        self::assertIsString($view);
        self::assertIsString($template);
        self::assertIsString($auditService);
        self::assertIsString($repairService);
        // The audit has its own layout (Tools / Audit), started from the Tools page.
        self::assertStringContainsString('if ($layout === \'audit\')', $view);
        self::assertStringNotContainsString("dropdownButton('about-actions-group')", $view);
        self::assertStringContainsString(
            'task=about.runAudit',
            (string) file_get_contents(__DIR__ . '/../../administrator/components/com_breezingformsng/tmpl/packages/default.php')
        );
        self::assertStringNotContainsString('about.startRepairWorkflow', $view);
        self::assertStringNotContainsString('function startRepairWorkflow()', $controller);
        self::assertStringNotContainsString('function migratePackedData()', $controller);
        self::assertStringContainsString('COM_BREEZINGFORMSNG_ABOUT_AUDIT_EMPTY', $template);
        self::assertStringContainsString('bf-audit-summary-table', $template);
        self::assertStringContainsString('bf-audit-detail-sections', $template);
        self::assertStringContainsString('about.repairTableCollations', $template);
        self::assertStringContainsString('function repairTableCollations(): void', $controller);
        self::assertStringContainsString('function repairTableCollations(array $selectedTokens): array', $repairService);
        self::assertStringContainsString("'facileforms_config'", $auditService);
        self::assertStringContainsString("'facileforms_records'", $auditService);
        self::assertStringContainsString("'facileforms_subrecords'", $auditService);
    }

    public function testAuditTitlesAreNeverEmptyAndOrphanValuesCanBeReassigned(): void
    {
        $base = __DIR__ . '/../../administrator/components/com_breezingformsng';
        $template = file_get_contents($base . '/tmpl/about/audit.php');
        $controller = file_get_contents($base . '/src/Controller/AboutController.php');
        $repair = file_get_contents($base . '/src/Service/DatabaseRepairService.php');
        $matcher = file_get_contents($base . '/src/Service/OrphanValueMatcher.php');

        self::assertIsString($template);
        self::assertIsString($controller);
        self::assertIsString($repair);
        self::assertIsString($matcher);
        self::assertStringNotContainsString("Text::_('')", $template);
        self::assertStringContainsString('about.reassignOrphanValues', $template);
        self::assertStringContainsString('function reassignOrphanValues()', $controller);
        self::assertStringContainsString('function reassignOrphanValues(', $repair);
        // Only unambiguous targets, free for the record, for values whose element is missing.
        self::assertStringContainsString('HAVING COUNT(*) = 1', $matcher);
        self::assertStringContainsString('x.id IS NULL', $repair);
        self::assertStringContainsString('o.id IS NULL', $matcher);
    }

    public function testRecordEditShowsValuesWithoutCurrentField(): void
    {
        $base = __DIR__ . '/../../administrator/components/com_breezingformsng';
        $model = file_get_contents($base . '/src/Model/RecordModel.php');
        $template = file_get_contents($base . '/tmpl/record/edit.php');

        self::assertIsString($model);
        self::assertIsString($template);
        self::assertStringContainsString('function getOrphanRows(', $model);
        self::assertStringContainsString('$item->orphan_values', $model);
        self::assertStringContainsString('record-orphan-values', $template);
        // Same layout as the fields of the record, with a read-only value.
        self::assertStringContainsString("'joomla.form.renderfield'", $template);
        self::assertStringContainsString('readonly', $template);
        // The value is escaped before it reaches the field.
        self::assertStringContainsString("\$value(\$text)", $template);
    }

    public function testOrphanValuesCanBeMovedToAFieldOfTheSameType(): void
    {
        $base = __DIR__ . '/../../administrator/components/com_breezingformsng';
        $template = file_get_contents($base . '/tmpl/about/audit.php');
        $controller = file_get_contents($base . '/src/Controller/AboutController.php');
        $repair = file_get_contents($base . '/src/Service/DatabaseRepairService.php');
        $matcher = file_get_contents($base . '/src/Service/OrphanValueMatcher.php');

        self::assertIsString($template);
        self::assertIsString($controller);
        self::assertIsString($repair);
        self::assertIsString($matcher);
        // One icon per element: the preview lists the values, with a checkbox each, before anything is moved.
        self::assertStringContainsString('data-bf-orphan-preview', $template);
        self::assertStringContainsString('data-bf-orphan-target-select', $template);
        self::assertStringContainsString('bf-orphan-reassign-dialog', $template);
        self::assertStringContainsString('function previewOrphanReassignment()', $controller);
        self::assertStringContainsString('function previewOrphanReassignment(', $repair);
        self::assertStringContainsString('ORPHAN_REASSIGN_COL_CURRENT', $template);
        // The checked values are posted one by one: the preview stays well under PHP's default max_input_vars (1000).
        self::assertStringContainsString('int $limit = 500', $repair);
        self::assertStringContainsString('data-bf-reassign-cancel aria-label', $template);
        // The elements offered as target are listed alphabetically, not in the order of the form.
        self::assertStringContainsString("quoteName('e.name') . ' ASC", (string) file_get_contents(__DIR__ . '/../../administrator/components/com_breezingformsng/src/Service/OrphanValueMatcher.php'));
        self::assertStringContainsString("'current' =>", $repair);
        self::assertStringContainsString('?array $selectedValueIds', $repair);
        self::assertStringContainsString('function reassignOrphanValuesToElement()', $controller);
        // The target must be in the form of the records and of the same type as the values.
        self::assertStringContainsString("quoteName('r.form') . ' = :form'", $repair);
        self::assertStringContainsString("typeClassSql(\$db, 's.type') . ' = :class'", $repair);
        self::assertStringContainsString("typeClassSql(\$db, 'e.type') . ' = ' . self::typeClassSql(\$db, 's.type')", $matcher);
        // Types holding one plain value are interchangeable (StripeId is a hidden input); the others only match themselves.
        self::assertStringContainsString("'Hidden Input'", $matcher);
        self::assertStringNotContainsString("'Checkbox Group'", $matcher);
    }

    public function testAuditListsEveryMissingElementInClosedAccordions(): void
    {
        $base = __DIR__ . '/../../administrator/components/com_breezingformsng';
        $audit = file_get_contents($base . '/src/Service/DatabaseAuditService.php');
        $template = file_get_contents($base . '/tmpl/about/audit.php');

        self::assertIsString($audit);
        self::assertIsString($template);
        self::assertStringNotContainsString('ORPHAN_GROUP_LIMIT', $audit);
        self::assertStringNotContainsString('array_slice($candidates', $audit);
        self::assertStringContainsString('class="bf-audit-orphans mb-1" data-bf-orphan-check', $template);
        self::assertStringNotContainsString('class="bf-audit-orphans mb-1" open', $template);
    }

    public function testEveryAuditSectionIsAnAccordionAndValuesCanBeSelected(): void
    {
        $base = __DIR__ . '/../../administrator/components/com_breezingformsng';
        $template = file_get_contents($base . '/tmpl/about/audit.php');
        $controller = file_get_contents($base . '/src/Controller/AboutController.php');

        self::assertIsString($template);
        self::assertIsString($controller);
        // The sections are accordions, open by default; their sub-sections (orphan checks) are closed.
        // The errors block alone stays a plain block.
        self::assertSame(15, substr_count($template, '<details class="bf-audit-section-block mb-3" open'));
        self::assertSame(15, substr_count($template, '<details class="bf-audit-section-block'));
        self::assertSame(1, substr_count($template, '<div class="bf-audit-section-block'));
        self::assertSame(substr_count($template, '<details'), substr_count($template, '</details>'));
        // The reassignment buttons are yellow, like the other repair buttons.
        self::assertStringNotContainsString('btn-outline-primary', $template);
        self::assertStringContainsString("'subrecords_without_element' => 'about.deleteOrphanValues'", $template);
        self::assertStringContainsString("orphan_ids", $controller);
        // Both kinds of orphan values are selectable, and every section shows its count in its title.
        self::assertStringContainsString("'subrecords_without_record' => 'about.deleteOrphanSubrecords'", $template);
        self::assertSame(15, substr_count($template, '<span class="badge bg-secondary ms-1">'));
        // The mixed collations list one sub-accordion per collation, with its tables.
        // The three collation sections are grouped in sub-accordions; the mixed ones count tables, not collations.
        self::assertSame(3, substr_count($template, '<details class="bf-audit-subsection mb-1">'));
        // Each mixed collation lists its tables in a striped table; the ones off the target can be repaired.
        self::assertStringContainsString('data-bf-select-item="<?php echo $mixedGroup; ?>" name="table_collation_issues[]"', $template);
        self::assertStringContainsString('$mixedIsTarget = strcasecmp(', $template);
        self::assertStringContainsString('array_sum($auditCollationHistogram)', $template);
    }

    public function testOrphanRepairActionsPrecedeTheLongResultTable(): void
    {
        $template = (string) file_get_contents(__DIR__ . '/../../administrator/components/com_breezingformsng/tmpl/about/audit.php');
        $actions = strpos($template, 'class="bf-audit-orphan-actions');
        $table = strpos($template, '<div class="table-responsive mt-2 mb-3">', $actions);

        self::assertNotFalse($actions);
        self::assertNotFalse($table);
        self::assertLessThan($table, strpos($template, 'data-bf-orphan-selection', $actions));
        self::assertLessThan($table, strpos($template, 'bf-audit-reassign-all-orphan-values', $actions));
        self::assertStringNotContainsString('data-bf-orphan-selection', substr($template, $table));
        self::assertStringNotContainsString('bf-audit-reassign-all-orphan-values', substr($template, $table));
    }

    public function testAuditLeavesJoomlaTemplateStylesToJoomla(): void
    {
        $base = __DIR__ . '/../../administrator/components/com_breezingformsng/src/Service';
        $audit = (string) file_get_contents($base . '/DatabaseAuditService.php');
        $repair = (string) file_get_contents($base . '/DatabaseRepairService.php');

        self::assertStringContainsString("\$excludedType = 'template';", $audit);
        self::assertStringContainsString("quoteName('type') . ' <> :excludedType'", $audit);
        self::assertStringNotContainsString('#__template_styles', $repair);
    }

    public function testExtensionsButtonsAreInTheToolbarBackFirst(): void
    {
        $base = __DIR__ . '/../../administrator/components/com_breezingformsng';
        $view = (string) file_get_contents($base . '/src/View/About/HtmlView.php');
        $template = (string) file_get_contents($base . '/tmpl/about/extensions.php');

        self::assertLessThan(
            (int) strpos($view, "linkButton('about_manage_plugins')"),
            (int) strpos($view, "linkButton('about_extensions_back')")
        );
        self::assertStringNotContainsString('COM_BREEZINGFORMSNG_ABOUT_EXTENSIONS_MANAGE_PLUGINS', $template);
        self::assertStringNotContainsString('HELP_BACK_TO_ABOUT', $template);
    }

    public function testExtensionsUseTheJoomlaStateToggle(): void
    {
        $base = __DIR__ . '/../../administrator/components/com_breezingformsng';
        $controller = (string) file_get_contents($base . '/src/Controller/AboutController.php');
        $view = (string) file_get_contents($base . '/src/View/About/HtmlView.php');
        $template = (string) file_get_contents($base . '/tmpl/about/extensions.php');

        self::assertStringContainsString('function setPluginState(): void', $controller);
        self::assertStringContainsString("checkToken('post', false)", $controller);
        self::assertStringContainsString("authorise('core.edit.state', 'com_plugins')", $controller);
        self::assertStringContainsString('data-item-task="about.setPluginState"', $template);
        self::assertStringContainsString('icon-publish', $template);
        self::assertStringContainsString('icon-unpublish', $template);
        self::assertStringContainsString('admin-toggle-published', $view);
    }

    public function testTheAuditCountsTheSameTrashAsTheToolsTile(): void
    {
        $base = __DIR__ . '/../../administrator/components/com_breezingformsng';
        $service = (string) file_get_contents($base . '/src/Service/DatabaseAuditService.php');
        $template = (string) file_get_contents($base . '/tmpl/about/audit.php');
        $tile = (string) file_get_contents($base . '/src/View/Packages/HtmlView.php');

        // The Tools tile adds the trashed submissions and the trashed forms, scripts and pieces: so does the audit.
        self::assertStringContainsString('RecordInventoryService', $tile);
        self::assertStringContainsString('(new TrashInventoryService($this->db))->getTrashedItems()', $service);
        self::assertStringContainsString("'trashed_items' => array_sum(array_column(\$trashedItems, 'count'))", $service);
        self::assertStringContainsString('$auditTrashedRecordsTotal + $auditTrashedItemsTotal', $template);
        self::assertStringContainsString('COM_BREEZINGFORMSNG_ABOUT_AUDIT_TRASHED_TOTAL_DETAIL', $template);
    }
}
