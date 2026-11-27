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
        // PackageList and PackageItem are the bases of the pieces and scripts controllers.
        foreach (['PackageList', 'PackageItem', 'Integrator'] as $area) {
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

    public function testPackageTransferTasksAssertTheirPermission(): void
    {
        $source = file_get_contents(self::ADMIN . '/src/Controller/PackagesController.php');
        $about = file_get_contents(self::ADMIN . '/src/Controller/AboutController.php');

        self::assertIsString($source);
        self::assertIsString($about);
        self::assertStringContainsString('AreaAccess::assert($user, AreaAccess::CONFIGURATION);', $source);
        // The raw configuration export and import are gone: no task is left unguarded in About.
        self::assertSame(0, substr_count($about, 'AreaAccess::CONFIGURATION);'));
    }

    public function testPiecesAndScriptsAreNotReadableWithoutTheCodePermission(): void
    {
        foreach (['PackageItem', 'PackageList'] as $name) {
            $controller = file_get_contents(self::ADMIN . '/src/Controller/' . $name . 'Controller.php');

            self::assertIsString($controller);
            self::assertStringContainsString('AreaAccess::assert($user, AreaAccess::CODE);', $controller);
            self::assertStringNotContainsString('readOnlyTasks', $controller);
        }

        // A view can be displayed by another controller: each one checks too.
        foreach (['Script', 'Scripts', 'Piece', 'Pieces'] as $area) {
            $view = file_get_contents(self::ADMIN . '/src/View/' . $area . '/HtmlView.php');

            self::assertIsString($view);
            self::assertStringContainsString(
                'AreaAccess::assert($this->getCurrentUser(), AreaAccess::CODE);',
                $view
            );
        }
    }

    public function testThePieceTestPageRequiresTheCodePermission(): void
    {
        $view = file_get_contents(self::ADMIN . '/src/View/Piece/HtmlView.php');
        $controller = file_get_contents(self::ADMIN . '/src/Controller/PieceController.php');

        self::assertIsString($view);
        self::assertIsString($controller);
        self::assertMatchesRegularExpression(
            "/getLayout\\(\\) === 'test'\\) \\{\\s+\\/\\/ [^\\n]+\\s+if \\(!\\\$this->canEdit\\) \\{\\s+throw new \\\\Exception\\(Text::_\\('JERROR_ALERTNOAUTHOR'\\), 403\\);/",
            $view
        );
        self::assertSame(2, substr_count($controller, '$this->checkToken();'));
    }

    public function testAuditRepairsRequireAnAdministrator(): void
    {
        $controller = file_get_contents(self::ADMIN . '/src/Controller/AboutController.php');
        $template = file_get_contents(self::ADMIN . '/tmpl/about/audit.php');

        self::assertIsString($controller);
        self::assertIsString($template);
        self::assertSame(8, substr_count($controller, 'AreaAccess::assertRepair('));
        // The orphan repairs and the reassignment preview also need the right to delete of the component.
        self::assertSame(3, substr_count($controller, 'AreaAccess::assertDeleteOrphans('));
        self::assertStringContainsString("\$this->canDeleteOrphans ? '' : ' disabled'", $template);
        self::assertStringContainsString("\$this->canRepair ? '' : ' disabled'", $template);
    }

    public function testIntegratorTrashButtonRequiresDeleteAndCodePermissions(): void
    {
        $view = file_get_contents(self::ADMIN . '/src/View/About/HtmlView.php');

        self::assertIsString($view);
        self::assertStringContainsString("'integrator' => \$canDelete && \$canCode", $view);
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
