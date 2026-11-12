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

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator\Mcp;

use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Administrator\Mcp\AuditReportFilter;
use Vcmb\Component\BreezingformsNG\Administrator\Mcp\Tool\AuditDatabaseTool;

require_once __DIR__ . '/McpTestDoubles.php';

final class AuditReportFilterTest extends TestCase
{
    private const ROOT = '/var/www/secret-host/public_html';

    /**
     * @param array<string, mixed> $lists
     * @return array<string, mixed>
     */
    private static function filter(array $lists): array
    {
        return AuditReportFilter::filter(['generated_at' => '2026-11-12 10:00:00', 'summary' => ['issues_total' => 3]] + $lists, self::ROOT);
    }

    public function testTheValuesOfTheParametersOfAMenuLinkAreMasked(): void
    {
        $report = self::filter(['menu_issues' => [[
            'menu_id' => 7,
            'title' => 'Contact',
            'link' => 'index.php?option=com_breezingformsng&view=form&ff_com_name=Contact&token=FAKE-SECRET-123&api_key[]=FAKE-KEY-456#frag',
            'form_name' => 'Contact',
            'issues' => ['form_missing'],
        ]]]);
        $item = $report['menu_issues'][0];

        self::assertSame('index.php?option=com_breezingformsng&view=form&ff_com_name=***&token=***&api_key=***', $item['link']);
        self::assertSame(7, $item['menu_id']);
        self::assertSame(['form_missing'], $item['issues']);
        self::assertStringNotContainsString('FAKE-SECRET-123', (string) json_encode($report));
        self::assertStringNotContainsString('FAKE-KEY-456', (string) json_encode($report));
    }

    public function testALinkToAnotherSiteKeepsNeitherItsHostNorItsCredentials(): void
    {
        $report = self::filter(['menu_issues' => [['menu_id' => 1, 'link' => 'https://user:FAKE-PASSWORD@example.test/index.php?option=com_x&secret=FAKE-SECRET-123']]]);

        self::assertSame('[external]?option=com_x&secret=***', $report['menu_issues'][0]['link']);
        self::assertStringNotContainsString('FAKE-PASSWORD', (string) json_encode($report));
    }

    public function testAbsolutePathsBecomeRelative(): void
    {
        $report = self::filter([
            'stale_installer_temp_dirs' => [self::ROOT . '/tmp/install_6a1b2c', '/var/tmp/other-place/install_9f8e7d', 'C:\\inetpub\\tmp\\install_55aa'],
            'stale_language_files' => [self::ROOT . '/language/fr-FR/com_breezingforms.ini', '/language/de-DE/com_breezingforms.sys.ini'],
        ]);

        self::assertSame(['install_6a1b2c', 'install_9f8e7d', 'install_55aa'], $report['stale_installer_temp_dirs']);
        self::assertSame(['/language/fr-FR/com_breezingforms.ini', '/language/de-DE/com_breezingforms.sys.ini'], $report['stale_language_files']);
        self::assertStringNotContainsString('secret-host', (string) json_encode($report));
        self::assertStringNotContainsString('inetpub', (string) json_encode($report));
    }

    public function testTheDetailOfAnAuditFailureIsLeftOut(): void
    {
        $report = self::filter(['errors' => [
            'Unable to inspect trashed records: SQLSTATE[HY000] [1045] Access denied for user \'bfng\'@\'10.0.0.5\' (using password: YES) in ' . self::ROOT . '/libraries/vendor/joomla/database/src/Mysqli/MysqliDriver.php',
        ]]);

        self::assertSame(['Unable to inspect trashed records'], $report['errors']);
        self::assertStringNotContainsString('10.0.0.5', (string) json_encode($report));
    }

    public function testAnyOtherTextLosesThePathOfTheSite(): void
    {
        $report = self::filter(['extension_legacy' => [['extension_id' => 4, 'name' => 'Old ' . self::ROOT . '/plugins/x', 'type' => 'plugin']]]);

        self::assertSame('Old /plugins/x', $report['extension_legacy'][0]['name']);
        self::assertSame(4, $report['extension_legacy'][0]['extension_id']);
    }

    public function testARepairTokenIsNeverReturned(): void
    {
        $report = self::filter(['column_collation_issues' => [['table' => '#__t', 'column' => 'c', 'collation' => 'a', 'token' => 'FAKE-REPAIR-TOKEN']]]);

        self::assertSame(['table' => '#__t', 'column' => 'c', 'collation' => 'a'], $report['column_collation_issues'][0]);
    }

    public function testARootListIsCutAndNamed(): void
    {
        $report = self::filter(['missing_tables' => array_map(static fn (int $i): string => 'table' . $i, range(1, 80))]);

        self::assertCount(AuditReportFilter::MAX_ITEMS, $report['missing_tables']);
        self::assertSame(['missing_tables'], $report['truncated']);
        // The complete count stays in the summary of the report.
        self::assertSame(['issues_total' => 3], $report['summary']);
    }

    public function testAListInsideTheItemsOfAListIsCutToo(): void
    {
        $drop = array_map(static fn (int $i): array => ['id' => $i, 'name' => 'form'], range(1, 3000));
        $report = self::filter([
            'duplicate_forms' => [['name' => 'contact', 'package' => '', 'keep' => ['id' => 1], 'drop' => $drop]],
            'extension_duplicates' => [['keep' => ['extension_id' => 1], 'drop' => array_slice($drop, 0, 10)]],
        ]);
        $item = $report['duplicate_forms'][0];

        self::assertCount(AuditReportFilter::MAX_ITEMS, $item['drop']);
        self::assertSame(3000, $item['drop_total']);
        self::assertSame(['duplicate_forms[].drop'], $report['truncated']);
        // A list within the limit is left whole, with no total.
        self::assertCount(10, $report['extension_duplicates'][0]['drop']);
        self::assertArrayNotHasKey('drop_total', $report['extension_duplicates'][0]);
        self::assertLessThan(20000, strlen((string) json_encode($report)));
    }

    public function testTheListsOfTheGroupsOfAnOrphanCheckAreCutToo(): void
    {
        $groups = array_map(static fn (int $i): array => ['parent_id' => $i, 'count' => 1, 'label' => 'form' . $i], range(1, 120));
        $report = self::filter(['orphan_checks' => [['id' => 'records_without_form', 'count' => 120, 'groups' => $groups]]]);

        self::assertCount(AuditReportFilter::MAX_ITEMS, $report['orphan_checks'][0]['groups']);
        self::assertSame(120, $report['orphan_checks'][0]['groups_total']);
        self::assertSame(['orphan_checks[].groups'], $report['truncated']);
    }

    public function testTheToolReturnsTheFilteredReport(): void
    {
        $tool = new AuditDatabaseTool(
            static fn (): array => ['summary' => ['issues_total' => 1], 'menu_issues' => [['menu_id' => 2, 'link' => 'index.php?option=com_x&secret=FAKE-SECRET-123']]],
            self::ROOT
        );

        $result = $tool->call([], McpTestDoubles::user(['core.manage']));

        self::assertSame('index.php?option=com_x&secret=***', $result['menu_issues'][0]['link']);
        self::assertStringNotContainsString('FAKE-SECRET-123', (string) json_encode($result));
    }
}
