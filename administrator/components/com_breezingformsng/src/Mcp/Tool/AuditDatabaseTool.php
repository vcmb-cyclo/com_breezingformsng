<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Vcmb\Component\BreezingformsNG\Administrator\Mcp\Tool;

\defined('_JEXEC') or die;

use Joomla\CMS\User\User;
use Vcmb\Component\BreezingformsNG\Administrator\Mcp\McpTool;
use Vcmb\Component\BreezingformsNG\Administrator\Service\DatabaseAuditService;

/**
 * Runs the database audit of the About screen and returns its report. The
 * audit only reads: its repairs stay on the About screen.
 */
final class AuditDatabaseTool implements McpTool
{
    /** Items kept per list of the report: the counts in summary stay complete. */
    public const MAX_ITEMS = 50;

    /** Lists of the report, in the order the About screen shows them. */
    private const LISTS = [
        'errors', 'missing_tables', 'missing_columns', 'unexpected_tables', 'collation_issues', 'column_collation_issues',
        'duplicate_indexes', 'orphan_checks', 'duplicate_forms', 'debug_forms', 'trashed_records', 'menu_issues',
        'extension_duplicates', 'extension_legacy', 'stale_language_files', 'stale_installer_temp_dirs',
    ];

    /**
     * @param \Closure(): DatabaseAuditService $audit Builds the audit when the tool runs.
     */
    public function __construct(private readonly \Closure $audit)
    {
    }

    public function name(): string
    {
        return 'bfng_audit_database';
    }

    public function title(): string
    {
        return 'Audit the BreezingForms NG database';
    }

    public function description(): string
    {
        return 'Runs the database audit of the BreezingForms NG About screen and changes nothing. '
            . 'It reports missing tables and columns, unexpected tables, table and column collation mismatches, '
            . 'duplicate indexes, orphan rows (records, elements or subrecords whose parent was deleted), duplicate forms, '
            . 'forms left in debug mode, trashed records, broken component menu items, duplicate or legacy extensions, '
            . 'and stale language files or installer folders. '
            . 'summary holds the complete counts; each list keeps its first ' . self::MAX_ITEMS . ' items, '
            . 'and truncated names the lists that were cut. Repairs are made by the user on the About screen.';
    }

    public function inputSchema(): array
    {
        // No argument: without properties, a JSON object schema that allows none.
        return ['type' => 'object', 'additionalProperties' => false];
    }

    public function readOnly(): bool
    {
        return true;
    }

    public function allows(User $user): bool
    {
        // The audit of the About screen: the same right as running it there.
        return $user->authorise('core.manage', 'com_breezingformsng');
    }

    public function call(array $arguments, User $user): array
    {
        $report = ($this->audit)()->run();
        $result = [
            'generated_at' => (string) ($report['generated_at'] ?? ''),
            'target_collation' => (string) ($report['target_collation'] ?? ''),
            'summary' => (array) ($report['summary'] ?? []),
            'truncated' => [],
        ];

        foreach (self::LISTS as $list) {
            $items = array_values((array) ($report[$list] ?? []));
            $result[$list] = \array_slice($items, 0, self::MAX_ITEMS);
            if (\count($items) > self::MAX_ITEMS) {
                $result['truncated'][] = $list;
            }
        }

        return $result;
    }
}
