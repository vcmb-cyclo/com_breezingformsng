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

    public function testAutomaticReassignmentRequiresTheSameType(): void
    {
        $source = (string) file_get_contents(self::ADMIN . '/src/Service/OrphanValueMatcher.php');

        self::assertStringContainsString('e.name = s.name AND e.type = s.type', $source);
    }
}
