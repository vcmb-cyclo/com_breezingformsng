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

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator\View;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PackageLibraryUiRegressionTest extends TestCase
{
    private const ROOT = __DIR__ . '/../../..';

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function libraryProvider(): iterable
    {
        yield 'pieces' => ['Piece', 'piece'];
        yield 'scripts' => ['Script', 'script'];
    }

    #[DataProvider('libraryProvider')]
    public function testLibrariesUseTheNativeListAndFormMvc(string $name, string $view): void
    {
        $admin = 'administrator/components/com_breezingformsng';
        $list = $this->read("{$admin}/tmpl/{$view}s/default.php");
        $edit = $this->read("{$admin}/tmpl/{$view}/edit.php");

        self::assertStringContainsString('extends PackageListModel', $this->read("{$admin}/src/Model/{$name}sModel.php"));
        self::assertStringContainsString('extends PackageItemModel', $this->read("{$admin}/src/Model/{$name}Model.php"));
        self::assertStringContainsString('extends PackageListController', $this->read("{$admin}/src/Controller/{$name}sController.php"));
        self::assertStringContainsString('extends PackageItemController', $this->read("{$admin}/src/Controller/{$name}Controller.php"));
        self::assertStringContainsString("LayoutHelper::render('joomla.searchtools.default', ['view' => \$this, 'options' => ['selectorFieldName' => 'package']])", $list);
        self::assertStringContainsString('$this->pagination->getListFooter()', $list);
        self::assertStringContainsString("task={$view}.edit&id=", $list);
        self::assertStringContainsString("\$this->form->renderFieldset('details')", $edit);
        self::assertStringContainsString("\$this->form->renderField('code')", $edit);
        self::assertFileDoesNotExist(self::ROOT . "/{$admin}/src/View/{$name}s/Renderer.php");
        self::assertFileDoesNotExist(self::ROOT . "/media/com_breezingformsng/js/admin/{$view}s-list.js");
    }

    public function testTheSharedBasesAreNativeJoomlaClasses(): void
    {
        $admin = 'administrator/components/com_breezingformsng/src';

        self::assertStringContainsString('extends ListModel', $this->read($admin . '/Model/PackageListModel.php'));
        self::assertStringContainsString('extends AdminModel', $this->read($admin . '/Model/PackageItemModel.php'));
        self::assertStringContainsString('extends AdminController', $this->read($admin . '/Controller/PackageListController.php'));
        self::assertStringContainsString('extends FormController', $this->read($admin . '/Controller/PackageItemController.php'));
        self::assertFileDoesNotExist(self::ROOT . '/' . $admin . '/Service/ScriptManager.php');
        self::assertFileDoesNotExist(self::ROOT . '/' . $admin . '/Service/PieceManager.php');
        self::assertFileDoesNotExist(self::ROOT . '/' . $admin . '/Model/PackageModel.php');
    }

    private function read(string $path): string
    {
        $source = file_get_contents(self::ROOT . '/' . $path);

        self::assertNotFalse($source, "Unable to read {$path}");

        return $source;
    }
}
