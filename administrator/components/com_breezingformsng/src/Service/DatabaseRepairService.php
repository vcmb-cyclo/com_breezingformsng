<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Vcmb\Component\BreezingformsNG\Administrator\Service;

\defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomla\Filesystem\Folder;

final class DatabaseRepairService
{
    public function __construct(
        private readonly DatabaseInterface $db,
        private readonly string $temporaryPath
    ) {
    }

    /**
     * @param array<string, mixed> $group
     */
    public static function getDuplicateIndexSelectionToken(array $group): string
    {
        $drop = array_values(array_map(
            static fn($value): string => trim((string) $value),
            (array) ($group['drop'] ?? [])
        ));

        return hash('sha256', implode("\0", [
            trim((string) ($group['table'] ?? '')),
            trim((string) ($group['keep'] ?? '')),
            implode("\0", $drop),
        ]));
    }

    /**
     * @param array<string, mixed> $issue
     */
    public static function getColumnCollationSelectionToken(array $issue): string
    {
        return hash('sha256', implode("\0", [
            trim((string) ($issue['table'] ?? '')),
            trim((string) ($issue['column'] ?? '')),
            trim((string) ($issue['charset'] ?? '')),
            trim((string) ($issue['collation'] ?? '')),
            trim((string) ($issue['expected_charset'] ?? '')),
            trim((string) ($issue['expected'] ?? '')),
        ]));
    }

    /**
     * @param array<string, mixed> $issue
     */
    public static function getTableCollationSelectionToken(array $issue): string
    {
        return hash('sha256', implode("\0", [
            trim((string) ($issue['table'] ?? '')),
            trim((string) ($issue['collation'] ?? '')),
            trim((string) ($issue['expected'] ?? '')),
        ]));
    }

