<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Vcmb\Component\BreezingformsNG\Administrator\Mcp\Tool;

\defined('_JEXEC') or die;

use Joomla\CMS\User\User;
use Vcmb\Component\BreezingformsNG\Administrator\Mcp\AuditReportFilter;
use Vcmb\Component\BreezingformsNG\Administrator\Mcp\McpTool;

/**
 * Runs the database audit of the About screen and returns its report, filtered
 * for an AI client (see AuditReportFilter). The audit only reads: its repairs
 * stay on the About screen.
 */
final class AuditDatabaseTool implements McpTool
{
    /**
     * @param \Closure(): array<string, mixed> $report Runs the audit when the tool is called.
     * @param string                           $root   The root folder of the site.
     */
    public function __construct(private readonly \Closure $report, private readonly string $root)
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
            . 'The report holds identifiers and codes only: the values of the parameters of menu links are masked, '
            . 'paths are relative to the site, and the detail of an audit failure is left out. '
            . 'summary holds the complete counts; every list, nested ones included, keeps its first ' . AuditReportFilter::MAX_ITEMS
            . ' items, truncated names the cut lists by their path (duplicate_forms[].drop), '
            . 'and a cut nested list gets a sibling <name>_total. Repairs are made by the user on the About screen.';
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
        return AuditReportFilter::filter(($this->report)(), $this->root);
    }
}
