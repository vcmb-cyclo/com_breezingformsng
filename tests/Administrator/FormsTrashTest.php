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

final class FormsTrashTest extends TestCase
{
    private function read(string $path): string
    {
        $content = file_get_contents(__DIR__ . '/../../' . $path);
        self::assertIsString($content);

        return $content;
    }

    private function admin(string $path): string
    {
        return $this->read('administrator/components/com_breezingformsng/' . $path);
    }

    public function testTheTrashTaskIsTheNativeTokenCheckedOne(): void
    {
        $native = $this->read('joomla-stubs/libraries/src/MVC/Controller/AdminController.php');

        self::assertStringContainsString("registerTask('trash', 'publish')", $native);
        self::assertStringContainsString("'trash' => -2", $native);
        self::assertMatchesRegularExpression('/function publish\(\)\s*\{\s*(\/\/[^\n]*\n\s*)?\$this->checkToken\(\)/', $native);
        // No override that would bypass the native task.
        self::assertStringNotContainsString('function trash(', $this->admin('src/Controller/FormsController.php'));
    }

    public function testDeleteRefusesAnyFormThatIsNotTrashed(): void
    {
        $controller = $this->admin('src/Controller/FormsController.php');
        $model = $this->admin('src/Model/FormModel.php');

        self::assertMatchesRegularExpression(
            '/public function delete\(\): void\s*\{\s*\$this->checkToken\(\);.*notTrashed\(\$ids\) !== \[\].*'
            . 'JLIB_APPLICATION_ERROR_DELETE_NOT_PERMITTED.*return;.*parent::delete\(\);/s',
            $controller
        );
        self::assertStringContainsString("->where(\$db->quoteName('published') . ' = -2')", $controller);
        // The model must protect other trusted callers as well as this route.
        self::assertMatchesRegularExpression('/protected function canDelete\(\$record\).*published \?\? 0\).*parent::canDelete\(\$record\)/s', $model);
        // The toggle never restores a trashed form by accident.
        self::assertMatchesRegularExpression('/isTrashed\(\$id\).*AjaxStateService::error/s', $controller);
    }

    public function testTheListHidesTheTrashUnlessAskedFor(): void
    {
        $model = $this->admin('src/Model/FormsModel.php');

        self::assertMatchesRegularExpression(
            "/elseif \(\\\$published !== '\*'\) \{[^}]*quoteName\('published'\) \. ' >= 0'/s",
            $model
        );
        self::assertStringContainsString('optionsFilter="*,-2,0,1"', $this->admin('forms/filter_forms.xml'));
    }

    public function testTheToolbarOffersDeleteOnlyInTheTrash(): void
    {
        $view = $this->admin('src/View/Forms/HtmlView.php');

        self::assertSame(1, substr_count($view, 'deleteList('));
        self::assertMatchesRegularExpression(
            "/filter\.published'\) === '-2'\) \{[^}]*deleteList\('JGLOBAL_CONFIRM_DELETE', 'forms\.delete', 'JTOOLBAR_EMPTY_TRASH'\);"
            . "\s*\} else \{\s*ToolbarHelper::trash\('forms\.trash'\);/s",
            $view
        );
    }

    public function testTheTemplateTreatsOnlyOneAsPublished(): void
    {
        $template = $this->admin('tmpl/forms/default.php');

        self::assertStringNotContainsString('$form->published ?', $template);
        // The trashed icon is red, like the ones of the records, scripts, pieces and rules lists.
        self::assertStringContainsString('icon-trash text-danger', $template);
        self::assertStringContainsString('(int) $form->published === 1', $template);
    }

    public function testATrashedFormIsNeverServedOrOffered(): void
    {
        $renderer = $this->read('components/com_breezingformsng/src/Service/FormRenderer.php');
        self::assertSame(2, substr_count($renderer, "->where(\$this->database->quoteName('published') . ' = 1')"));
        self::assertStringContainsString("'form.published = 1'", str_replace('->where(\'form.published = 1\')', "'form.published = 1'", $this->read('components/com_breezingformsng/src/Service/Callback/FlashUploadCallback.php')));
        self::assertStringContainsString('loadChoices("#__facileforms_forms", true)', $this->admin('src/Model/PackageTransferModel.php'));
        self::assertStringContainsString("quoteName('published') . ' >= 0'", $this->admin('src/Model/RecordstransferModel.php'));
        self::assertStringContainsString("quoteName('f.published') . ' >= 0'", $this->admin('src/Mcp/FormCatalog.php'));
        self::assertStringContainsString("quoteName('published') . ' >= 0'", $this->admin('src/Model/FormModel.php'));
        self::assertStringContainsString('sql_where="published &gt;= 0"', $this->admin('forms/recordsimport.xml'));
    }
}
