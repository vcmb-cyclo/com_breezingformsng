<?php

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator;

use PHPUnit\Framework\TestCase;

final class AboutAuditPresentationTest extends TestCase
{
    public function testAuditIsTheOnlyGlobalMaintenanceEntryPoint(): void
    {
        $controller = file_get_contents(__DIR__ . '/../../administrator/components/com_breezingformsng/src/Controller/AboutController.php');
        $view = file_get_contents(__DIR__ . '/../../administrator/components/com_breezingformsng/src/View/About/HtmlView.php');
        $template = file_get_contents(__DIR__ . '/../../administrator/components/com_breezingformsng/tmpl/about/default.php');
        $auditService = file_get_contents(__DIR__ . '/../../administrator/components/com_breezingformsng/src/Service/DatabaseAuditService.php');
        $repairService = file_get_contents(__DIR__ . '/../../administrator/components/com_breezingformsng/src/Service/DatabaseRepairService.php');

        self::assertIsString($controller);
        self::assertIsString($view);
        self::assertIsString($template);
        self::assertIsString($auditService);
        self::assertIsString($repairService);
        self::assertStringContainsString("->task('about.runAudit')", $view);
        self::assertStringNotContainsString('about.startRepairWorkflow', $view);
        self::assertStringNotContainsString('function startRepairWorkflow()', $controller);
        self::assertStringNotContainsString('function migratePackedData()', $controller);
        self::assertStringContainsString('COM_BREEZINGFORMSNG_ABOUT_AUDIT_EMPTY', $template);
        self::assertStringContainsString('bf-audit-summary-table', $template);
        self::assertStringContainsString('bf-audit-detail-sections', $template);
        self::assertStringContainsString('about.repairTableCollations', $template);
        self::assertStringContainsString('function repairTableCollations(): void', $controller);
        self::assertStringContainsString('function repairTableCollations(array $selectedTokens): array', $repairService);
        self::assertStringContainsString("'facileforms_config'", $auditService);
        self::assertStringContainsString("'facileforms_records'", $auditService);
        self::assertStringContainsString("'facileforms_subrecords'", $auditService);
    }
}
