<?php

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AuditAccordionArchitectureTest extends TestCase
{
    private const ROOT = __DIR__ . '/../../administrator/components/com_breezingformsng/';

    /** @return array<string, array{string, string}> */
    public static function editors(): array
    {
        return [
            'script' => ['tmpl/script/edit.php', 'bf-script-audit'],
            'piece' => ['tmpl/piece/edit.php', 'bf-piece-audit'],
            'integrator' => ['tmpl/integrator/edit.php', 'bf-integrator-audit'],
            'quickmode' => ['tmpl/quickmode/default.php', 'bf-form-audit'],
        ];
    }

    #[DataProvider('editors')]
    public function testEditorRendersTheSharedAuditAccordion(string $template, string $id): void
    {
        $source = (string) file_get_contents(self::ROOT . $template);

        self::assertStringContainsString("LayoutHelper::render('audit.accordion'", $source);
        self::assertStringContainsString("'id' => '" . $id . "'", $source);
    }

    public function testQuickmodeAccordionFollowsTheDebugMode(): void
    {
        $template = (string) file_get_contents(self::ROOT . 'tmpl/quickmode/default.php');
        $script = (string) file_get_contents(
            __DIR__ . '/../../media/com_breezingformsng/js/admin/admin-toggle-published.js'
        );

        self::assertStringContainsString("'hidden' => !\$debugMode", $template);
        self::assertStringContainsString("getElementById('bf-form-audit')", $script);
    }

    public function testIntegratorRulesHaveAuditColumnsInInstallAndUpdate(): void
    {
        $sql = (string) file_get_contents(self::ROOT . 'sql/install.mysql.utf8.sql');
        preg_match('/integrator_rules` \((.*?)\) ENGINE/s', $sql, $table);
        $script = (string) file_get_contents(__DIR__ . '/../../script.php');

        foreach (['created', 'created_by', 'modified', 'modified_by'] as $column) {
            self::assertStringContainsString('`' . $column . '`', $table[1]);
            self::assertStringContainsString("'" . $column . "'", $script);
        }
        self::assertStringContainsString("'com_breezingformsng.integrator'", $script);
    }

    public function testIntegratorChangesAreVersionedAndStamped(): void
    {
        $model = (string) file_get_contents(self::ROOT . 'src/Model/IntegratorModel.php');
        $controller = (string) file_get_contents(self::ROOT . 'src/Controller/IntegratorController.php');

        self::assertStringContainsString('->record($ruleId)', $model);
        self::assertStringContainsString('deleteHistory', $model);
        self::assertStringContainsString('function loadhistory', $controller);
        self::assertStringContainsString("checkToken('get')", $controller);
    }
}
