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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AreaAccessArchitectureTest extends TestCase
{
    private const ADMIN = __DIR__ . '/../../administrator/components/com_breezingformsng';

    /** @return iterable<string, array{string, string}> */
    public static function guardedFiles(): iterable
    {
        foreach (['Pieces', 'Scripts', 'Integrator'] as $area) {
            yield $area . ' controller' => ['/src/Controller/' . $area . 'Controller.php', 'AreaAccess::CODE'];
        }

        yield 'Integrator view' => ['/src/View/Integrator/HtmlView.php', 'AreaAccess::CODE'];

        yield 'Packages controller' => ['/src/Controller/PackagesController.php', 'AreaAccess::CONFIGURATION'];
        yield 'Packages view' => ['/src/View/Packages/HtmlView.php', 'AreaAccess::CONFIGURATION'];
    }

    #[DataProvider('guardedFiles')]
    public function testSensitiveAreaAssertsItsPermission(string $file, string $action): void
    {
        $source = file_get_contents(self::ADMIN . $file);

        self::assertIsString($source);
        self::assertMatchesRegularExpression(
            '/AreaAccess::assert\([^;]+' . preg_quote($action, '/') . '\);/',
            $source
        );
    }

    public function testConfigurationTransferTasksAssertTheirPermission(): void
    {
        $source = file_get_contents(self::ADMIN . '/src/Controller/AboutController.php');

        self::assertIsString($source);
        self::assertSame(2, substr_count($source, 'AreaAccess::CONFIGURATION);'));
    }

    public function testPiecesStayReadableWithoutTheCodePermission(): void
    {
        $view = file_get_contents(self::ADMIN . '/src/View/Pieces/HtmlView.php');
        $renderer = file_get_contents(self::ADMIN . '/src/View/Pieces/Renderer.php');
        $controller = file_get_contents(self::ADMIN . '/src/Controller/PiecesController.php');

        self::assertIsString($view);
        self::assertIsString($renderer);
        self::assertIsString($controller);
        self::assertStringNotContainsString('AreaAccess::assert(', $view);
        self::assertStringContainsString("\$canEdit ? '' : ' disabled'", $renderer);
        self::assertStringContainsString('READ_ONLY_TASKS', $controller);

        foreach (['save', 'copy', 'remove', 'publish', 'unpublish', 'setpublished', 'add'] as $writeTask) {
            self::assertDoesNotMatchRegularExpression(
                "/READ_ONLY_TASKS = \\[[^\\]]*'" . $writeTask . "'/",
                $controller
            );
        }
    }

    public function testScriptsStayReadableWithoutTheCodePermission(): void
    {
        $view = file_get_contents(self::ADMIN . '/src/View/Script/HtmlView.php');
        $template = file_get_contents(self::ADMIN . '/tmpl/script/edit.php');
        $item = file_get_contents(self::ADMIN . '/src/Controller/ScriptController.php');
        $list = file_get_contents(self::ADMIN . '/src/Controller/ScriptsController.php');

        self::assertIsString($view);
        self::assertIsString($template);
        self::assertIsString($item);
        self::assertIsString($list);
        self::assertStringNotContainsString('AreaAccess::assert(', $view);
        self::assertStringContainsString("\$this->canEdit ? '' : ' disabled'", $template);
        self::assertStringContainsString("READ_ONLY_TASKS = ['edit', 'cancel', 'previous', 'next', 'test'];", $item);
        self::assertStringContainsString('AreaAccess::assert($user, AreaAccess::CODE);', $item);
        self::assertStringContainsString('AreaAccess::assert($user, AreaAccess::CODE);', $list);
        self::assertStringNotContainsString('READ_ONLY_TASKS', $list);
    }

    public function testAuditRepairsRequireAnAdministrator(): void
    {
        $controller = file_get_contents(self::ADMIN . '/src/Controller/AboutController.php');
        $template = file_get_contents(self::ADMIN . '/tmpl/about/default.php');

        self::assertIsString($controller);
        self::assertIsString($template);
        self::assertSame(6, substr_count($controller, 'AreaAccess::assertRepair('));
        self::assertStringContainsString("\$this->canRepair ? '' : ' disabled'", $template);
    }

    public function testAccessFileDeclaresEveryEnforcedAction(): void
    {
        $access = simplexml_load_file(self::ADMIN . '/access.xml');

        self::assertNotFalse($access);
        $actions = array_map('strval', $access->xpath('/access/section[@name="component"]/action/@name'));

        foreach (['core.admin', 'core.options', 'core.manage', 'breezingformsng.code', 'breezingformsng.configuration'] as $action) {
            self::assertContains($action, $actions);
        }
    }
}
