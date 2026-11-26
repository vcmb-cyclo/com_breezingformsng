<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Vcmb\Component\BreezingformsNG\Administrator\Service;

\defined('_JEXEC') or die;

use Joomla\CMS\Date\Date;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

final class DatabaseAuditService
{
    private const TARGET_CHARSET = 'utf8mb4';

    private const EXPECTED_TABLES = [
        'facileforms_config',
        'facileforms_packages',
        'facileforms_compmenus',
        'facileforms_forms',
        'facileforms_elements',
        'facileforms_scripts',
        'facileforms_pieces',
        'facileforms_records',
        'facileforms_transfer_operations',
        'facileforms_subrecords',
        'facileforms_integrator_criteria_fixed',
        'facileforms_integrator_criteria_form',
        'facileforms_integrator_criteria_joomla',
        'facileforms_integrator_items',
        'facileforms_integrator_rules',
    ];

    private const FALLBACK_COLLATION = 'utf8mb4_unicode_ci';

    /** Missing parents listed per orphan relation; the rest is only counted. */

    private const INSTALL_SQL = '/components/com_breezingformsng/sql/install.mysql.utf8.sql';

    private ?string $resolvedTargetCollation = null;

    /** @var array<int, string> */
    private array $errors = [];

    public function __construct(
        private readonly DatabaseInterface $db,
        private readonly string $temporaryPath
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function run(): array
    {
        $this->errors = [];
        $targetCollation = $this->resolveTargetCollation();
        $tableList = $this->db->getTableList();
        $tables = [];
        $missingTables = [];
        $missingColumns = [];
        $expectedColumns = $this->getExpectedColumns();
        $collationIssues = [];
        $columnCollationIssues = [];
        $duplicateIndexes = [];
        $totalRows = 0;
        $totalDataBytes = 0;
        $totalIndexBytes = 0;

        foreach (self::EXPECTED_TABLES as $table) {
            $physicalTable = $this->db->getPrefix() . $table;
            $alias = '#__' . $table;

            if (!in_array($physicalTable, $tableList, true)) {
                $missingTables[] = $alias;
                continue;
            }

            try {
                $status = $this->getTableStatus($physicalTable);
            } catch (\Throwable $exception) {
                $this->addError('Unable to inspect table ' . $alias . ': ' . $exception->getMessage());
                continue;
            }
            $rows = self::intValue($status['TABLE_ROWS'] ?? 0);
            $collation = self::stringValue($status['TABLE_COLLATION'] ?? '');
            $dataBytes = self::intValue($status['DATA_LENGTH'] ?? 0);
            $indexBytes = self::intValue($status['INDEX_LENGTH'] ?? 0);
            $totalRows += $rows;
            $totalDataBytes += $dataBytes;
            $totalIndexBytes += $indexBytes;
            $tables[] = [
                'table' => $alias,
                'rows' => $rows,
                'engine' => self::stringValue($status['ENGINE'] ?? ''),
                'collation' => $collation,
                'size_bytes' => $dataBytes + $indexBytes,
            ];

            if (strcasecmp($collation, $targetCollation) !== 0) {
                $collationIssues[] = [
                    'table' => $alias,
                    'collation' => $collation,
                    'expected' => $targetCollation,
                ];
            }

            $missing = $this->findMissingColumns($physicalTable, $alias, $expectedColumns[$table] ?? []);
            if ($missing !== []) {
                $missingColumns[] = ['table' => $alias, 'columns' => $missing];
            }

            foreach ($this->findColumnCollationIssues($physicalTable, $alias, $targetCollation) as $columnIssue) {
                $columnCollationIssues[] = $columnIssue;
            }

            foreach ($this->findDuplicateIndexes($physicalTable, $alias) as $duplicate) {
                $duplicateIndexes[] = $duplicate;
            }
        }

        $collationHistogram = $this->buildCollationHistogram($tables);
        $orphanChecks = $this->findOrphans($tableList);
        $orphanRows = array_sum(array_column($orphanChecks, 'count'));
        $unexpectedTables = $this->findUnexpectedTables($tableList);
        $staleLanguageFiles = $this->findStaleLanguageFiles();
        $staleInstallerTempDirs = $this->findStaleInstallerTempDirectories();
        $menuIssues = $this->findMenuIssues();
        $extensionIssues = $this->findExtensionIssues();
        $duplicateForms = $this->findDuplicateForms();
        $debugForms = $this->findDebugModeForms($tableList);
        $trashedRecords = $this->findTrashedRecords($tableList);
        $trashedItems = $this->findTrashedItems();
        $missingColumnsTotal = array_sum(array_map(
            static fn (array $entry): int => count($entry['columns']),
            $missingColumns
        ));
        $issuesTotal = count($missingTables) + $missingColumnsTotal + count($debugForms)
            + count($collationIssues) + count($columnCollationIssues)
            + count($duplicateIndexes) + $orphanRows + count($unexpectedTables)
            + count($staleLanguageFiles) + count($staleInstallerTempDirs)
            + count($menuIssues) + count($extensionIssues['duplicates']) + count($extensionIssues['legacy'])
            + count($duplicateForms);

        return [
            'generated_at' => (new Date())->toSql(),
            'target_charset' => self::TARGET_CHARSET,
            'target_collation' => $targetCollation,
            'tables' => $tables,
            'missing_tables' => $missingTables,
            'missing_columns' => $missingColumns,
            'debug_forms' => $debugForms,
            'trashed_records' => $trashedRecords,
            'trashed_items' => $trashedItems,
            'unexpected_tables' => $unexpectedTables,
            'collation_issues' => $collationIssues,
            'column_collation_issues' => $columnCollationIssues,
            'collation_histogram' => $collationHistogram,
            'duplicate_indexes' => $duplicateIndexes,
            'orphan_checks' => $orphanChecks,
            'stale_language_files' => $staleLanguageFiles,
            'stale_installer_temp_dirs' => $staleInstallerTempDirs,
            'menu_issues' => $menuIssues,
            'extension_duplicates' => $extensionIssues['duplicates'],
            'extension_legacy' => $extensionIssues['legacy'],
            'duplicate_forms' => $duplicateForms,
            'errors' => array_values(array_unique($this->errors)),
            'summary' => [
                'expected_tables' => count(self::EXPECTED_TABLES),
                'scanned_tables' => count($tables),
                'missing_tables' => count($missingTables),
                'missing_columns' => $missingColumnsTotal,
                'debug_forms' => count($debugForms),
                'trashed_records' => array_sum(array_column($trashedRecords, 'count')),
                'trashed_items' => array_sum(array_column($trashedItems, 'count')),
                'unexpected_tables' => count($unexpectedTables),
                'total_rows' => $totalRows,
                'total_data_bytes' => $totalDataBytes,
                'total_index_bytes' => $totalIndexBytes,
                'collation_issues' => count($collationIssues),
                'column_collation_issues' => count($columnCollationIssues),
                'mixed_collations' => count($collationHistogram) > 1,
                'duplicate_index_groups' => count($duplicateIndexes),
                'orphan_rows' => $orphanRows,
                'stale_language_files' => count($staleLanguageFiles),
                'stale_installer_temp_dirs' => count($staleInstallerTempDirs),
                'menu_issues' => count($menuIssues),
                'extension_duplicates' => count($extensionIssues['duplicates']),
                'extension_legacy' => count($extensionIssues['legacy']),
                'duplicate_forms' => count($duplicateForms),
                'audit_errors' => count(array_unique($this->errors)),
                'issues_total' => $issuesTotal,
            ],
        ];
    }

    /**
     * Prefer the server's modern utf8mb4 collation (e.g. utf8mb4_0900_ai_ci on
     * MySQL 8) over a hardcoded one, so tables that are already correctly
     * configured for this server aren't perpetually flagged as mismatched.
     */
    private function resolveTargetCollation(): string
    {
        if ($this->resolvedTargetCollation !== null) {
            return $this->resolvedTargetCollation;
        }

        $preferred = ['utf8mb4_0900_ai_ci', 'utf8mb4_unicode_520_ci', self::FALLBACK_COLLATION];

        foreach ($preferred as $candidate) {
            if ($this->isCollationSupported($candidate)) {
                return $this->resolvedTargetCollation = $candidate;
            }
        }

        return $this->resolvedTargetCollation = self::FALLBACK_COLLATION;
    }

    private function isCollationSupported(string $collation): bool
    {
        try {
            $query = $this->db->createQuery()
                ->select('COLLATION_NAME')
                ->from($this->db->quoteName('information_schema.COLLATIONS'))
                ->where($this->db->quoteName('COLLATION_NAME') . ' = :collation')
                ->bind(':collation', $collation);
            $this->db->setQuery($query);

            return (bool) $this->db->loadResult();
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Catches columns that stayed on a stale collation even after the table's
     * own default was fixed (ALTER TABLE ... CONVERT TO CHARACTER SET only
     * changes new/altered columns going forward on some MySQL versions when
     * a column had an explicit COLLATE override).
     *
     * @return list<array<string, string>>
     */
    private function findColumnCollationIssues(string $physicalTable, string $alias, string $targetCollation): array
    {
        try {
            $query = $this->db->createQuery()
                ->select(['COLUMN_NAME', 'CHARACTER_SET_NAME', 'COLLATION_NAME'])
                ->from($this->db->quoteName('information_schema.COLUMNS'))
                ->where('TABLE_SCHEMA = DATABASE()')
                ->where($this->db->quoteName('TABLE_NAME') . ' = :table')
                ->where($this->db->quoteName('COLLATION_NAME') . ' IS NOT NULL')
                ->bind(':table', $physicalTable);
            $this->db->setQuery($query);
            $columns = $this->loadAssocRows();
        } catch (\Throwable $exception) {
            $this->addError('Unable to inspect column encoding for ' . $alias . ': ' . $exception->getMessage());

            return [];
        }

        $issues = [];
        foreach ($columns as $column) {
            $charset = self::stringValue($column['CHARACTER_SET_NAME'] ?? '');
            $collation = self::stringValue($column['COLLATION_NAME'] ?? '');
            if (
                ($charset !== '' && strcasecmp($charset, self::TARGET_CHARSET) !== 0)
                || ($collation !== '' && strcasecmp($collation, $targetCollation) !== 0)
            ) {
                $issues[] = [
                    'table' => $alias,
                    'column' => self::stringValue($column['COLUMN_NAME'] ?? ''),
                    'charset' => $charset,
                    'collation' => $collation,
                    'expected_charset' => self::TARGET_CHARSET,
                    'expected' => $targetCollation,
                ];
            }
        }

        return $issues;
    }

    /**
     * @param list<array<string, mixed>> $tables
     * @return array<string, int>
     */
    private function buildCollationHistogram(array $tables): array
    {
        $histogram = [];
        foreach ($tables as $table) {
            $collation = self::stringValue($table['collation'] ?? '');
            if ($collation === '') {
                continue;
            }
            $histogram[$collation] = ($histogram[$collation] ?? 0) + 1;
        }
        arsort($histogram);

        return $histogram;
    }

    /**
     * @return array<mixed>
     */
    private function getTableStatus(string $physicalTable): array
    {
        $query = $this->db->createQuery()
            ->select(['TABLE_ROWS', 'ENGINE', 'TABLE_COLLATION', 'DATA_LENGTH', 'INDEX_LENGTH'])
            ->from($this->db->quoteName('information_schema.TABLES'))
            ->where('TABLE_SCHEMA = DATABASE()')
            ->where($this->db->quoteName('TABLE_NAME') . ' = :table')
            ->bind(':table', $physicalTable);
        $this->db->setQuery($query);

        return $this->loadAssocRow();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function findDuplicateIndexes(string $physicalTable, string $alias): array
    {
        try {
            $this->db->setQuery('SHOW INDEX FROM ' . $this->db->quoteName($physicalTable));
            $rows = $this->loadAssocRows();
        } catch (\Throwable $exception) {
            $this->addError('Unable to inspect indexes for ' . $alias . ': ' . $exception->getMessage());

            return [];
        }
        $indexes = [];

        foreach ($rows as $row) {
            $name = self::stringValue($row['Key_name'] ?? '');
            if ($name === '' || strtoupper($name) === 'PRIMARY') {
                continue;
            }

            $sequence = self::intValue($row['Seq_in_index'] ?? 0);
            $indexes[$name]['meta'] = implode(':', [
                self::stringValue($row['Non_unique'] ?? '1'),
                strtoupper(self::stringValue($row['Index_type'] ?? 'BTREE')),
            ]);
            $indexes[$name][$sequence] = implode(':', [
                strtolower(self::stringValue($row['Column_name'] ?? '')),
                self::stringValue($row['Sub_part'] ?? ''),
                strtoupper(self::stringValue($row['Collation'] ?? 'A')),
            ]);
        }

        $bySignature = [];
        foreach ($indexes as $name => $definition) {
            $meta = self::stringValue($definition['meta'] ?? '1:BTREE');
            unset($definition['meta']);
            $columns = $definition;
            ksort($columns, SORT_NUMERIC);
            $bySignature[$meta . '|' . implode(',', $columns)][] = $name;
        }

        $duplicates = [];
        foreach ($bySignature as $names) {
            if (count($names) < 2) {
                continue;
            }

            sort($names, SORT_NATURAL | SORT_FLAG_CASE);
            $duplicates[] = [
                'table' => $alias,
                'indexes' => $names,
                'keep' => $names[0],
                'drop' => array_slice($names, 1),
            ];
        }

        return $duplicates;
    }

    /**
     * @param list<string> $tableList
     * @return list<array<string, mixed>>
     */
    private function findOrphans(array $tableList): array
    {
        // [child table, parent table, child foreign key, parent kind,
        //  child column holding a copy of the parent name, child date column]
        $checks = [
            'records_without_form' => ['facileforms_records', 'facileforms_forms', 'form', 'form', 'name', 'submitted'],
            'elements_without_form' => ['facileforms_elements', 'facileforms_forms', 'form', 'form', null, null],
            'subrecords_without_record' => [
                'facileforms_subrecords',
                'facileforms_records',
                'record',
                'record',
                null,
                null,
            ],
            'subrecords_without_element' => [
                'facileforms_subrecords',
                'facileforms_elements',
                'element',
                'element',
                'name',
                null,
            ],
        ];
        $result = [];

        foreach ($checks as $id => [$child, $parent, $foreignKey, $parentKind, $labelColumn, $dateColumn]) {
            if (
                !in_array($this->db->getPrefix() . $child, $tableList, true)
                || !in_array($this->db->getPrefix() . $parent, $tableList, true)
            ) {
                continue;
            }

            try {
                // The records of a deleted form that are already in the trash are being dealt with there.
                $notTrashed = $id === 'records_without_form'
                    ? $this->db->quoteName('child.state') . ' <> ' . RecordState::TRASHED
                    : null;
                $summary = $this->summarizeOrphans(
                    $child,
                    $parent,
                    $foreignKey,
                    $labelColumn,
                    $dateColumn,
                    $notTrashed
                );

                if ($id === 'subrecords_without_element') {
                    $summary['groups'] = $this->addFormOfOrphanValues($summary['groups']);
                    $summary = $this->addDeletableOrphanValues($summary);
                    $summary = $this->addOrphanValueRecordCounts($summary);
                    $summary = $this->addReassignableOrphanValues($summary);
                }

                $result[] = ['id' => $id, 'parent' => $parentKind] + $summary;
            } catch (\Throwable $exception) {
                $this->addError('Unable to inspect orphan references for ' . $id . ': ' . $exception->getMessage());
            }
        }

        return $this->labelOrphanForms($result);
    }

    /**
     * Counts the orphan rows of one relation and groups them by missing
     * parent, most represented first.
     *
     * @return array{count: int, parents: int, groups: list<array<string, mixed>>}
     */
    private function summarizeOrphans(
        string $child,
        string $parent,
        string $foreignKey,
        ?string $labelColumn,
        ?string $dateColumn,
        ?string $extraCondition = null
    ): array {
        $key = $this->db->quoteName('child.' . $foreignKey);
        $orphans = function () use ($key, $child, $parent, $extraCondition) {
            $query = $this->db->createQuery()
                ->from($this->db->quoteName('#__' . $child, 'child'))
                ->join(
                    'LEFT',
                    $this->db->quoteName('#__' . $parent, 'parent'),
                    $key . ' = ' . $this->db->quoteName('parent.id')
                )
                ->where($this->db->quoteName('parent.id') . ' IS NULL');

            return $extraCondition === null ? $query : $query->where($extraCondition);
        };

        $this->db->setQuery(
            $orphans()->select(['COUNT(*) AS ' . $this->db->quoteName('total'), 'COUNT(DISTINCT ' . $key . ') AS '
                . $this->db->quoteName('parents')])
        );
        $totals = $this->loadAssocRow();
        $count = self::intValue($totals['total'] ?? 0);

        if ($count === 0) {
            return ['count' => 0, 'parents' => 0, 'groups' => []];
        }

        $query = $orphans()
            ->select([
                $key . ' AS ' . $this->db->quoteName('parent_id'),
                'COUNT(*) AS ' . $this->db->quoteName('total'),
            ])
            ->group($key)
            ->order($this->db->quoteName('total') . ' DESC, ' . $key . ' ASC');

        if ($labelColumn !== null) {
            $query->select(
                'MAX(' . $this->db->quoteName('child.' . $labelColumn) . ') AS ' . $this->db->quoteName('label')
            );
        }

        if ($dateColumn !== null) {
            $date = $this->db->quoteName('child.' . $dateColumn);
            $query->select([
                'MIN(' . $date . ') AS ' . $this->db->quoteName('first'),
                'MAX(' . $date . ') AS ' . $this->db->quoteName('last'),
            ]);
        }

        $groups = [];

        $this->db->setQuery($query);
        foreach ($this->loadAssocRows() as $row) {
            $groups[] = [
                'parent_id' => self::intValue($row['parent_id'] ?? 0),
                'count' => self::intValue($row['total'] ?? 0),
                'label' => self::stringValue($row['label'] ?? ''),
                'first' => self::stringValue($row['first'] ?? ''),
                'last' => self::stringValue($row['last'] ?? ''),
            ];
        }

        return ['count' => $count, 'parents' => self::intValue($totals['parents'] ?? 0), 'groups' => $groups];
    }

    /**
     * The values of a missing element belong to records, which know their form:
     * adds the form (id and title) to each group of missing elements.
     *
     * @param list<array<string, mixed>> $groups
     *
     * @return list<array<string, mixed>>
     */
    private function addFormOfOrphanValues(array $groups): array
    {
        $ids = array_values(array_map(
            static fn (array $group): int => self::intValue($group['parent_id'] ?? 0),
            $groups
        ));

        if ($ids === []) {
            return $groups;
        }

        $db = $this->db;
        $element = $db->quoteName('child.element');
        $query = $db->createQuery()
            ->select([
                $element . ' AS ' . $db->quoteName('element_id'),
                $db->quoteName('records.form') . ' AS ' . $db->quoteName('form_id'),
                'COALESCE(MAX(' . $db->quoteName('forms.title') . '), MAX(' . $db->quoteName('records.title') . ')) AS '
                    . $db->quoteName('form_title'),
            ])
            ->from($db->quoteName('#__facileforms_subrecords', 'child'))
            ->join('INNER', $db->quoteName('#__facileforms_records', 'records') . ' ON records.id = child.record')
            ->join('LEFT', $db->quoteName('#__facileforms_forms', 'forms') . ' ON forms.id = records.form')
            ->whereIn($element, $ids)
            ->group([$element, $db->quoteName('records.form')])
            ->order($element . ' ASC, ' . $db->quoteName('records.form') . ' ASC');
        $forms = [];

        $db->setQuery($query);
        foreach ($this->loadAssocRows() as $row) {
            $id = self::intValue($row['element_id'] ?? 0);
            if (!isset($forms[$id])) {
                $row['forms'] = 0;
                $forms[$id] = $row;
            }
            $forms[$id]['forms']++;
        }

        foreach ($groups as &$group) {
            $row = $forms[self::intValue($group['parent_id'] ?? 0)] ?? null;

            if ($row !== null) {
                $group['form_id'] = self::intValue($row['form_id'] ?? 0);
                $group['form_title'] = self::stringValue($row['form_title'] ?? '');
                $group['forms'] = self::intValue($row['forms']);
            }
        }
        unset($group);

        return $groups;
    }

    /**
     * Count only values whose record and element are both missing.
     *
     * @param array{count: int, parents: int, groups: list<array<string, mixed>>} $summary
     * @return array{count: int, parents: int, groups: list<array<string, mixed>>, deletable: int}
     */
    private function addDeletableOrphanValues(array $summary): array
    {
        $db = $this->db;
        $orphans = fn () => $db->createQuery()
            ->from($db->quoteName('#__facileforms_subrecords', 'child'))
            ->join('LEFT', $db->quoteName('#__facileforms_elements', 'elements') . ' ON elements.id = child.element')
            ->join('LEFT', $db->quoteName('#__facileforms_records', 'records') . ' ON records.id = child.record')
            ->where('elements.id IS NULL')
            ->where('records.id IS NULL');

        $db->setQuery($orphans()->select('COUNT(*)'));
        $summary['deletable'] = self::intValue($db->loadResult());
        $ids = array_map(static fn (array $group): int => self::intValue($group['parent_id'] ?? 0), $summary['groups']);
        $counts = [];

        if ($ids !== []) {
            $query = $orphans()
                ->select([$db->quoteName('child.element', 'element_id'), 'COUNT(*) AS ' . $db->quoteName('total')])
                ->whereIn($db->quoteName('child.element'), $ids)
                ->group($db->quoteName('child.element'));

            $db->setQuery($query);
            foreach ($this->loadAssocRows() as $row) {
                $counts[self::intValue($row['element_id'] ?? 0)] = self::intValue($row['total'] ?? 0);
            }
        }

        foreach ($summary['groups'] as &$group) {
            $group['deletable'] = $counts[self::intValue($group['parent_id'] ?? 0)] ?? 0;
        }
        unset($group);

        return $summary;
    }

    /**
     * Counts, for each missing element, the distinct records holding its values
     * (a multi-valued element stores several values per record).
     *
     * @param array{count: int, parents: int, groups: list<array<string, mixed>>, deletable: int} $summary
     * @return array{count: int, parents: int, groups: list<array<string, mixed>>, deletable: int}
     */
    private function addOrphanValueRecordCounts(array $summary): array
    {
        $db = $this->db;
        $ids = array_map(static fn (array $group): int => self::intValue($group['parent_id'] ?? 0), $summary['groups']);
        $counts = [];

        if ($ids !== []) {
            $query = $db->createQuery()
                ->select([
                    $db->quoteName('child.element', 'element_id'),
                    'COUNT(DISTINCT ' . $db->quoteName('child.record') . ') AS ' . $db->quoteName('total'),
                ])
                ->from($db->quoteName('#__facileforms_subrecords', 'child'))
                ->join(
                    'LEFT',
                    $db->quoteName('#__facileforms_elements', 'elements') . ' ON elements.id = child.element'
                )
                ->where('elements.id IS NULL')
                ->whereIn($db->quoteName('child.element'), $ids)
                ->group($db->quoteName('child.element'));

            $db->setQuery($query);
            foreach ($this->loadAssocRows() as $row) {
                $counts[self::intValue($row['element_id'] ?? 0)] = self::intValue($row['total'] ?? 0);
            }
        }

        foreach ($summary['groups'] as &$group) {
            $group['records'] = $counts[self::intValue($group['parent_id'] ?? 0)] ?? 0;
        }
        unset($group);

        return $summary;
    }

    /**
     * Looks for the current element carrying the stored name of each missing
     * element, within the form of the records, and counts the values that can
     * be reassigned to it (the target holds no value yet for that record).
     *
     * @param array{count: int, parents: int, groups: list<array<string, mixed>>, deletable: int} $summary
     * @return array<string, mixed>
     */
    private function addReassignableOrphanValues(array $summary): array
    {
        $db = $this->db;
        $db->setQuery(OrphanValueMatcher::query($db)->select('COUNT(*)')->where('x.id IS NULL'));
        $summary['reassignable'] = self::intValue($db->loadResult());
        $ids = array_map(static fn (array $group): int => self::intValue($group['parent_id'] ?? 0), $summary['groups']);
        $matches = [];

        if ($ids !== []) {
            $query = OrphanValueMatcher::query($db, $ids)
                ->select([
                    $db->quoteName('s.element', 'old_id'),
                    $db->quoteName('e.id', 'target_id'),
                    $db->quoteName('e.name', 'target_name'),
                    'COUNT(*) AS ' . $db->quoteName('total'),
                    'SUM(CASE WHEN x.id IS NULL THEN 1 ELSE 0 END) AS ' . $db->quoteName('free'),
                ])
                ->group([$db->quoteName('s.element'), $db->quoteName('e.id'), $db->quoteName('e.name')])
                ->order($db->quoteName('free') . ' DESC, ' . $db->quoteName('e.id') . ' ASC');

            $db->setQuery($query);
            foreach ($this->loadAssocRows() as $row) {
                $old = self::intValue($row['old_id'] ?? 0);
                $matches[$old] ??= [
                    'target_id' => self::intValue($row['target_id'] ?? 0),
                    'target_name' => self::stringValue($row['target_name'] ?? ''),
                    'matched' => 0,
                    'free' => 0,
                ];
                $matches[$old]['matched'] += self::intValue($row['total'] ?? 0);
                $matches[$old]['free'] += self::intValue($row['free'] ?? 0);
            }
        }

        $candidates = OrphanValueMatcher::candidates($db, $ids);

        foreach ($summary['groups'] as &$group) {
            $group['candidates'] = $candidates[self::intValue($group['parent_id'] ?? 0)] ?? [];
            $match = $matches[self::intValue($group['parent_id'] ?? 0)] ?? null;
            $group['target_id'] = $match['target_id'] ?? 0;
            $group['target_name'] = $match['target_name'] ?? '';
            $group['matched'] = $match['matched'] ?? 0;
            $group['reassignable'] = $match['free'] ?? 0;
        }
        unset($group);

        return $summary;
    }

    /**
     * Elements keep no copy of their form name: reuse the one stored by the
     * orphan records pointing at the same missing form.
     *
     * @param list<array<string, mixed>> $checks
     *
     * @return list<array<string, mixed>>
     */
    public static function labelOrphanForms(array $checks): array
    {
        $names = [];

        foreach ($checks as $check) {
            if (($check['parent'] ?? '') !== 'form') {
                continue;
            }

            $groups = $check['groups'] ?? [];

            if (!is_array($groups)) {
                continue;
            }

            foreach ($groups as $group) {
                if (!is_array($group)) {
                    continue;
                }

                $label = self::stringValue($group['label'] ?? '');
                if ($label !== '') {
                    $names[self::intValue($group['parent_id'] ?? 0)] = $label;
                }
            }
        }

        foreach ($checks as &$check) {
            if (($check['parent'] ?? '') !== 'form') {
                continue;
            }

            $groups = $check['groups'] ?? [];

            if (!is_array($groups)) {
                continue;
            }

            foreach ($groups as &$group) {
                if (!is_array($group)) {
                    continue;
                }

                if (self::stringValue($group['label'] ?? '') === '') {
                    $group['label'] = $names[self::intValue($group['parent_id'] ?? 0)] ?? '';
                }
            }
            $check['groups'] = $groups;
            unset($group);
        }
        unset($check);

        return $checks;
    }

    /**
     * Flags any facileforms_ or breezingforms_ table on this prefix that
     * isn't in EXPECTED_TABLES - leftovers from an old install, a removed
     * feature, or a botched uninstall that a fresh EXPECTED_TABLES scan
     * alone would never surface (it only looks for tables it expects).
     *
     * @param list<string> $tableList
     * @return list<string>
     */
    private function findUnexpectedTables(array $tableList): array
    {
        $prefix = $this->db->getPrefix();
        $expectedPhysical = array_map(
            static fn (string $table): string => $prefix . $table,
            self::EXPECTED_TABLES
        );

        $unexpected = [];
        foreach ($tableList as $physicalTable) {
            if (in_array($physicalTable, $expectedPhysical, true)) {
                continue;
            }

            $withoutPrefix = str_starts_with($physicalTable, $prefix)
                ? substr($physicalTable, strlen($prefix))
                : $physicalTable;

            if (preg_match('/^(facileforms|breezingforms)_?/i', $withoutPrefix) === 1) {
                $unexpected[] = '#__' . $withoutPrefix;
            }
        }

        sort($unexpected, SORT_NATURAL | SORT_FLAG_CASE);

        return $unexpected;
    }

    /**
     * Scans both the admin and site language folders for leftover files
     * from this component's older naming (com_breezingforms, no "ng"),
     * which a straightforward Joomla update never removes on its own.
     *
     * @return list<string>
     */
    private function findStaleLanguageFiles(): array
    {
        $stale = [];
        $roots = [
            JPATH_ADMINISTRATOR . '/language',
            JPATH_SITE . '/language',
        ];

        foreach ($roots as $root) {
            if (!is_dir($root)) {
                continue;
            }

            $matches = glob($root . '/*/com_breezingforms.*') ?: [];
            foreach ($matches as $match) {
                $stale[] = str_replace(JPATH_ROOT, '', $match);
            }
        }

        sort($stale, SORT_NATURAL | SORT_FLAG_CASE);

        return $stale;
    }

    /**
     * Leftover administrator/components/com_breezingformsng-named temp
     * folders under Joomla's configured tmp_path or PHP's system temp path,
     * from an install/update that was interrupted before cleanup.
     *
     * @return list<string>
     */
    private function findStaleInstallerTempDirectories(): array
    {
        $stale = [];
        $roots = array_values(array_unique([$this->temporaryPath, sys_get_temp_dir()]));

        foreach ($roots as $root) {
            $tmpRoot = realpath($root);

            if ($tmpRoot === false || !is_dir($tmpRoot)) {
                continue;
            }

            $matches = glob($tmpRoot . '/install_*', GLOB_ONLYDIR) ?: [];

            foreach ($matches as $match) {
                $realMatch = realpath($match);

                if (
                    $realMatch === false
                    || dirname($realMatch) !== rtrim($tmpRoot, DIRECTORY_SEPARATOR)
                    || !is_dir($realMatch)
                    || filemtime($realMatch) === false
                    || filemtime($realMatch) > time() - 3600
                ) {
                    continue;
                }

                // Joomla names these folders with a random suffix, not the
                // extension name, so check the extracted contents instead of
                // the folder name itself.
                $manifestMatches = glob($realMatch . '/*breezingformsng*.xml') ?: [];
                if ($manifestMatches === []) {
                    continue;
                }

                $stale[] = $realMatch;
            }
        }

        sort($stale, SORT_NATURAL | SORT_FLAG_CASE);

        return $stale;
    }

    /**
     * Site menu items pointing at this component whose target form (referenced
     * by name via the ff_com_name menu parameter, not by id) is missing,
     * unpublished, or not configured at all - plus menus still linking to the
     * pre-NG option=com_breezingforms component.
     *
     * @return list<array<string, mixed>>
     */
    private function findMenuIssues(): array
    {
        $issues = [];

        try {
            $query = $this->db->createQuery()
                ->select($this->db->quoteName(['id', 'title', 'link', 'published', 'params']))
                ->from($this->db->quoteName('#__menu'))
                ->where($this->db->quoteName('client_id') . ' = 0')
                ->where($this->db->quoteName('published') . ' != -2')
                ->where($this->db->quoteName('type') . ' = ' . $this->db->quote('component'))
                ->where($this->db->quoteName('link') . ' LIKE ' . $this->db->quote('%option=com_breezingforms%'))
                ->order($this->db->quoteName('id') . ' ASC');
            $this->db->setQuery($query);
            $menus = $this->loadAssocRows();

            $query = $this->db->createQuery()
                ->select($this->db->quoteName(['name', 'published']))
                ->from($this->db->quoteName('#__facileforms_forms'));
            $this->db->setQuery($query);
            $formRows = $this->loadAssocRows();
        } catch (\Throwable $exception) {
            $this->addError('Unable to inspect BreezingForms menu items: ' . $exception->getMessage());
            return [];
        }

        $publishedByName = [];
        foreach ($formRows as $formRow) {
            $name = trim(self::stringValue($formRow['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $publishedByName[$name] = ($publishedByName[$name] ?? false)
                || self::intValue($formRow['published'] ?? 0) === 1;
        }

        foreach ($menus as $menu) {
            $link = trim(self::stringValue($menu['link'] ?? ''));
            $linkQuery = [];
            parse_str(self::stringValue(parse_url($link, PHP_URL_QUERY)), $linkQuery);
            $option = is_string($linkQuery['option'] ?? null) ? $linkQuery['option'] : '';
            $menuIssues = [];
            $formName = '';

            if ($option === 'com_breezingforms') {
                $menuIssues[] = 'legacy_component_link';
            } elseif ($option === 'com_breezingformsng') {
                $params = json_decode(self::stringValue($menu['params'] ?? ''), true);
                $formName = trim(self::stringValue(is_array($params) ? ($params['ff_com_name'] ?? '') : ''));

                if ($formName === '') {
                    $menuIssues[] = 'no_form_configured';
                } elseif (!array_key_exists($formName, $publishedByName)) {
                    $menuIssues[] = 'form_missing';
                } elseif (!$publishedByName[$formName]) {
                    $menuIssues[] = 'form_unpublished';
                }
            }

            if ($menuIssues !== []) {
                $issues[] = [
                    'menu_id' => self::intValue($menu['id'] ?? 0),
                    'title' => trim(self::stringValue($menu['title'] ?? '')),
                    'published' => self::intValue($menu['published'] ?? 0),
                    'link' => $link,
                    'form_name' => $formName,
                    'issues' => $menuIssues,
                ];
            }
        }

        return $issues;
    }

    /**
     * Duplicate #__extensions registrations (same type/element/folder/client)
     * left behind by repeated discover-installs, and leftover rows from the
     * pre-NG crosstec extensions that an update never removes.
     *
     * @return array{duplicates: list<array<string, mixed>>, legacy: list<array<string, mixed>>}
     */
    private function findExtensionIssues(): array
    {
        $excludedType = 'template';

        try {
            $query = $this->db->createQuery()
                ->select($this->db->quoteName([
                    'extension_id',
                    'name',
                    'type',
                    'element',
                    'folder',
                    'client_id',
                    'enabled',
                ]))
                ->from($this->db->quoteName('#__extensions'))
                ->where($this->db->quoteName('element') . ' LIKE ' . $this->db->quote('%breezingforms%'))
                ->where($this->db->quoteName('type') . ' <> :excludedType')
                ->order($this->db->quoteName('extension_id') . ' ASC')
                ->bind(':excludedType', $excludedType);
            $this->db->setQuery($query);
            $rows = $this->loadAssocRows();
        } catch (\Throwable $exception) {
            $this->addError('Unable to inspect BreezingForms extension registrations: ' . $exception->getMessage());
            return ['duplicates' => [], 'legacy' => []];
        }

        $byKey = [];
        $legacy = [];

        foreach ($rows as $row) {
            $element = strtolower(trim(self::stringValue($row['element'] ?? '')));
            $entry = [
                'extension_id' => self::intValue($row['extension_id'] ?? 0),
                'name' => self::stringValue($row['name'] ?? ''),
                'type' => self::stringValue($row['type'] ?? ''),
                'element' => self::stringValue($row['element'] ?? ''),
                'folder' => self::stringValue($row['folder'] ?? ''),
                'client_id' => self::intValue($row['client_id'] ?? 0),
                'enabled' => self::intValue($row['enabled'] ?? 0),
            ];

            // What the NG package installs is expected; any other registration is a former BreezingForms leftover.
            if (
                !NgExtensions::owns($entry['type'], $entry['element'], $entry['folder'], $entry['client_id'])
                && !str_contains($element, 'breezingformsng')
            ) {
                $legacy[] = $entry;
            }

            $key = implode('|', [
                $entry['type'],
                $element,
                strtolower($entry['folder']),
                self::intValue($row['client_id'] ?? 0),
            ]);
            $byKey[$key][] = $entry;
        }

        $duplicates = [];
        foreach ($byKey as $entries) {
            if (count($entries) > 1) {
                // Keep the first registration; every later row is redundant.
                $duplicates[] = [
                    'keep' => $entries[0],
                    'drop' => array_slice($entries, 1),
                ];
            }
        }

        return ['duplicates' => $duplicates, 'legacy' => $legacy];
    }

    /**
     * Forms sharing the same name within the same package - typically leftovers
     * from a package being imported several times. Genuinely harmful here
     * because site menu items reference forms by name (ff_com_name), so
     * duplicated names make that resolution ambiguous. The row to keep is the
     * one holding submitted records (or the oldest when none has any).
     *
     * @return list<array<string, mixed>>
     */
    private function findDuplicateForms(): array
    {
        try {
            $query = $this->db->createQuery()
                ->select($this->db->quoteName(['f.id', 'f.name', 'f.package', 'f.published']))
                ->select('COUNT(' . $this->db->quoteName('r.id') . ') AS ' . $this->db->quoteName('record_count'))
                ->from($this->db->quoteName('#__facileforms_forms', 'f'))
                ->join('LEFT', $this->db->quoteName('#__facileforms_records', 'r'), 'r.form = f.id')
                ->group($this->db->quoteName('f.id'))
                ->order($this->db->quoteName('f.id') . ' ASC');
            $this->db->setQuery($query);
            $rows = $this->loadAssocRows();
        } catch (\Throwable $exception) {
            $this->addError('Unable to inspect duplicate forms: ' . $exception->getMessage());
            return [];
        }

        $byKey = [];
        foreach ($rows as $row) {
            $name = trim(self::stringValue($row['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $byKey[$name . '|' . trim(self::stringValue($row['package'] ?? ''))][] = [
                'id' => self::intValue($row['id'] ?? 0),
                'name' => $name,
                'package' => trim(self::stringValue($row['package'] ?? '')),
                'published' => self::intValue($row['published'] ?? 0),
                'record_count' => self::intValue($row['record_count'] ?? 0),
            ];
        }

        $duplicates = [];
        foreach ($byKey as $entries) {
            if (count($entries) < 2) {
                continue;
            }

            // Prefer the row that actually holds records; ties go to the oldest id.
            usort($entries, static fn(array $a, array $b): int =>
                [$b['record_count'], $a['id']] <=> [$a['record_count'], $b['id']]);

            $duplicates[] = [
                'name' => $entries[0]['name'],
                'package' => $entries[0]['package'],
                'keep' => $entries[0],
                'drop' => array_slice($entries, 1),
            ];
        }

        return $duplicates;
    }

    /**
     * Column names per table, read from the packaged install schema so the
     * audit and a fresh installation can never disagree on what is expected.
     *
     * @return array<string, array<int, string>>
     */
    public static function parseExpectedColumns(string $sql): array
    {
        $expected = [];
        preg_match_all('/CREATE TABLE IF NOT EXISTS `#__(\w+)` \((.*?)\n\) ENGINE/s', $sql, $tables, PREG_SET_ORDER);

        foreach ($tables as $table) {
            preg_match_all('/^\s*`(\w+)`\s/m', $table[2], $columns);
            $expected[$table[1]] = $columns[1];
        }

        return $expected;
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function getExpectedColumns(): array
    {
        $sql = file_get_contents(JPATH_ADMINISTRATOR . self::INSTALL_SQL);

        if ($sql === false) {
            $this->addError('Unable to read the install schema to verify table columns.');

            return [];
        }

        return self::parseExpectedColumns($sql);
    }

    /**
     * Columns the current schema defines but the table lacks - the state a
     * legacy table is left in when it was preserved without being migrated.
     *
     * @param array<int, string> $expected
     *
     * @return array<int, string>
     */
    private function findMissingColumns(string $physicalTable, string $alias, array $expected): array
    {
        try {
            $actual = array_map('strtolower', array_keys($this->db->getTableColumns($physicalTable)));
        } catch (\Throwable $exception) {
            $this->addError('Unable to inspect columns for ' . $alias . ': ' . $exception->getMessage());

            return [];
        }

        return array_values(array_filter(
            $expected,
            static fn (string $column): bool => !in_array(strtolower($column), $actual, true)
        ));
    }

    /**
     * Forms left with debug mode enabled, which shows diagnostic output to
     * site visitors.
     *
     * @param list<string> $tableList
     * @return list<array<string, mixed>>
     */
    private function findDebugModeForms(array $tableList): array
    {
        $physicalTable = $this->db->getPrefix() . 'facileforms_forms';

        if (!in_array($physicalTable, $tableList, true)) {
            return [];
        }

        try {
            if (!array_key_exists('debug_mode', $this->db->getTableColumns($physicalTable))) {
                return [];
            }

            $query = $this->db->createQuery()
                ->select($this->db->quoteName(['id', 'name', 'title', 'package', 'published']))
                ->from($this->db->quoteName('#__facileforms_forms'))
                ->where($this->db->quoteName('debug_mode') . ' = 1')
                ->order($this->db->quoteName('id') . ' ASC');
            $this->db->setQuery($query);
            $rows = $this->loadAssocRows();
        } catch (\Throwable $exception) {
            $this->addError('Unable to inspect forms with debug mode enabled: ' . $exception->getMessage());

            return [];
        }

        return array_values(array_map(static fn (array $row): array => [
            'id' => self::intValue($row['id'] ?? 0),
            'name' => trim(self::stringValue($row['name'] ?? '')),
            'title' => trim(self::stringValue($row['title'] ?? '')),
            'package' => trim(self::stringValue($row['package'] ?? '')),
            'published' => self::intValue($row['published'] ?? 0),
        ], $rows));
    }

    /**
     * Forms, scripts, pieces and integrator rules in the trash: the same items as the Trash tile of the Tools screen.
     *
     * @return list<array{label: string, view: string, count: int}>
     */
    private function findTrashedItems(): array
    {
        try {
            return (new TrashInventoryService($this->db))->getTrashedItems();
        } catch (\Throwable $e) {
            $this->errors[] = $e->getMessage();

            return [];
        }
    }

    /**
     * Trashed records per form. A trash that is not empty is a normal state:
     * it is reported for information and never counted as an issue.
     *
     * @param list<string> $tableList
     * @return list<array{form: int, name: string, count: int}>
     */
    private function findTrashedRecords(array $tableList): array
    {
        $physicalTable = $this->db->getPrefix() . 'facileforms_records';

        if (!in_array($physicalTable, $tableList, true)) {
            return [];
        }

        try {
            if (!array_key_exists('state', $this->db->getTableColumns($physicalTable))) {
                return [];
            }

            $trashed = RecordState::TRASHED;
            $query = $this->db->createQuery()
                ->select($this->db->quoteName('r.form'))
                ->select('MAX(' . $this->db->quoteName('f.name') . ') AS ' . $this->db->quoteName('name'))
                ->select('COUNT(*) AS ' . $this->db->quoteName('record_count'))
                ->from($this->db->quoteName('#__facileforms_records', 'r'))
                ->join('LEFT', $this->db->quoteName('#__facileforms_forms', 'f'), 'f.id = r.form')
                ->where($this->db->quoteName('r.state') . ' = :trashed')
                ->group($this->db->quoteName('r.form'))
                ->order($this->db->quoteName('r.form') . ' ASC')
                ->bind(':trashed', $trashed, ParameterType::INTEGER);
            $this->db->setQuery($query);
            $rows = $this->loadAssocRows();
        } catch (\Throwable $exception) {
            $this->addError('Unable to inspect trashed records: ' . $exception->getMessage());

            return [];
        }

        return array_values(array_map(static fn (array $row): array => [
            'form' => self::intValue($row['form'] ?? 0),
            'name' => trim(self::stringValue($row['name'] ?? '')),
            'count' => self::intValue($row['record_count'] ?? 0),
        ], $rows));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function loadAssocRows(): array
    {
        $rows = $this->db->loadAssocList();

        if (!is_array($rows)) {
            return [];
        }

        $normalized = [];

        foreach ($rows as $row) {
            if (is_array($row)) {
                $normalized[] = self::normalizeAssocRow($row);
            }
        }

        return $normalized;
    }

    /**
     * @param array<mixed, mixed> $row
     * @return array<string, mixed>
     */
    private static function normalizeAssocRow(array $row): array
    {
        $normalized = [];

        foreach ($row as $key => $value) {
            if (is_string($key)) {
                $normalized[$key] = $value;
            }
        }

        return $normalized;
    }

    /**
     * @return array<string, mixed>
     */
    private function loadAssocRow(): array
    {
        $row = $this->db->loadAssoc();

        return is_array($row) ? $row : [];
    }

    private static function intValue(mixed $value): int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_bool($value)) {
            return $value ? 1 : 0;
        }

        if (is_float($value)) {
            return (int) $value;
        }

        return is_string($value) && is_numeric($value) ? (int) $value : 0;
    }

    private static function stringValue(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }

        return is_int($value) || is_float($value) || is_bool($value) ? (string) $value : '';
    }

    private function addError(string $message): void
    {
        $message = trim($message);

        if ($message !== '') {
            $this->errors[] = $message;
        }
    }
}
