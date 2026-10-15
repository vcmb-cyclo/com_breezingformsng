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

final class FormsListActionsArchitectureTest extends TestCase
{
    private const ADMIN = __DIR__ . '/../../administrator/components/com_breezingformsng';

    public function testListActionsGoThroughTheNativeAdminControllerAndModel(): void
    {
        $controller = $this->read('/src/Controller/FormsController.php');
        $model = $this->read('/src/Model/FormModel.php');

        self::assertStringContainsString('class FormsController extends AdminController', $controller);
        self::assertStringContainsString('class FormModel extends AdminModel', $model);
        self::assertStringContainsString("getTable(\$name = 'Form', \$prefix = 'Site'", $model);

        // Publish, delete and reorder are the inherited tasks of AdminController.
        foreach (['remove', 'publish', 'unpublish', 'orderup', 'orderdown'] as $task) {
            self::assertStringNotContainsString('public function ' . $task . '(', $controller);
        }

        self::assertStringContainsString('protected function postDeleteHook(', $controller);
    }

    public function testDeletingAFormDeletesItsElementsInTheSameTransaction(): void
    {
        $model = $this->read('/src/Model/FormModel.php');

        self::assertMatchesRegularExpression(
            '/transactionStart\(\);.*parent::delete\(\$ids\).*#__facileforms_elements.*transactionCommit\(\);/s',
            $model
        );
        self::assertStringContainsString('$db->transactionRollback();', $model);
    }

    public function testTheListUsesTheNativeDeleteTask(): void
    {
        $view = $this->read('/src/View/Forms/HtmlView.php');

        self::assertStringContainsString("ToolbarHelper::deleteList('JGLOBAL_CONFIRM_DELETE', 'forms.delete')", $view);
        self::assertStringNotContainsString('forms.remove', $view);
        // The toolbar button asks for the confirmation itself.
        self::assertStringNotContainsString('confirmDeleteTask', $view);
    }

    public function testTheFormOptionsKeepTheirOwnModel(): void
    {
        self::assertStringContainsString(
            'class FormOptionsModel extends BaseDatabaseModel',
            $this->read('/src/Model/FormOptionsModel.php')
        );
        self::assertStringContainsString(
            "createModel('FormOptions', 'Administrator'",
            $this->read('/src/View/Quickmode/HtmlView.php')
        );
    }

    private function read(string $path): string
    {
        $source = file_get_contents(self::ADMIN . $path);

        self::assertIsString($source);

        return $source;
    }
}
