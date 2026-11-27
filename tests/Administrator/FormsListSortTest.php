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

final class FormsListSortTest extends TestCase
{
    public function testFormsListSortsThroughTheNativeSearchTools(): void
    {
        $admin = __DIR__ . '/../../administrator/components/com_breezingformsng';
        $view = file_get_contents($admin . '/src/View/Forms/HtmlView.php');
        $template = file_get_contents($admin . '/tmpl/forms/default.php');

        self::assertIsString($view);
        self::assertIsString($template);
        self::assertStringNotContainsString('admin-sort', $view);
        self::assertStringContainsString("LayoutHelper::render('joomla.searchtools.default', ['view' => \$this])", $template);
        self::assertStringContainsString("HTMLHelper::_('searchtools.sort', 'COM_BREEZINGFORMSNG_FORMS_TITLE', 'title'", $template);
        self::assertStringNotContainsString('filter_order', $template);
    }

    public function testTheAuditColumnsAreSortableAndHiddenButTheModificationDate(): void
    {
        $admin = __DIR__ . '/../../administrator/components/com_breezingformsng';
        $template = (string) file_get_contents($admin . '/tmpl/forms/default.php');
        $model = (string) file_get_contents($admin . '/src/Model/FormsModel.php');

        foreach (['modified_by', 'created', 'created_by'] as $column) {
            self::assertStringContainsString("'" . $column . "', \$listDirn, \$listOrder", $template, $column);
        }

        self::assertStringContainsString("'modified_by', 'created', 'created_by',", $model);
        self::assertStringContainsString(
            '<td class="text-nowrap"><?= $date($form->modified ?? null); ?></td>',
            $template
        );
        self::assertSame(4, substr_count($template, '<th class="d-none'));
        // Creation first, then modification.
        self::assertMatchesRegularExpression(
            "/'created', .*'created_by', .*'modified', .*'modified_by', /s",
            $template
        );
    }

    public function testAnImportedItemRecordsWhoWroteIt(): void
    {
        $model = (string) file_get_contents(
            __DIR__ . '/../../administrator/components/com_breezingformsng/src/Model/PackageTransferModel.php'
        );

        // Forms, scripts and pieces.
        self::assertSame(2, substr_count($model, '$data = array_merge($data, $this->auditStamp($id <= 0));'));
        // A replaced item keeps who created it: those columns are set for a new one only.
        self::assertStringContainsString("\$stamp = ['modified' => \$now, 'modified_by' => \$user];", $model);
        self::assertStringContainsString(
            "return \$isNew ? \$stamp + ['created' => \$now, 'created_by' => \$user] : \$stamp;",
            $model
        );
    }

    public function testTheOtherListsShowTheSameAuditColumns(): void
    {
        $admin = __DIR__ . '/../../administrator/components/com_breezingformsng';

        foreach (['scripts' => '', 'pieces' => '', 'integrator' => 'rules.'] as $list => $prefix) {
            $template = (string) file_get_contents($admin . '/tmpl/' . $list . '/default.php');
            $view = (string) file_get_contents($admin . '/src/View/' . ucfirst($list) . '/HtmlView.php');

            self::assertStringContainsString('data-name="bfng-' . $list . '"', $template);
            self::assertStringContainsString("'com_breezingformsng.forms-columns'", $view);
            self::assertMatchesRegularExpression(
                "/'{$prefix}created', .*'{$prefix}created_by', .*'{$prefix}modified', .*'{$prefix}modified_by', /s",
                $template,
                $list
            );
        }
    }
}
