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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The scripts and pieces lists have a Joomla-style trash: published = -2.
 */
final class ScriptsPiecesTrashTest extends TestCase
{
    private const ADMIN = __DIR__ . '/../../administrator/components/com_breezingformsng/';
    private const SITE = __DIR__ . '/../../components/com_breezingformsng/';

    private function read(string $path): string
    {
        return (string) file_get_contents($path);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function lists(): array
    {
        return ['scripts' => ['scripts'], 'pieces' => ['pieces']];
    }

    #[DataProvider('lists')]
    public function testFilterOffersTheTrash(string $list): void
    {
        self::assertStringContainsString('optionsFilter="*,-2,0,1"', $this->read(self::ADMIN . 'forms/filter_' . $list . '.xml'));
    }

    public function testListModelHidesTheTrashByDefault(): void
    {
        $model = $this->read(self::ADMIN . 'src/Model/PackageListModel.php');

        self::assertStringContainsString("elseif (\$published !== '*')", $model);
        self::assertStringContainsString("quoteName('published') . ' >= 0'", $model);
    }

    public function testTrashTaskGoesThroughTheCheckedPublishTask(): void
    {
        $controller = $this->read(self::ADMIN . 'src/Controller/PackageListController.php');

        // AdminController maps trash to publish; this controller delegates to it.
        self::assertStringContainsString('parent::publish()', $controller);
        self::assertStringContainsString('AreaAccess::assert($user, AreaAccess::CODE)', $controller);
    }

    public function testDeleteRefusesWhatIsNotTrashed(): void
    {
        $model = $this->read(self::ADMIN . 'src/Model/PackageItemModel.php');

        self::assertStringContainsString('public const int TRASHED = -2;', $model);
        self::assertStringContainsString('!$this->allTrashed($ids)', $model);
        self::assertStringContainsString("throw new \\RuntimeException(Text::_('JLIB_APPLICATION_ERROR_DELETE_NOT_PERMITTED'))", $model);
        self::assertStringContainsString('(int) ($record->published ?? 0) === self::TRASHED', $model);
        self::assertStringContainsString("quoteName('published') . ' = :trashed'", $model);

        $controller = $this->read(self::ADMIN . 'src/Controller/PackageListController.php');
        self::assertStringContainsString('public function delete(): void', $controller);
        self::assertStringContainsString('!$model->isTrashed($id)', $controller);
    }

    #[DataProvider('lists')]
    public function testToolbarDeletesOnlyInTheTrashFilter(string $list): void
    {
        $view = $this->read(self::ADMIN . 'src/View/' . ucfirst($list) . '/HtmlView.php');

        self::assertStringContainsString("trash('" . $list . ".trash')", $view);
        self::assertStringContainsString("delete('" . $list . ".delete', 'JTOOLBAR_EMPTY_TRASH')", $view);
        self::assertStringContainsString("message('JGLOBAL_CONFIRM_DELETE')", $view);
        self::assertSame(1, substr_count($view, "->delete('"));
        self::assertMatchesRegularExpression(
            '/filter\.published\'\) === \(string\) PackageItemModel::TRASHED\s*&&\s*\$user->authorise\(\'core\.delete\'/',
            $view
        );
    }

    #[DataProvider('lists')]
    public function testTemplateTreatsOnlyOneAsPublishedAndShowsTheTrashIcon(string $list): void
    {
        $template = $this->read(self::ADMIN . 'tmpl/' . $list . '/default.php');

        self::assertStringContainsString('(int) $row->published === PackageItemModel::TRASHED', $template);
        self::assertStringContainsString('(int) $row->published === 1', $template);
        self::assertStringContainsString('icon-trash', $template);
        self::assertStringNotContainsString('$row->published ?', $template);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function runtimeReaders(): array
    {
        return [
            'site repository' => [self::SITE . 'src/Service/Scripting/Repository.php', "'published') . ' = 1'"],
            'form pieces' => [self::SITE . 'src/Service/Rendering/FormPieceExecutionService.php', "'published') . ' = 1'"],
            'submitted callback' => [self::SITE . 'src/Service/Rendering/SubmittedCallbackNameResolver.php', "'published') . ' = 1'"],
            'quick mode' => [self::ADMIN . 'src/Model/QuickmodeModel.php', "'published') . ' = 1'"],
            'forms selectors' => [self::ADMIN . 'src/Model/FormsModel.php', "'published') . ' = 1'"],
            'piece test runner' => [self::ADMIN . 'src/Service/PieceTestRunner.php', "'published') . ' = 1'"],
        ];
    }

    #[DataProvider('runtimeReaders')]
    public function testRuntimeReadersRunOnlyPublishedCode(string $file, string $needle): void
    {
        self::assertStringContainsString($needle, $this->read($file));
    }

    public function testPackageExportLeavesTheTrashOut(): void
    {
        $model = $this->read(self::ADMIN . 'src/Model/PackageTransferModel.php');

        self::assertStringContainsString('loadChoices("#__facileforms_scripts", true)', $model);
        self::assertStringContainsString('loadChoices("#__facileforms_pieces", true)', $model);
        self::assertStringContainsString("loadPackageRows('#__facileforms_scripts', \$package, true)", $model);
        self::assertStringContainsString("loadPackageRows('#__facileforms_pieces', \$package, true)", $model);
    }
}
