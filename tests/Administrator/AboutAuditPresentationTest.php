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
    public function testAuditIsTheOnlyGlobalMaintenanceEntryPoint(): void
    {
        $controller = file_get_contents(__DIR__ . '/../../administrator/components/com_breezingformsng/src/Controller/AboutController.php');
        $view = file_get_contents(__DIR__ . '/../../administrator/components/com_breezingformsng/src/View/About/HtmlView.php');
        $template = file_get_contents(__DIR__ . '/../../administrator/components/com_breezingformsng/tmpl/about/default.php');
        $auditService = file_get_contents(__DIR__ . '/../../administrator/components/com_breezingformsng/src/Service/DatabaseAuditService.php');
        $repairService = file_get_contents(__DIR__ . '/../../administrator/components/com_breezingformsng/src/Service/DatabaseRepairService.php');

        self::assertIsString($controller);
        self::assertIsString($view);
        self::assertIsString($template);
        self::assertIsString($auditService);
        self::assertIsString($repairService);
        self::assertStringContainsString("->task('about.runAudit')", $view);
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
        $template = file_get_contents($base . '/tmpl/about/default.php');
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
        $template = file_get_contents($base . '/tmpl/about/default.php');
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

    public function testAuditListsEveryMissingElementAndOpensItsAccordions(): void
    {
        $base = __DIR__ . '/../../administrator/components/com_breezingformsng';
        $audit = file_get_contents($base . '/src/Service/DatabaseAuditService.php');
        $template = file_get_contents($base . '/tmpl/about/default.php');

        self::assertIsString($audit);
        self::assertIsString($template);
        self::assertStringNotContainsString('ORPHAN_GROUP_LIMIT', $audit);
        self::assertStringNotContainsString('array_slice($candidates', $audit);
        self::assertStringContainsString('class="bf-audit-orphans mb-1" open', $template);
    }

    public function testRemovingALegacyTemplateNeverLeavesAClientWithoutDefaultStyle(): void
    {
        $repair = file_get_contents(__DIR__ . '/../../administrator/components/com_breezingformsng/src/Service/DatabaseRepairService.php');

        self::assertIsString($repair);
        self::assertStringContainsString('function deleteTemplateStyles(', $repair);
        self::assertStringContainsString("'atum' : 'cassiopeia'", $repair);
        self::assertStringContainsString("quoteName('template_style_id') . ' = 0'", $repair);
        self::assertStringContainsString('No \' . $coreTemplate', $repair);
    }
}
