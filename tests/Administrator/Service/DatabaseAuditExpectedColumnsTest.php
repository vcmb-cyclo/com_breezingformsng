<?php

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator\Service;

use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Administrator\Service\DatabaseAuditService;

final class DatabaseAuditExpectedColumnsTest extends TestCase
{
    public function testInstallSchemaYieldsColumnsForEveryAuditedTable(): void
    {
        $root = __DIR__ . '/../../../administrator/components/com_breezingformsng';
        $sql = file_get_contents($root . '/sql/install.mysql.utf8.sql');
        self::assertIsString($sql);

        $expected = DatabaseAuditService::parseExpectedColumns($sql);

        self::assertCount(14, $expected);
        self::assertSame(['id', 'value'], $expected['facileforms_config']);
        self::assertContains('debug_mode', $expected['facileforms_forms']);
        self::assertContains('modified', $expected['facileforms_forms']);
        self::assertContains('modified_user_id', $expected['facileforms_records']);
        self::assertContains('unit_tests', $expected['facileforms_scripts']);
        self::assertContains('unit_tests', $expected['facileforms_pieces']);

        foreach ($expected as $table => $columns) {
            self::assertNotSame([], $columns, $table);
            self::assertNotContains('PRIMARY', $columns, $table);
            self::assertNotContains('KEY', $columns, $table);
        }
    }

    public function testIndexDefinitionsAreNotReportedAsColumns(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS `#__demo` (\n"
            . "  `id` int(11) NOT NULL,\n"
            . "  `name` varchar(30) NOT NULL DEFAULT '',\n"
            . "  PRIMARY KEY (`id`),\n"
            . "  KEY `name` (`name`)\n"
            . ") ENGINE=InnoDB;";

        self::assertSame(['demo' => ['id', 'name']], DatabaseAuditService::parseExpectedColumns($sql));
    }
}
