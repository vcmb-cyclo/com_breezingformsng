<?php

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator;

use PHPUnit\Framework\TestCase;

final class AboutColumnCollationRepairTest extends TestCase
{
    public function testColumnCollationIssuesExposeSingleAndBulkRepairControls(): void
    {
        $template = file_get_contents(__DIR__ . '/../../administrator/components/com_breezingformsng/tmpl/about/default.php');
        $controller = file_get_contents(__DIR__ . '/../../administrator/components/com_breezingformsng/src/Controller/AboutController.php');
        $repairService = file_get_contents(__DIR__ . '/../../administrator/components/com_breezingformsng/src/Service/DatabaseRepairService.php');

        self::assertIsString($template);
        self::assertIsString($controller);
        self::assertIsString($repairService);
        self::assertStringContainsString('data-bf-select-all="column-collations"', $template);
        self::assertStringContainsString('name="column_collation_issues[]"', $template);
        self::assertStringContainsString("about.repairColumnCollations", $template);
        self::assertStringContainsString('function repairColumnCollations(): void', $controller);
        self::assertStringContainsString('repairColumnCollations($selectedTokens)', $controller);
        self::assertStringContainsString('function getColumnCollationSelectionToken(array $issue): string', $repairService);
        self::assertStringContainsString('SHOW FULL COLUMNS FROM', $repairService);
        self::assertStringContainsString('MODIFY COLUMN', $repairService);
    }
}
