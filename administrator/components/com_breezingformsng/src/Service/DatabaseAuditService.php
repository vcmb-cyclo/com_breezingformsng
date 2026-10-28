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
        'facileforms_subrecords',
        'facileforms_integrator_criteria_fixed',
        'facileforms_integrator_criteria_form',
        'facileforms_integrator_criteria_joomla',
        'facileforms_integrator_items',
        'facileforms_integrator_rules',
    ];

    private const FALLBACK_COLLATION = 'utf8mb4_unicode_ci';

    /** Missing parents listed per orphan relation; the rest is only counted. */
    private const ORPHAN_GROUP_LIMIT = 10;

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
            $rows = (int) ($status['TABLE_ROWS'] ?? 0);
            $collation = (string) ($status['TABLE_COLLATION'] ?? '');
            $dataBytes = (int) ($status['DATA_LENGTH'] ?? 0);
            $indexBytes = (int) ($status['INDEX_LENGTH'] ?? 0);
            $totalRows += $rows;
            $totalDataBytes += $dataBytes;
            $totalIndexBytes += $indexBytes;
            $tables[] = [
                'table' => $alias,
                'rows' => $rows,
                'engine' => (string) ($status['ENGINE'] ?? ''),
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
            $columns = $this->db->loadAssocList() ?: [];
        } catch (\Throwable $exception) {
            $this->addError('Unable to inspect column encoding for ' . $alias . ': ' . $exception->getMessage());

            return [];
        }

        $issues = [];
        foreach ($columns as $column) {
            $charset = (string) ($column['CHARACTER_SET_NAME'] ?? '');
            $collation = (string) ($column['COLLATION_NAME'] ?? '');
            if (
                ($charset !== '' && strcasecmp($charset, self::TARGET_CHARSET) !== 0)
                || ($collation !== '' && strcasecmp($collation, $targetCollation) !== 0)
            ) {
                $issues[] = [
                    'table' => $alias,
                    'column' => (string) ($column['COLUMN_NAME'] ?? ''),
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
            $collation = (string) ($table['collation'] ?? '');
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

        return (array) ($this->db->loadAssoc() ?: []);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function findDuplicateIndexes(string $physicalTable, string $alias): array
    {
        try {
            $this->db->setQuery('SHOW INDEX FROM ' . $this->db->quoteName($physicalTable));
            $rows = $this->db->loadAssocList() ?: [];
        } catch (\Throwable $exception) {
            $this->addError('Unable to inspect indexes for ' . $alias . ': ' . $exception->getMessage());

            return [];
        }
        $indexes = [];

        foreach ($rows as $row) {
            $name = (string) ($row['Key_name'] ?? '');
            if ($name === '' || strtoupper($name) === 'PRIMARY') {
                continue;
            }

            $sequence = (int) ($row['Seq_in_index'] ?? 0);
            $indexes[$name]['meta'] = implode(':', [
                (string) ($row['Non_unique'] ?? '1'),
                strtoupper((string) ($row['Index_type'] ?? 'BTREE')),
            ]);
            $indexes[$name][$sequence] = implode(':', [
                strtolower((string) ($row['Column_name'] ?? '')),
                (string) ($row['Sub_part'] ?? ''),
                strtoupper((string) ($row['Collation'] ?? 'A')),
            ]);
        }

        $bySignature = [];
        foreach ($indexes as $name => $definition) {
            $meta = (string) ($definition['meta'] ?? '1:BTREE');
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
                $result[] = ['id' => $id, 'parent' => $parentKind]
                    + $this->summarizeOrphans($child, $parent, $foreignKey, $labelColumn, $dateColumn);
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
        ?string $dateColumn
    ): array {
        $key = $this->db->quoteName('child.' . $foreignKey);
        $orphans = fn() => $this->db->createQuery()
            ->from($this->db->quoteName('#__' . $child, 'child'))
            ->join(
                'LEFT',
                $this->db->quoteName('#__' . $parent, 'parent'),
                $key . ' = ' . $this->db->quoteName('parent.id')
            )
            ->where($this->db->quoteName('parent.id') . ' IS NULL');

        $totals = $this->db->setQuery(
            $orphans()->select(['COUNT(*) AS ' . $this->db->quoteName('total'), 'COUNT(DISTINCT ' . $key . ') AS '
                . $this->db->quoteName('parents')])
        )->loadAssoc() ?? [];
        $count = (int) ($totals['total'] ?? 0);

        if ($count === 0) {
            return ['count' => 0, 'parents' => 0, 'groups' => []];
        }

        $query = $orphans()
            ->select([
                $key . ' AS ' . $this->db->quoteName('parent_id'),
                'COUNT(*) AS ' . $this->db->quoteName('total'),
            ])
            ->group($key)
            ->order($this->db->quoteName('total') . ' DESC, ' . $key . ' ASC')
            ->setLimit(self::ORPHAN_GROUP_LIMIT);

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

        foreach ($this->db->setQuery($query)->loadAssocList() as $row) {
            $groups[] = [
                'parent_id' => (int) $row['parent_id'],
                'count' => (int) $row['total'],
                'label' => (string) ($row['label'] ?? ''),
                'first' => (string) ($row['first'] ?? ''),
                'last' => (string) ($row['last'] ?? ''),
            ];
        }

        return ['count' => $count, 'parents' => (int) ($totals['parents'] ?? 0), 'groups' => $groups];
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

            foreach ($check['groups'] as $group) {
                if ($group['label'] !== '') {
                    $names[$group['parent_id']] = $group['label'];
                }
            }
        }

        foreach ($checks as &$check) {
            if (($check['parent'] ?? '') !== 'form') {
                continue;
            }

            foreach ($check['groups'] as &$group) {
                if ($group['label'] === '') {
                    $group['label'] = $names[$group['parent_id']] ?? '';
                }
            }
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
                ->where($this->db->quoteName('type') . ' = ' . $this->db->quote('component'))
                ->where($this->db->quoteName('link') . ' LIKE ' . $this->db->quote('%option=com_breezingforms%'))
                ->order($this->db->quoteName('id') . ' ASC');
            $this->db->setQuery($query);
            $menus = $this->db->loadAssocList() ?: [];

            $query = $this->db->createQuery()
                ->select($this->db->quoteName(['name', 'published']))
                ->from($this->db->quoteName('#__facileforms_forms'));
            $this->db->setQuery($query);
            $formRows = $this->db->loadAssocList() ?: [];
        } catch (\Throwable $exception) {
            $this->addError('Unable to inspect BreezingForms menu items: ' . $exception->getMessage());
            return [];
        }

        $publishedByName = [];
        foreach ($formRows as $formRow) {
            $name = trim((string) ($formRow['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $publishedByName[$name] = ($publishedByName[$name] ?? false) || (int) ($formRow['published'] ?? 0) === 1;
        }

        foreach ($menus as $menu) {
            $link = trim((string) ($menu['link'] ?? ''));
            $linkQuery = [];
            parse_str((string) parse_url($link, PHP_URL_QUERY), $linkQuery);
            $option = is_string($linkQuery['option'] ?? null) ? $linkQuery['option'] : '';
            $menuIssues = [];
            $formName = '';

            if ($option === 'com_breezingforms') {
                $menuIssues[] = 'legacy_component_link';
            } elseif ($option === 'com_breezingformsng') {
                $params = json_decode((string) ($menu['params'] ?? ''), true);
                $formName = trim((string) (is_array($params) ? ($params['ff_com_name'] ?? '') : ''));

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
                    'menu_id' => (int) ($menu['id'] ?? 0),
                    'title' => trim((string) ($menu['title'] ?? '')),
                    'published' => (int) ($menu['published'] ?? 0),
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
                ->order($this->db->quoteName('extension_id') . ' ASC');
            $this->db->setQuery($query);
            $rows = $this->db->loadAssocList() ?: [];
        } catch (\Throwable $exception) {
            $this->addError('Unable to inspect BreezingForms extension registrations: ' . $exception->getMessage());
            return ['duplicates' => [], 'legacy' => []];
        }

        $byKey = [];
        $legacy = [];

        foreach ($rows as $row) {
            $element = strtolower(trim((string) ($row['element'] ?? '')));
            $entry = [
                'extension_id' => (int) ($row['extension_id'] ?? 0),
                'name' => (string) ($row['name'] ?? ''),
                'type' => (string) ($row['type'] ?? ''),
                'element' => (string) ($row['element'] ?? ''),
                'folder' => (string) ($row['folder'] ?? ''),
                'enabled' => (int) ($row['enabled'] ?? 0),
            ];

            // The NG component and its compatibility plugin are the expected rows.
            $isExpected = ($entry['type'] === 'component' && $element === 'com_breezingformsng')
                || ($entry['type'] === 'plugin' && $element === 'bfcompat' && $entry['folder'] === 'system');

            if (!$isExpected && !str_contains($element, 'breezingformsng')) {
                $legacy[] = $entry;
            }

            $key = implode('|', [
                $entry['type'],
                $element,
                strtolower($entry['folder']),
                (int) ($row['client_id'] ?? 0),
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
            $rows = $this->db->loadAssocList() ?: [];
        } catch (\Throwable $exception) {
            $this->addError('Unable to inspect duplicate forms: ' . $exception->getMessage());
            return [];
        }

        $byKey = [];
        foreach ($rows as $row) {
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $byKey[$name . '|' . trim((string) ($row['package'] ?? ''))][] = [
                'id' => (int) ($row['id'] ?? 0),
                'name' => $name,
                'package' => trim((string) ($row['package'] ?? '')),
                'published' => (int) ($row['published'] ?? 0),
                'record_count' => (int) ($row['record_count'] ?? 0),
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
            $rows = $this->db->loadAssocList() ?: [];
        } catch (\Throwable $exception) {
            $this->addError('Unable to inspect forms with debug mode enabled: ' . $exception->getMessage());

            return [];
        }

        return array_values(array_map(static fn (array $row): array => [
            'id' => (int) ($row['id'] ?? 0),
            'name' => trim((string) ($row['name'] ?? '')),
            'title' => trim((string) ($row['title'] ?? '')),
            'package' => trim((string) ($row['package'] ?? '')),
            'published' => (int) ($row['published'] ?? 0),
        ], $rows));
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
            $rows = $this->db->loadAssocList() ?: [];
        } catch (\Throwable $exception) {
            $this->addError('Unable to inspect trashed records: ' . $exception->getMessage());

            return [];
        }

        return array_values(array_map(static fn (array $row): array => [
            'form' => (int) ($row['form'] ?? 0),
            'name' => trim((string) ($row['name'] ?? '')),
            'count' => (int) ($row['record_count'] ?? 0),
        ], $rows));
    }

    private function addError(string $message): void
    {
        $message = trim($message);

        if ($message !== '') {
            $this->errors[] = $message;
        }
    }
}