    /**
     * @param array<mixed> $selectedTokens
     * @return array{selected_tables:int, repaired_tables:int, failed_tables:int, errors:array<int,string>}
     */
    public function repairTableCollations(array $selectedTokens): array
    {
        $selectedTokens = array_values(array_unique(array_filter(
            array_map(static fn($value): string => trim((string) $value), $selectedTokens),
            static fn(string $value): bool => $value !== ''
        )));
        $report = (new DatabaseAuditService($this->db, $this->temporaryPath))->run();
        $selectedIssues = array_values(array_filter(
            (array) ($report['collation_issues'] ?? []),
            static fn(array $issue): bool => in_array(self::getTableCollationSelectionToken($issue), $selectedTokens, true)
        ));
        $errors = [];
        $repairedTables = 0;

        foreach ($selectedIssues as $issue) {
            $tableAlias = trim((string) ($issue['table'] ?? ''));
            $targetCollation = trim((string) ($issue['expected'] ?? ''));
            $tableName = str_starts_with($tableAlias, '#__')
                ? $this->db->getPrefix() . substr($tableAlias, 3)
                : '';

            try {
                if ($tableName === '' || $targetCollation === '' || !in_array($tableName, $this->db->getTableList(), true)) {
                    throw new \RuntimeException('Invalid table-collation audit entry.');
                }

                $this->db->setQuery(
                    'ALTER TABLE ' . $this->db->quoteName($tableName)
                    . ' CONVERT TO CHARACTER SET utf8mb4 COLLATE ' . $targetCollation
                );
                $this->db->execute();
                $repairedTables++;
            } catch (\Throwable $exception) {
                $errors[] = $tableAlias . ': ' . $exception->getMessage();
            }
        }

        return [
            'selected_tables' => count($selectedIssues),
            'repaired_tables' => $repairedTables,
            'failed_tables' => count($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Repair only column-collation issues selected from a fresh audit report.
     *
     * @param array<mixed> $selectedTokens
     * @return array{selected_columns:int, repaired_columns:int, failed_columns:int, errors:array<int,string>}
     */
    public function repairColumnCollations(array $selectedTokens): array
    {
        $selectedTokens = array_values(array_unique(array_filter(
            array_map(static fn($value): string => trim((string) $value), $selectedTokens),
            static fn(string $value): bool => $value !== ''
        )));
        $report = (new DatabaseAuditService($this->db, $this->temporaryPath))->run();
        $issues = array_values((array) ($report['column_collation_issues'] ?? []));
        $selectedIssues = array_values(array_filter(
            $issues,
            static fn(array $issue): bool => in_array(self::getColumnCollationSelectionToken($issue), $selectedTokens, true)
        ));
        $errors = [];
        $repairedColumns = 0;

        foreach ($selectedIssues as $issue) {
            $tableAlias = trim((string) ($issue['table'] ?? ''));
            $columnName = trim((string) ($issue['column'] ?? ''));
            $targetCharset = trim((string) ($issue['expected_charset'] ?? ''));
            $targetCollation = trim((string) ($issue['expected'] ?? ''));
            $tableName = str_starts_with($tableAlias, '#__')
                ? $this->db->getPrefix() . substr($tableAlias, 3)
                : '';

            try {
                if ($tableName === '' || $columnName === '' || $targetCharset !== 'utf8mb4' || $targetCollation === '') {
                    throw new \RuntimeException('Invalid column-collation audit entry.');
                }

                if (!in_array($tableName, $this->db->getTableList(), true)) {
                    throw new \RuntimeException('Table not found.');
                }

                $this->db->setQuery(
                    'SHOW FULL COLUMNS FROM ' . $this->db->quoteName($tableName)
                    . ' WHERE Field = ' . $this->db->quote($columnName)
                );
                $definition = (array) ($this->db->loadAssoc() ?: []);
                $type = trim((string) ($definition['Type'] ?? ''));

                if ($type === '') {
                    throw new \RuntimeException('Column not found.');
                }

                $sql = 'ALTER TABLE ' . $this->db->quoteName($tableName)
                    . ' MODIFY COLUMN ' . $this->db->quoteName($columnName)
                    . ' ' . $type
                    . ' CHARACTER SET ' . $targetCharset
                    . ' COLLATE ' . $targetCollation
                    . ((string) ($definition['Null'] ?? '') === 'YES' ? ' NULL' : ' NOT NULL');

                if (array_key_exists('Default', $definition) && $definition['Default'] !== null) {
                    $sql .= ' DEFAULT ' . $this->db->quote((string) $definition['Default']);
                } elseif ((string) ($definition['Null'] ?? '') === 'YES') {
                    $sql .= ' DEFAULT NULL';
                }

                if ((string) ($definition['Comment'] ?? '') !== '') {
                    $sql .= ' COMMENT ' . $this->db->quote((string) $definition['Comment']);
                }

                $this->db->setQuery($sql);
                $this->db->execute();
                $repairedColumns++;
            } catch (\Throwable $exception) {
                $errors[] = $tableAlias . '/' . $columnName . ': ' . $exception->getMessage();
            }
        }

        return [
            'selected_columns' => count($selectedIssues),
            'repaired_columns' => $repairedColumns,
            'failed_columns' => count($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Repair only duplicate-index groups selected from a fresh audit report.
     *
     * @param array<mixed> $selectedTokens
     * @return array{
     *     selected_groups:int,
     *     repaired_groups:int,
     *     removed_indexes:int,
     *     failed_indexes:int,
     *     errors:array<int,string>
     * }
     */
    public function repairDuplicateIndexes(array $selectedTokens): array
    {
        $selectedTokens = array_values(array_unique(array_filter(
            array_map(static fn($value): string => trim((string) $value), $selectedTokens),
            static fn(string $value): bool => $value !== ''
        )));
        $report = (new DatabaseAuditService($this->db, $this->temporaryPath))->run();
        $groups = array_values((array) ($report['duplicate_indexes'] ?? []));
        $selectedGroups = [];
        $errors = [];
        $removedIndexes = 0;
        $failedIndexes = 0;
        $repairedGroups = 0;

        foreach ($groups as $group) {
            if (!is_array($group) || !in_array(self::getDuplicateIndexSelectionToken($group), $selectedTokens, true)) {
                continue;
            }

            $selectedGroups[] = $group;
            $tableAlias = trim((string) ($group['table'] ?? ''));
            $tableName = str_starts_with($tableAlias, '#__')
                ? $this->db->getPrefix() . substr($tableAlias, 3)
                : '';

            if ($tableName === '' || !in_array($tableName, $this->db->getTableList(), true)) {
                $failedIndexes += count((array) ($group['drop'] ?? []));
                $errors[] = $tableAlias !== '' ? $tableAlias : 'unknown table';
                continue;
            }

            $groupRemovedIndexes = 0;

            foreach ((array) ($group['drop'] ?? []) as $indexName) {
                $indexName = trim((string) $indexName);

                if ($indexName === '') {
                    continue;
                }

                try {
                    $this->db->setQuery(
                        'ALTER TABLE ' . $this->db->quoteName($tableName)
                        . ' DROP INDEX ' . $this->db->quoteName($indexName)
                    );
                    $this->db->execute();
                    $removedIndexes++;
                    $groupRemovedIndexes++;
                } catch (\Throwable $exception) {
                    $failedIndexes++;
                    $errors[] = $tableAlias . '/' . $indexName . ': ' . $exception->getMessage();
                }
            }

            if ($groupRemovedIndexes > 0) {
                $repairedGroups++;
            }
        }

        return [
            'selected_groups' => count($selectedGroups),
            'repaired_groups' => $repairedGroups,
            'removed_indexes' => $removedIndexes,
            'failed_indexes' => $failedIndexes,
            'errors' => $errors,
        ];
    }

    /**
     * Deletes the elements whose form no longer exists. Their records keep the
     * id, name, title and type of each field in their own rows.
     *
     * @return int The number of elements deleted.
     */
    public function deleteOrphanElements(): int
    {
        $db = $this->db;
        $forms = $db->createQuery()
            ->select('1')
            ->from($db->quoteName('#__facileforms_forms', 'forms'))
            ->where($db->quoteName('forms.id') . ' = ' . $db->quoteName('#__facileforms_elements.form'));
        $query = $db->createQuery()
            ->delete($db->quoteName('#__facileforms_elements'))
            ->where('NOT EXISTS (' . $forms . ')');
        $db->setQuery($query)->execute();

        return $db->getAffectedRows();
    }

    /**
     * Deletes the values (subrecords) whose element no longer exists, never those
     * of an element that still does, whatever id is asked for.
     *
     * @param int $elementId One missing element, or 0 for all of them.
     *
     * @return int The number of values deleted.
     */
    public function deleteOrphanValues(int $elementId = 0): int
    {
        $db = $this->db;
        $elements = $db->createQuery()
            ->select('1')
            ->from($db->quoteName('#__facileforms_elements', 'elements'))
            ->where($db->quoteName('elements.id') . ' = ' . $db->quoteName('#__facileforms_subrecords.element'));
        $query = $db->createQuery()
            ->delete($db->quoteName('#__facileforms_subrecords'))
            ->where('NOT EXISTS (' . $elements . ')');

        if ($elementId > 0) {
            $query->where($db->quoteName('element') . ' = :elementId')
                ->bind(':elementId', $elementId, ParameterType::INTEGER);
        }

        $db->setQuery($query)->execute();

        return $db->getAffectedRows();
    }

    /**
     * Delete only stale installer directories selected from a fresh audit.
     *
     * @param array<mixed> $selectedPaths
     * @return array{selected_dirs:int, deleted_dirs:int, failed_dirs:int, errors:array<int,string>}
     */
    public function deleteStaleInstallerTemp(array $selectedPaths): array
    {
        $selectedPaths = array_values(array_unique(array_filter(
            array_map(static fn($value): string => trim((string) $value), $selectedPaths),
            static fn(string $value): bool => $value !== ''
        )));
        $report = (new DatabaseAuditService($this->db, $this->temporaryPath))->run();
        $stalePaths = array_values(array_filter(
            array_map(
                static fn($value): string => realpath((string) $value) ?: '',
                (array) ($report['stale_installer_temp_dirs'] ?? [])
            ),
            static fn(string $value): bool => $value !== ''
        ));
        $selectedDirs = array_values(array_intersect($selectedPaths, $stalePaths));
        $errors = [];
        $deletedDirs = 0;

        foreach ($selectedDirs as $path) {
            try {
                if (!is_dir($path) || !Folder::delete($path)) {
                    throw new \RuntimeException('The directory could not be removed.');
                }

                $deletedDirs++;
            } catch (\Throwable $exception) {
                $errors[] = $path . ': ' . $exception->getMessage();
            }
        }

        return [
            'selected_dirs' => count($selectedDirs),
            'deleted_dirs' => $deletedDirs,
            'failed_dirs' => count($errors),
            'errors' => $errors,
        ];
    }
}
