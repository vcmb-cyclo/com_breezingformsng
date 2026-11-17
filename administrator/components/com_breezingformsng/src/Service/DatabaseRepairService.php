<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Vcmb\Component\BreezingformsNG\Administrator\Service;

\defined('_JEXEC') or die;

use Joomla\CMS\Installer\Installer;
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
     * Deletes only values whose element and record no longer exist. Values of
     * existing records remain available even after their form was deleted.
     *
     * @param ?int $elementId One missing element, or null for all of them.
     *
     * @return int The number of values deleted.
     */
    public function deleteOrphanValues(?int $elementId = null): int
    {
        if ($elementId !== null && $elementId < 0) {
            throw new \InvalidArgumentException('Invalid element ID.');
        }

        $db = $this->db;
        $elements = $db->createQuery()
            ->select('1')
            ->from($db->quoteName('#__facileforms_elements', 'elements'))
            ->where($db->quoteName('elements.id') . ' = ' . $db->quoteName('#__facileforms_subrecords.element'));
        $records = $db->createQuery()
            ->select('1')
            ->from($db->quoteName('#__facileforms_records', 'records'))
            ->where($db->quoteName('records.id') . ' = ' . $db->quoteName('#__facileforms_subrecords.record'));
        $query = $db->createQuery()
            ->delete($db->quoteName('#__facileforms_subrecords'))
            ->where('NOT EXISTS (' . $elements . ')')
            ->where('NOT EXISTS (' . $records . ')');

        if ($elementId !== null) {
            $query->where($db->quoteName('element') . ' = :elementId')
                ->bind(':elementId', $elementId, ParameterType::INTEGER);
        }

        $db->setQuery($query)->execute();

        return $db->getAffectedRows();
    }

    /**
     * Permanently deletes every stored value of ONE missing element, including
     * the values of records that still exist. Irreversible; the caller must have
     * obtained an explicit acknowledgement. Never applies to an element that
     * still exists, and never to all missing elements at once.
     *
     * @return int The number of values deleted.
     */
    public function deleteOrphanValuesOfRecords(int $elementId): int
    {
        if ($elementId < 1) {
            throw new \InvalidArgumentException('Invalid element ID.');
        }

        $db = $this->db;
        $elements = $db->createQuery()
            ->select('1')
            ->from($db->quoteName('#__facileforms_elements', 'elements'))
            ->where($db->quoteName('elements.id') . ' = ' . $db->quoteName('#__facileforms_subrecords.element'));
        $query = $db->createQuery()
            ->delete($db->quoteName('#__facileforms_subrecords'))
            ->where($db->quoteName('element') . ' = :elementId')
            ->where('NOT EXISTS (' . $elements . ')')
            ->bind(':elementId', $elementId, ParameterType::INTEGER);
        $db->setQuery($query)->execute();

        return $db->getAffectedRows();
    }

    /**
     * Reassigns the values of missing elements to the current element of the
     * same form that has the same (unique) name, unless that element already
     * holds a value for the same record.
     *
     * @param ?int $elementId One missing element, or null for all of them.
     *
     * @return int The number of values reassigned.
     */
    public function reassignOrphanValues(?int $elementId = null): int
    {
        if ($elementId !== null && $elementId < 1) {
            throw new \InvalidArgumentException('Invalid element ID.');
        }

        $db = $this->db;
        $query = OrphanValueMatcher::query($db, $elementId === null ? null : [$elementId])
            ->select([
                $db->quoteName('s.id', 'sub_id'),
                $db->quoteName('s.record', 'record_id'),
                $db->quoteName('s.element', 'old_id'),
                $db->quoteName('e.id', 'target_id'),
            ])
            ->where('x.id IS NULL')
            ->order($db->quoteName('s.id') . ' ASC');
        $byTarget = [];
        $taken = [];

        foreach ($db->setQuery($query)->loadAssocList() as $row) {
            $slot = (int) $row['record_id'] . ':' . (int) $row['target_id'];

            // A multi-valued field has several rows per record and element: they move together.
            // Two different missing elements may carry the same name: the first one wins the target.
            if (($taken[$slot] ?? (int) $row['old_id']) !== (int) $row['old_id']) {
                continue;
            }

            $taken[$slot] = (int) $row['old_id'];
            $byTarget[(int) $row['target_id']][] = (int) $row['sub_id'];
        }

        $reassigned = 0;
        $db->transactionStart();

        try {
            foreach ($byTarget as $targetId => $subIds) {
                foreach (array_chunk($subIds, 500) as $chunk) {
                    $update = $db->createQuery()
                        ->update($db->quoteName('#__facileforms_subrecords'))
                        ->set($db->quoteName('element') . ' = :target')
                        ->bind(':target', $targetId, ParameterType::INTEGER)
                        ->whereIn($db->quoteName('id'), $chunk);
                    $db->setQuery($update)->execute();
                    $reassigned += $db->getAffectedRows();
                }
            }

            $db->transactionCommit();
        } catch (\Throwable $exception) {
            $db->transactionRollback();

            throw $exception;
        }

        return $reassigned;
    }

    /**
     * Reassigns the values of one missing element to a field chosen by the
     * administrator. The field must belong to the form of the records and have
     * a type compatible with the stored values (OrphanValueMatcher); records that already hold a value
     * for it are left unchanged, except that an orphan value strictly identical
     * to one the record already holds for it is a duplicate and is deleted.
     *
     * @param int $identicalRemoved Receives the number of identical duplicates deleted.
     * @param ?list<int> $selectedValueIds Only these values (those the administrator checked); null for all.
     *
     * @return int The number of values reassigned.
     */
    public function reassignOrphanValuesToElement(
        int $oldElementId,
        int $targetElementId,
        int &$identicalRemoved = 0,
        ?array $selectedValueIds = null
    ): int {
        if ($oldElementId < 1 || $targetElementId < 1) {
            throw new \InvalidArgumentException('Invalid element ID.');
        }

        $db = $this->db;
        $target = $db->setQuery(
            $db->createQuery()
                ->select($db->quoteName(['id', 'form', 'type']))
                ->from($db->quoteName('#__facileforms_elements'))
                ->where($db->quoteName('id') . ' = :id')
                ->bind(':id', $targetElementId, ParameterType::INTEGER)
        )->loadAssoc();

        if (!is_array($target)) {
            throw new \InvalidArgumentException('The target field does not exist.');
        }

        $targetForm = (int) $target['form'];
        $targetClass = OrphanValueMatcher::typeClass((string) $target['type']);
        $query = $db->createQuery()
            ->select($db->quoteName('s.id'))
            ->from($db->quoteName('#__facileforms_subrecords', 's'))
            ->join('INNER', $db->quoteName('#__facileforms_records', 'r') . ' ON r.id = s.record')
            ->join('LEFT', $db->quoteName('#__facileforms_elements', 'o') . ' ON o.id = s.element')
            ->join(
                'LEFT',
                $db->quoteName('#__facileforms_subrecords', 'x') . ' ON x.record = s.record AND x.element = :target'
            )
            ->where('o.id IS NULL')
            ->where('x.id IS NULL')
            ->where($db->quoteName('s.element') . ' = :old')
            ->where($db->quoteName('r.form') . ' = :form')
            ->where(OrphanValueMatcher::typeClassSql($db, 's.type') . ' = :class')
            ->bind(':target', $targetElementId, ParameterType::INTEGER)
            ->bind(':old', $oldElementId, ParameterType::INTEGER)
            ->bind(':form', $targetForm, ParameterType::INTEGER)
            ->bind(':class', $targetClass);
        $subIds = array_map('intval', $db->setQuery($query)->loadColumn() ?: []);

        // Orphan values the target already holds, byte for byte (a different
        // value is never touched): they are duplicates, nothing is lost.
        $duplicateQuery = $db->createQuery()
            ->select($db->quoteName('s.id'))
            ->from($db->quoteName('#__facileforms_subrecords', 's'))
            ->join('INNER', $db->quoteName('#__facileforms_records', 'r') . ' ON r.id = s.record')
            ->join('LEFT', $db->quoteName('#__facileforms_elements', 'o') . ' ON o.id = s.element')
            ->join(
                'INNER',
                $db->quoteName('#__facileforms_subrecords', 'x')
                    . ' ON x.record = s.record AND x.element = :target AND BINARY x.value = BINARY s.value'
            )
            ->where('o.id IS NULL')
            ->where($db->quoteName('s.element') . ' = :old')
            ->where($db->quoteName('r.form') . ' = :form')
            ->where(OrphanValueMatcher::typeClassSql($db, 's.type') . ' = :class')
            ->group($db->quoteName('s.id'))
            ->bind(':target', $targetElementId, ParameterType::INTEGER)
            ->bind(':old', $oldElementId, ParameterType::INTEGER)
            ->bind(':form', $targetForm, ParameterType::INTEGER)
            ->bind(':class', $targetClass);
        $duplicateIds = array_map('intval', $db->setQuery($duplicateQuery)->loadColumn() ?: []);

        if ($selectedValueIds !== null) {
            $selectedValueIds = array_map('intval', $selectedValueIds);
            $subIds = array_values(array_intersect($subIds, $selectedValueIds));
            $duplicateIds = array_values(array_intersect($duplicateIds, $selectedValueIds));
        }

        $reassigned = 0;
        $identicalRemoved = 0;
        $db->transactionStart();

        try {
            foreach (array_chunk($duplicateIds, 500) as $chunk) {
                $delete = $db->createQuery()
                    ->delete($db->quoteName('#__facileforms_subrecords'))
                    ->whereIn($db->quoteName('id'), $chunk);
                $db->setQuery($delete)->execute();
                $identicalRemoved += $db->getAffectedRows();
            }

            foreach (array_chunk($subIds, 500) as $chunk) {
                $update = $db->createQuery()
                    ->update($db->quoteName('#__facileforms_subrecords'))
                    ->set($db->quoteName('element') . ' = :newElement')
                    ->bind(':newElement', $targetElementId, ParameterType::INTEGER)
                    ->whereIn($db->quoteName('id'), $chunk);
                $db->setQuery($update)->execute();
                $reassigned += $db->getAffectedRows();
            }

            $db->transactionCommit();
        } catch (\Throwable $exception) {
            $db->transactionRollback();

            throw $exception;
        }

        return $reassigned;
    }

    /**
     * What reassigning the values of a missing element to a chosen element would
     * do, value by value, so the administrator can check them before confirming.
     * The same rules as reassignOrphanValuesToElement(): same form and type; a
     * value is moved when the record holds nothing for the target, deleted as a
     * duplicate when it is strictly identical to one the record holds, and left
     * alone otherwise.
     *
     * @return array{from: string, to: string, truncated: bool, rows: list<array{id: int, record: int, value: string, current: string, action: string}>}
     */
    public function previewOrphanReassignment(int $oldElementId, int $targetElementId, int $limit = 500): array
    {
        if ($oldElementId < 1 || $targetElementId < 1) {
            throw new \InvalidArgumentException('Invalid element ID.');
        }

        $db = $this->db;
        $target = $db->setQuery(
            $db->createQuery()
                ->select($db->quoteName(['id', 'form', 'type', 'name']))
                ->from($db->quoteName('#__facileforms_elements'))
                ->where($db->quoteName('id') . ' = :id')
                ->bind(':id', $targetElementId, ParameterType::INTEGER)
        )->loadAssoc();

        if (!is_array($target)) {
            throw new \InvalidArgumentException('The target field does not exist.');
        }

        $targetForm = (int) $target['form'];
        $targetClass = OrphanValueMatcher::typeClass((string) $target['type']);
        $query = $db->createQuery()
            ->select($db->quoteName(['s.id', 's.record', 's.name', 's.value']))
            ->from($db->quoteName('#__facileforms_subrecords', 's'))
            ->join('INNER', $db->quoteName('#__facileforms_records', 'r') . ' ON r.id = s.record')
            ->join('LEFT', $db->quoteName('#__facileforms_elements', 'o') . ' ON o.id = s.element')
            ->where('o.id IS NULL')
            ->where($db->quoteName('s.element') . ' = :old')
            ->where($db->quoteName('r.form') . ' = :form')
            ->where(OrphanValueMatcher::typeClassSql($db, 's.type') . ' = :class')
            ->order($db->quoteName('s.record') . ' ASC, ' . $db->quoteName('s.id') . ' ASC')
            ->bind(':old', $oldElementId, ParameterType::INTEGER)
            ->bind(':form', $targetForm, ParameterType::INTEGER)
            ->bind(':class', $targetClass);
        $values = $db->setQuery($query, 0, $limit + 1)->loadAssocList();
        $truncated = count($values) > $limit;
        $values = array_slice($values, 0, $limit);
        $records = array_values(array_unique(array_map(static fn (array $row): int => (int) $row['record'], $values)));
        $held = [];

        if ($records !== []) {
            $heldQuery = $db->createQuery()
                ->select($db->quoteName(['record', 'value']))
                ->from($db->quoteName('#__facileforms_subrecords'))
                ->where($db->quoteName('element') . ' = :target')
                ->whereIn($db->quoteName('record'), $records)
                ->bind(':target', $targetElementId, ParameterType::INTEGER);

            foreach ($db->setQuery($heldQuery)->loadAssocList() as $row) {
                $held[(int) $row['record']][] = (string) $row['value'];
            }
        }

        $rows = [];
        $from = '';

        foreach ($values as $row) {
            $from = $from !== '' ? $from : (string) $row['name'];
            $record = (int) $row['record'];
            $value = (string) $row['value'];
            $action = match (true) {
                !isset($held[$record]) => 'move',
                in_array($value, $held[$record], true) => 'duplicate',
                default => 'taken',
            };
            $current = implode("\n", $held[$record] ?? []);
            $rows[] = [
                'id' => (int) $row['id'],
                'record' => $record,
                'value' => mb_strlen($value) > 160 ? mb_substr($value, 0, 160) . '…' : $value,
                // What the target element already holds for this record, if anything.
                'current' => mb_strlen($current) > 160 ? mb_substr($current, 0, 160) . '…' : $current,
                'action' => $action,
            ];
        }

        return ['from' => $from, 'to' => (string) $target['name'], 'truncated' => $truncated, 'rows' => $rows];
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

    /**
     * Uninstall leftover pre-NG extensions selected from a fresh audit.
     *
     * Only ids currently reported as legacy are accepted. The Joomla installer
     * is used first; when its files and manifest are already gone and it
     * cannot uninstall, the orphan registration (and template styles) is removed.
     *
     * @param array<mixed> $selectedIds
     * @return array{selected:int, removed:int, failed:int, errors:array<int,string>}
     */
    public function removeLegacyExtensions(array $selectedIds): array
    {
        $selectedIds = array_values(array_unique(array_filter(
            array_map('intval', $selectedIds),
            static fn(int $id): bool => $id > 0
        )));
        $report = (new DatabaseAuditService($this->db, $this->temporaryPath))->run();
        $legacy = [];

        foreach ((array) ($report['extension_legacy'] ?? []) as $entry) {
            $legacy[(int) ($entry['extension_id'] ?? 0)] = (array) $entry;
        }

        $errors = [];
        $removed = 0;
        $selected = array_values(array_intersect($selectedIds, array_keys($legacy)));

        foreach ($selected as $extensionId) {
            $entry = $legacy[$extensionId];
            $label = $entry['type'] . '/' . $entry['element'] . ' (#' . $extensionId . ')';

            try {
                $uninstalled = false;

                try {
                    $uninstalled = (bool) Installer::getInstance()->uninstall((string) $entry['type'], $extensionId);
                } catch (\Throwable) {
                    $uninstalled = false;
                }

                if (!$uninstalled && $this->extensionRowExists($extensionId)) {
                    // Only a registration whose files are really gone may be dropped;
                    // any other installer failure is reported and the extension is kept.
                    if (!$this->isOrphanRegistration($entry)) {
                        throw new \RuntimeException('The installer failed and the extension files still exist; nothing was removed.');
                    }

                    $this->deleteExtensionRow($extensionId, $entry);
                }

                if ($this->extensionRowExists($extensionId)) {
                    throw new \RuntimeException('The extension is still registered.');
                }

                $removed++;
            } catch (\Throwable $exception) {
                $errors[] = $label . ': ' . $exception->getMessage();
            }
        }

        return [
            'selected' => count($selected),
            'removed' => $removed,
            'failed' => count($errors),
            'errors' => $errors,
        ];
    }

    /**
     * True when no file of the registered extension exists any more. Types
     * whose location cannot be determined are never considered orphan.
     *
     * @param array<string, mixed> $entry
     */
    private function isOrphanRegistration(array $entry): bool
    {
        $type = (string) ($entry['type'] ?? '');
        $element = (string) ($entry['element'] ?? '');
        $folder = (string) ($entry['folder'] ?? '');
        $base = (int) ($entry['client_id'] ?? 0) === 1 ? JPATH_ADMINISTRATOR : JPATH_SITE;

        if ($element === '' || preg_match('/^[A-Za-z0-9_.-]+$/', $element) !== 1 || preg_match('/^[A-Za-z0-9_.-]*$/', $folder) !== 1) {
            return false;
        }

        $paths = match ($type) {
            'component' => [JPATH_ADMINISTRATOR . '/components/' . $element, JPATH_SITE . '/components/' . $element],
            'plugin' => [JPATH_PLUGINS . '/' . $folder . '/' . $element],
            'module' => [$base . '/modules/' . $element],
            'template' => [$base . '/templates/' . $element],
            'library' => [JPATH_LIBRARIES . '/' . $element],
            default => null,
        };

        if ($paths === null) {
            return false;
        }

        foreach ($paths as $path) {
            if (file_exists($path)) {
                return false;
            }
        }

        return true;
    }

    private function extensionRowExists(int $extensionId): bool
    {
        $query = $this->db->createQuery()
            ->select('COUNT(*)')
            ->from($this->db->quoteName('#__extensions'))
            ->where($this->db->quoteName('extension_id') . ' = :id')
            ->bind(':id', $extensionId, ParameterType::INTEGER);

        return (int) $this->db->setQuery($query)->loadResult() > 0;
    }

    /**
     * @param array<string, mixed> $entry
     */
    private function deleteExtensionRow(int $extensionId, array $entry): void
    {
        if ((string) ($entry['type'] ?? '') === 'template') {
            $this->deleteTemplateStyles((string) ($entry['element'] ?? ''), (int) ($entry['client_id'] ?? 0));
        }

        $query = $this->db->createQuery()
            ->delete($this->db->quoteName('#__extensions'))
            ->where($this->db->quoteName('extension_id') . ' = :id')
            ->bind(':id', $extensionId, ParameterType::INTEGER);
        $this->db->setQuery($query)->execute();
    }

    /**
     * Removes the styles of a template that can no longer be uninstalled
     * normally. Like the Joomla installer, it never leaves a client without a
     * default style: the default of the removed template passes to a core
     * style (Atum / Cassiopeia), or the removal is refused.
     */
    private function deleteTemplateStyles(string $template, int $client): void
    {
        $db = $this->db;
        $styles = $db->setQuery(
            $db->createQuery()
                ->select($db->quoteName(['id', 'home']))
                ->from($db->quoteName('#__template_styles'))
                ->where($db->quoteName('template') . ' = :template')
                ->where($db->quoteName('client_id') . ' = :client')
                ->bind(':template', $template)
                ->bind(':client', $client, ParameterType::INTEGER)
        )->loadAssocList();

        if ($styles === []) {
            return;
        }

        $styleIds = array_map(static fn (array $style): int => (int) $style['id'], $styles);
        $homes = array_values(array_unique(array_filter(
            array_map(static fn (array $style): string => (string) $style['home'], $styles),
            static fn (string $home): bool => $home !== '0' && $home !== ''
        )));

        if (count($homes) > 1) {
            throw new \RuntimeException('The styles of this template are the default of several languages.');
        }

        $db->transactionStart();

        try {
            if ($homes !== []) {
                $coreTemplate = $client === 1 ? 'atum' : 'cassiopeia';
                $heir = (int) $db->setQuery(
                    $db->createQuery()
                        ->select('MIN(' . $db->quoteName('id') . ')')
                        ->from($db->quoteName('#__template_styles'))
                        ->where($db->quoteName('template') . ' = :core')
                        ->where($db->quoteName('client_id') . ' = :client')
                        ->where($db->quoteName('home') . ' = ' . $db->quote('0'))
                        ->bind(':core', $coreTemplate)
                        ->bind(':client', $client, ParameterType::INTEGER)
                )->loadResult();

                if ($heir < 1) {
                    throw new \RuntimeException('No ' . $coreTemplate . ' style is available to become the default.');
                }

                $home = $homes[0];
                $db->setQuery(
                    $db->createQuery()
                        ->update($db->quoteName('#__template_styles'))
                        ->set($db->quoteName('home') . ' = :home')
                        ->where($db->quoteName('id') . ' = :id')
                        ->bind(':home', $home)
                        ->bind(':id', $heir, ParameterType::INTEGER)
                )->execute();
            }

            // Menu items pointing at the removed styles fall back to the default style.
            $db->setQuery(
                $db->createQuery()
                    ->update($db->quoteName('#__menu'))
                    ->set($db->quoteName('template_style_id') . ' = 0')
                    ->whereIn($db->quoteName('template_style_id'), $styleIds)
            )->execute();
            $db->setQuery(
                $db->createQuery()
                    ->delete($db->quoteName('#__template_styles'))
                    ->whereIn($db->quoteName('id'), $styleIds)
            )->execute();
            $db->transactionCommit();
        } catch (\Throwable $exception) {
            $db->transactionRollback();

            throw $exception;
        }
    }
}
