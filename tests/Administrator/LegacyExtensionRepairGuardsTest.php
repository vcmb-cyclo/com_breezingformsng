<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator;

use PHPUnit\Framework\TestCase;

final class LegacyExtensionRepairGuardsTest extends TestCase
{
    private const ADMIN = __DIR__ . '/../../administrator/components/com_breezingformsng';

    public function testUninstallRequiresTheInstallerDeleteRight(): void
    {
        $source = (string) file_get_contents(self::ADMIN . '/src/Controller/AboutController.php');
        $method = substr($source, (int) strpos($source, 'function removeLegacyExtensions'));
        $method = substr($method, 0, (int) strpos($method, 'function deleteDuplicateForm'));

        self::assertStringContainsString("authorise('core.delete', 'com_installer')", $method);
    }

    public function testRegistrationIsOnlyDroppedWhenItsFilesAreGone(): void
    {
        $source = (string) file_get_contents(self::ADMIN . '/src/Service/DatabaseRepairService.php');

        self::assertMatchesRegularExpression('/isOrphanRegistration\(\$entry\)\)\s*\{\s*throw/', $source);
    }

    public function testAutomaticReassignmentRequiresACompatibleType(): void
    {
        $source = (string) file_get_contents(self::ADMIN . '/src/Service/OrphanValueMatcher.php');

        // Same name and a compatible type: plain-value types (text, hidden input...) are
        // interchangeable, a multi-valued type (checkbox group...) only matches itself.
        self::assertStringContainsString("e.name = s.name AND '", $source);
        self::assertStringContainsString("self::typeClassSql(\$db, 'e.type') . ' = ' . self::typeClassSql(\$db, 's.type')", $source);
        self::assertMatchesRegularExpression("/SCALAR_TYPES = \\[[^\\]]*'Hidden Input'/", $source);
        self::assertDoesNotMatchRegularExpression("/SCALAR_TYPES = \\[[^\\]]*Checkbox/", $source);
    }

    public function testMenuItemDeletionIsGuardedAndNotNestedInAForm(): void
    {
        $controller = (string) file_get_contents(self::ADMIN . '/src/Controller/AboutController.php');
        $method = substr($controller, (int) strpos($controller, 'function deleteMenuItem'));
        $method = substr($method, 0, (int) strpos($method, 'private function menuError'));

        self::assertStringContainsString('AreaAccess::assertRepair', $method);
        self::assertStringContainsString("authorise('core.edit.state', 'com_menus')", $method);
        self::assertStringContainsString("['menu_issues']", $method);
        self::assertStringContainsString('publish($pks, -2)', $method);
        self::assertStringNotContainsString('->delete(', $method);

        // The About screen is one form: a nested form is dropped by the browser.
        $template = (string) file_get_contents(self::ADMIN . '/tmpl/about/default.php');
        $menuBlock = substr($template, (int) strpos($template, 'AUDIT_MENU_ISSUES\''));
        $menuBlock = substr($menuBlock, 0, (int) strpos($menuBlock, 'auditDuplicateForms !== array()'));

        self::assertStringContainsString("Joomla.submitform('about.deleteMenuItem')", $menuBlock);
        self::assertStringNotContainsString('<form', $menuBlock);
    }
}
