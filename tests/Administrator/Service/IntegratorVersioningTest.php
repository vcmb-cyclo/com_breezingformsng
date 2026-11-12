<?php

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator\Service;

use PHPUnit\Framework\TestCase;

final class IntegratorVersioningTest extends TestCase
{
    public function testRecordedRuleVersionIsMarkedCurrent(): void
    {
        $servicePath = __DIR__ . '/../../../administrator/components/com_breezingformsng/src/Service/'
            . 'IntegratorVersioning.php';
        $service = (string) file_get_contents($servicePath);

        self::assertStringContainsString("->set(\$this->db->quoteName('is_current') . ' = 0')", $service);
        self::assertStringContainsString("->set(\$this->db->quoteName('is_current') . ' = 1')", $service);
        self::assertStringContainsString(
            "->select('MAX(' . \$this->db->quoteName('version_id') . ')')",
            $service
        );
        self::assertStringContainsString('$this->db->transactionStart(true);', $service);
        self::assertStringContainsString('$this->db->transactionCommit(true);', $service);
        self::assertStringContainsString('$this->db->transactionRollback(true);', $service);
    }

    public function testIntegratorHistoryResolvesToAValidJoomlaTable(): void
    {
        $tablePath = __DIR__ . '/../../../components/com_breezingformsng/src/Table/IntegratorTable.php';
        $table = (string) file_get_contents($tablePath);

        self::assertStringContainsString('final class IntegratorTable extends Table', $table);
        self::assertStringContainsString(
            'parent::__construct(\'#__facileforms_integrator_rules\', \'id\', $db);',
            $table
        );
    }
}
