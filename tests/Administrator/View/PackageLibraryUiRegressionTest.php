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
        yield 'pieces' => ['Pieces', 'pieces'];
    }

    #[DataProvider('libraryProvider')]
    public function testListAssetIsRegisteredBeforeRendering(string $view, string $asset): void
    {
        $source = $this->read("administrator/components/com_breezingformsng/src/View/{$view}/HtmlView.php");

        self::assertStringContainsString("'com_breezingformsng.{$asset}-list'", $source);
        self::assertStringContainsString("'media/com_breezingformsng/js/admin/{$asset}-list.js'", $source);
        self::assertLessThan(
            strpos($source, 'parent::display($tpl)'),
            strpos($source, "'com_breezingformsng.{$asset}-list'")
        );
    }

    #[DataProvider('libraryProvider')]
    public function testListFilterHandlerIsExposedGlobally(string $view, string $asset): void
    {
        $function = 'bf' . $view . 'SubmitList';
        $source = $this->read("media/com_breezingformsng/js/admin/{$asset}-list.js");

        self::assertStringContainsString("window.{$function} = {$function};", $source);
    }

    #[DataProvider('libraryProvider')]
    public function testListsUseNativeFiltersAndColumnSelector(string $view, string $asset): void
    {
        $viewSource = $this->read("administrator/components/com_breezingformsng/src/View/{$view}/HtmlView.php");
        $rendererSource = $this->read("administrator/components/com_breezingformsng/src/View/{$view}/Renderer.php");

        self::assertStringContainsString("useScript('table.columns')", $viewSource);
        self::assertStringContainsString('d-flex flex-wrap gap-2 mb-3 align-items-center', $rendererSource);
        self::assertStringContainsString('id="filter_package"', $rendererSource);
        self::assertStringContainsString('id="filter_state"', $rendererSource);
        self::assertStringContainsString('id="filter_search"', $rendererSource);
        self::assertStringContainsString('id="filter_search_submit"', $rendererSource);
        self::assertStringContainsString('id="check-all"', $rendererSource);
        self::assertStringContainsString("data-name=\"breezingformsng-{$asset}\"", $rendererSource);
    }

    #[DataProvider('libraryProvider')]
    public function testToolbarTasksAreQualified(string $view, string $controller): void
    {
        $source = $this->read("administrator/components/com_breezingformsng/src/View/{$view}/Renderer.php");
        preg_match_all("/ToolBarHelper::custom\\('([^']+)'/", $source, $matches);

        self::assertNotEmpty($matches[1]);

        foreach ($matches[1] as $task) {
            self::assertStringStartsWith("{$controller}.", $task, "Unqualified toolbar task: {$task}");
        }
    }

    #[DataProvider('libraryProvider')]
    public function testListActionsAreAddedBeforeRightAlignedHelp(string $view, string $controller): void
    {
        $source = $this->read("administrator/components/com_breezingformsng/src/View/{$view}/HtmlView.php");
        $firstAction = strpos($source, "ToolbarHelper::custom('{$controller}.add'");
        $help = strpos($source, 'ToolbarHelper::help(');

        self::assertNotFalse($firstAction);
        self::assertNotFalse($help);
        self::assertLessThan($help, $firstAction);
    }

    public function testRequestedOrderingIsAppliedAfterJoomlaStateInitialisation(): void
    {
        $source = $this->read('administrator/components/com_breezingformsng/src/Model/PackageModel.php');
        $initialisation = strpos($source, '$this->getState();');
        $ordering = strpos($source, "\$this->setState('list.ordering'", $initialisation ?: 0);

        self::assertNotFalse($initialisation);
        self::assertNotFalse($ordering);
        self::assertLessThan($ordering, $initialisation);
        self::assertStringContainsString("'a.title' => 'a.title'", $source);
    }

    public function testPieceModelSharesListPreparationInPackageModel(): void
    {
        $packageModel = $this->read('administrator/components/com_breezingformsng/src/Model/PackageModel.php');
        $pieceModel = $this->read('administrator/components/com_breezingformsng/src/Model/PieceModel.php');

        self::assertStringContainsString('public function prepareList(', $packageModel);
        self::assertStringContainsString('abstract protected function getSessionPrefix(): string;', $packageModel);
        self::assertStringContainsString("return 'pieces';", $pieceModel);
        self::assertStringNotContainsString('public function prepareList(', $pieceModel);
    }

    public function testScriptsUseTheNativeListAndFormMvc(): void
    {
        $admin = 'administrator/components/com_breezingformsng';
        $list = $this->read($admin . '/tmpl/scripts/default.php');
        $edit = $this->read($admin . '/tmpl/script/edit.php');

        self::assertStringContainsString('extends ListModel', $this->read($admin . '/src/Model/ScriptsModel.php'));
        self::assertStringContainsString('extends AdminModel', $this->read($admin . '/src/Model/ScriptModel.php'));
        self::assertStringContainsString('extends AdminController', $this->read($admin . '/src/Controller/ScriptsController.php'));
        self::assertStringContainsString('extends FormController', $this->read($admin . '/src/Controller/ScriptController.php'));
        self::assertStringContainsString("LayoutHelper::render('joomla.searchtools.default', ['view' => \$this])", $list);
        self::assertStringContainsString('$this->pagination->getListFooter()', $list);
        self::assertStringContainsString('task=script.edit&id=', $list);
        self::assertStringContainsString("\$this->form->renderFieldset('details')", $edit);
        self::assertStringContainsString("\$this->form->renderField('code')", $edit);
        self::assertFileDoesNotExist(self::ROOT . '/' . $admin . '/src/View/Scripts/Renderer.php');
        self::assertFileDoesNotExist(self::ROOT . '/' . $admin . '/src/Service/ScriptManager.php');
    }

    public function testSortInitialisationSupportsLateAssetLoading(): void
    {
        $source = $this->read('media/com_breezingformsng/js/admin/admin-sort.js');

        self::assertStringContainsString("document.readyState === 'loading'", $source);
        self::assertStringContainsString('initialiseAdminSort();', $source);
        self::assertStringContainsString('data-bf-sort-initialised', str_replace('dataset.bfSortInitialised', 'data-bf-sort-initialised', $source));
    }

    private function read(string $path): string
    {
        $source = file_get_contents(self::ROOT . '/' . $path);

        self::assertNotFalse($source, "Unable to read {$path}");

        return $source;
    }
}
