<?php

/**
 * BreezingForms NG - A Joomla Forms Application
 *
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 *
 * SPDX-License-Identifier: GPL-2.0-or-later
 **/

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator;

use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Administrator\Service\RecordInventoryService;

final class RecordInventoryServiceTest extends TestCase
{
    public function testRowsAreCastAndAFormWithoutNameIsKept(): void
    {
        $rows = RecordInventoryService::normalize([
            ['form' => '3', 'name' => ' Contact ', 'published' => '5', 'archived' => '1', 'trashed' => '2', 'total' => '8'],
            ['form' => '9', 'name' => null, 'published' => '1', 'archived' => '0', 'trashed' => '0', 'total' => '1'],
        ]);

        self::assertSame(
            ['form' => 3, 'name' => 'Contact', 'published' => 5, 'archived' => 1, 'trashed' => 2, 'total' => 8],
            $rows[0]
        );
        self::assertSame(9, $rows[1]['form']);
        self::assertSame('', $rows[1]['name']);
        self::assertSame([], RecordInventoryService::normalize([]));
    }

    public function testStatisticsScreenOpensTheFormAndLinksItsRecordCounts(): void
    {
        $template = (string) file_get_contents(
            __DIR__ . '/../../administrator/components/com_breezingformsng/tmpl/about/statistics.php'
        );

        self::assertStringContainsString('bf-records-inventory', $template);
        self::assertStringContainsString("\$auditHelp('COM_BREEZINGFORMSNG_TOOLS_RECORDS_INVENTORY_HELP')", $template);
        self::assertStringContainsString('task=quickmode.display&form=', $template);
        self::assertStringContainsString('view=records&filter[form]=', $template);
        self::assertStringContainsString("'-2'", $template);
        self::assertSame(2, substr_count($template, 'target="_blank" rel="noopener"'));
    }

    public function testTableInventoryFlagsColumnsWithADifferentCollation(): void
    {
        $template = (string) file_get_contents(
            __DIR__ . '/../../administrator/components/com_breezingformsng/tmpl/about/statistics.php'
        );

        self::assertStringContainsString("['column_collation_issues']", $template);
        self::assertStringContainsString('COM_BREEZINGFORMSNG_TOOLS_STATISTICS_MISMATCH', $template);
        self::assertStringContainsString('task=about.runAudit', $template);
        self::assertStringContainsString("HTMLHelper::_('form.token')", $template);
    }
}
