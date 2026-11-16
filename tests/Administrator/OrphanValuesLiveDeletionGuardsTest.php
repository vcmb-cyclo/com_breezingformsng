<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator;

use PHPUnit\Framework\TestCase;

final class OrphanValuesLiveDeletionGuardsTest extends TestCase
{
    private const ADMIN = __DIR__ . '/../../administrator/components/com_breezingformsng';

    public function testControllerRefusesWithoutAcknowledgement(): void
    {
        $source = (string) file_get_contents(self::ADMIN . '/src/Controller/AboutController.php');
        $method = substr($source, (int) strpos($source, 'function deleteOrphanValuesWithRecords'));
        $method = substr($method, 0, (int) strpos($method, 'private function repairOrphans'));

        self::assertStringContainsString('orphan_acknowledged', $method);
        self::assertStringContainsString('if (!$acknowledged)', $method);
        self::assertStringContainsString('InvalidArgumentException', $method);
    }

    public function testServiceDeletesOneMissingElementOnly(): void
    {
        $source = (string) file_get_contents(self::ADMIN . '/src/Service/DatabaseRepairService.php');
        $method = substr($source, (int) strpos($source, 'function deleteOrphanValuesOfRecords'));
        $method = substr($method, 0, (int) strpos($method, 'public function reassignOrphanValues('));

        self::assertStringContainsString('$elementId < 1', $method);
        self::assertStringContainsString("'element') . ' = :elementId'", $method);
        self::assertStringContainsString('NOT EXISTS (', $method);
    }

    public function testButtonNeedsTheCheckbox(): void
    {
        $js = (string) file_get_contents(__DIR__ . '/../../media/com_breezingformsng/js/admin/about.js');

        self::assertStringContainsString('bfOrphanAck', $js);
        self::assertStringContainsString('acknowledge.checked', $js);
    }
}
