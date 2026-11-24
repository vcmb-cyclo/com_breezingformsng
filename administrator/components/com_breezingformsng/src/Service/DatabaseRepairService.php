<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Vcmb\Component\BreezingformsNG\Administrator\Service;

\defined('_JEXEC') or die;

use Joomla\CMS\Installer\Installer;
use Joomla\CMS\Language\Text;
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

    /** Remove only the standalone legacy table after a fresh audit confirms it. */
    public function deleteLegacyBreezingformsTable(): void
    {
        $alias = '#__breezingforms';
        $report = (new DatabaseAuditService($this->db, $this->temporaryPath))->run();
        $physical = $this->db->getPrefix() . 'breezingforms';

        if (!in_array($alias, (array) ($report['unexpected_tables'] ?? []), true)
            || !in_array($physical, $this->db->getTableList(), true)) {
            throw new \RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));
        }

        $this->db->setQuery('DROP TABLE ' . $this->db->quoteName($physical));
        $this->db->execute();
    }

    /**
     * @param array<string, mixed> $group
     */
    public static function getDuplicateIndexSelectionToken(array $group): string
    {
        $drop = array_values(array_map(
            static fn(mixed $value): string => trim(self::stringValue($value)),
            (array) ($group['drop'] ?? [])
        ));

        return hash('sha256', implode("\0", [
            trim(self::stringValue($group['table'] ?? '')),
            trim(self::stringValue($group['keep'] ?? '')),
            implode("\0", $drop),
        ]));
    }

    /**
     * @param array<string, mixed> $issue
     */
    public static function getColumnCollationSelectionToken(array $issue): string
    {
        return hash('sha256', implode("\0", [
            trim(self::stringValue($issue['table'] ?? '')),
            trim(self::stringValue($issue['column'] ?? '')),
            trim(self::stringValue($issue['charset'] ?? '')),
            trim(self::stringValue($issue['collation'] ?? '')),
            trim(self::stringValue($issue['expected_charset'] ?? '')),
            trim(self::stringValue($issue['expected'] ?? '')),
        ]));
    }

    /**
     * @param array<string, mixed> $issue
     */
    public static function getTableCollationSelectionToken(array $issue): string
    {
        return hash('sha256', implode("\0", [
            trim(self::stringValue($issue['table'] ?? '')),
            trim(self::stringValue($issue['collation'] ?? '')),
            trim(self::stringValue($issue['expected'] ?? '')),
        ]));
    }

    /**
     * @param array<mixed> $selectedTokens
     * @return array{selected_tables:int, repaired_tables:int, failed_tables:int, errors:array<int,string>}
     */
    public function repairTableCollations(array $selectedTokens): array
    {
        $selectedTokens = array_values(array_unique(array_filter(
            array_map(static fn(mixed $value): string => trim(self::stringValue($value)), $selectedTokens),
            static fn(string $value): bool => $value !== ''
        )));
        $report = (new DatabaseAuditService($this->db, $this->temporaryPath))->run();
        $selectedIssues = array_values(array_filter(
            self::assocRows($report['collation_issues'] ?? []),
            static fn(array $issue): bool => in_array(self::getTableCollationSelectionToken($issue), $selectedTokens, true)
        ));
        $errors = [];
        $repairedTables = 0;

        foreach ($selectedIssues as $issue) {
            $tableAlias = trim(self::stringValue($issue['table'] ?? ''));
            $targetCollation = trim(self::stringValue($issue['expected'] ?? ''));
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
            array_map(static fn(mixed $value): string => trim(self::stringValue($value)), $selectedTokens),
            static fn(string $value): bool => $value !== ''
        )));
        $report = (new DatabaseAuditService($this->db, $this->temporaryPath))->run();
        $issues = self::assocRows($report['column_collation_issues'] ?? []);
        $selectedIssues = array_values(array_filter(
            $issues,
            static fn(array $issue): bool => in_array(self::getColumnCollationSelectionToken($issue), $selectedTokens, true)
        ));
        $errors = [];
        $repairedColumns = 0;

        foreach ($selectedIssues as $issue) {
            $tableAlias = trim(self::stringValue($issue['table'] ?? ''));
            $columnName = trim(self::stringValue($issue['column'] ?? ''));
            $targetCharset = trim(self::stringValue($issue['expected_charset'] ?? ''));
            $targetCollation = trim(self::stringValue($issue['expected'] ?? ''));
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
                $definition = $this->loadAssocRow();
                $type = trim(self::stringValue($definition['Type'] ?? ''));

                if ($type === '') {
                    throw new \RuntimeException('Column not found.');
                }

                $sql = 'ALTER TABLE ' . $this->db->quoteName($tableName)
                    . ' MODIFY COLUMN ' . $this->db->quoteName($columnName)
                    . ' ' . $type
                    . ' CHARACTER SET ' . $targetCharset
                    . ' COLLATE ' . $targetCollation
                    . (self::stringValue($definition['Null'] ?? '') === 'YES' ? ' NULL' : ' NOT NULL');

                if (array_key_exists('Default', $definition) && $definition['Default'] !== null) {
                    $sql .= ' DEFAULT ' . $this->db->quote(self::stringValue($definition['Default']));
                } elseif (self::stringValue($definition['Null'] ?? '') === 'YES') {
                    $sql .= ' DEFAULT NULL';
                }

                if (self::stringValue($definition['Comment'] ?? '') !== '') {
                    $sql .= ' COMMENT ' . $this->db->quote(self::stringValue($definition['Comment']));
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
            array_map(static fn(mixed $value): string => trim(self::stringValue($value)), $selectedTokens),
            static fn(string $value): bool => $value !== ''
        )));
        $report = (new DatabaseAuditService($this->db, $this->temporaryPath))->run();
        $groups = self::assocRows($report['duplicate_indexes'] ?? []);
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
            $tableAlias = trim(self::stringValue($group['table'] ?? ''));
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
                $indexName = trim(self::stringValue($indexName));

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
     * @param ?list<int|string> $formIds The missing forms, or null for all of them.
     *
     * @return int The number of elements deleted.
     */
    public function deleteOrphanElements(?array $formIds = null): int
    {
        if ($formIds !== null) {
            $formIds = self::missingParentIds($formIds);
        }

        $db = $this->db;
        $forms = $db->createQuery()
            ->select('1')
            ->from($db->quoteName('#__facileforms_forms', 'forms'))
            ->where($db->quoteName('forms.id') . ' = ' . $db->quoteName('#__facileforms_elements.form'));
        $query = $db->createQuery()
            ->delete($db->quoteName('#__facileforms_elements'))
            ->where('NOT EXISTS (' . $forms . ')');

        if ($formIds !== null) {
            $query->whereIn($db->quoteName('form'), $formIds);
        }

        $db->setQuery($query)->execute();

        return $db->getAffectedRows();
    }

    /**
     * Deletes the records of forms that no longer exist, with their values. The records of a form that exists
     * are never touched, and nothing is deleted without the forms being named.
     *
     * @param list<int|string> $formIds The missing forms (their ids are the "form" of the records).
     *
     * @return int The number of records deleted.
     */
    public function deleteOrphanRecords(array $formIds): int
    {
        $formIds = self::missingParentIds($formIds);

        if ($formIds === []) {
            throw new \InvalidArgumentException('No form was named.');
        }

        $db = $this->db;
        $forms = $db->createQuery()
            ->select('1')
            ->from($db->quoteName('#__facileforms_forms', 'forms'))
            ->where($db->quoteName('forms.id') . ' = ' . $db->quoteName('records.form'));
        $orphans = $db->createQuery()
            ->select($db->quoteName('records.id'))
            ->from($db->quoteName('#__facileforms_records', 'records'))
            // The ids are integers (checked above): written in the query, because a subquery put inside another
            // one keeps none of its bound values.
            ->where($db->quoteName('records.form') . ' IN (' . implode(',', $formIds) . ')')
            ->where('NOT EXISTS (' . $forms . ')');
        $db->transactionStart();

        try {
            $db->setQuery(
                $db->createQuery()
                    ->delete($db->quoteName('#__facileforms_subrecords'))
                    ->where($db->quoteName('record') . ' IN (' . $orphans . ')')
            )->execute();

            $formsOfRecords = $db->createQuery()
                ->select('1')
                ->from($db->quoteName('#__facileforms_forms', 'forms'))
                ->where($db->quoteName('forms.id') . ' = ' . $db->quoteName('#__facileforms_records.form'));
            $db->setQuery(
                $db->createQuery()
                    ->delete($db->quoteName('#__facileforms_records'))
                    ->whereIn($db->quoteName('form'), $formIds)
                    ->where('NOT EXISTS (' . $formsOfRecords . ')')
            )->execute();
            $deleted = $db->getAffectedRows();
            $db->transactionCommit();
        } catch (\Throwable $exception) {
            $db->transactionRollback();
            throw $exception;
        }

        return $deleted;
    }

    /**
     * @param list<int|string> $ids
     *
     * @return list<int>
     */
    private static function missingParentIds(array $ids): array
    {
        $clean = [];

        foreach ($ids as $id) {
            if (!is_int($id) && !(is_string($id) && preg_match('/\A[0-9]+\z/', $id) === 1)) {
                throw new \InvalidArgumentException('Invalid form ID.');
            }

            $clean[(int) $id] = (int) $id;
        }

        return array_values($clean);
    }

    /**
     * Deletes the values (subrecords) whose record no longer exists: nothing can show them any more.
     * Values of an existing record are never touched.
     *
     * @param ?int $recordId One missing record, or null for all of them.
     *
     * @return int The number of values deleted.
     */
    public function deleteOrphanSubrecords(?int $recordId = null): int
    {
        if ($recordId !== null && $recordId < 1) {
            throw new \InvalidArgumentException('Invalid record ID.');
        }

        $db = $this->db;
        $records = $db->createQuery()
            ->select('1')
            ->from($db->quoteName('#__facileforms_records', 'records'))
            ->where($db->quoteName('records.id') . ' = ' . $db->quoteName('#__facileforms_subrecords.record'));
        $query = $db->createQuery()
            ->delete($db->quoteName('#__facileforms_subrecords'))
            ->where('NOT EXISTS (' . $records . ')');

        if ($recordId !== null) {
            $query->where($db->quoteName('record') . ' = :recordId')
                ->bind(':recordId', $recordId, ParameterType::INTEGER);
        }

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

        $db->setQuery($query);
        foreach ($this->loadAssocRows() as $row) {
            $recordId = self::intValue($row['record_id'] ?? 0);
            $targetId = self::intValue($row['target_id'] ?? 0);
            $oldId = self::intValue($row['old_id'] ?? 0);
            $slot = $recordId . ':' . $targetId;

            // A multi-valued field has several rows per record and element: they move together.
            // Two different missing elements may carry the same name: the first one wins the target.
            if (($taken[$slot] ?? $oldId) !== $oldId) {
                continue;
            }

            $taken[$slot] = $oldId;
            $byTarget[$targetId][] = self::intValue($row['sub_id'] ?? 0);
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
        $db->setQuery(
            $db->createQuery()
                ->select($db->quoteName(['id', 'form', 'type']))
                ->from($db->quoteName('#__facileforms_elements'))
                ->where($db->quoteName('id') . ' = :id')
                ->bind(':id', $targetElementId, ParameterType::INTEGER)
        );
        $target = $this->loadAssocRow();

        $targetForm = self::intValue($target['form'] ?? 0);
        $targetClass = OrphanValueMatcher::typeClass(self::stringValue($target['type'] ?? ''));
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
        $db->setQuery($query);
        $subIds = $this->intList($db->loadColumn());

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
        $db->setQuery($duplicateQuery);
        $duplicateIds = $this->intList($db->loadColumn());

        if ($selectedValueIds !== null) {
            $selectedValueIds = $this->intList($selectedValueIds);
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
        $db->setQuery(
            $db->createQuery()
                ->select($db->quoteName(['id', 'form', 'type', 'name']))
                ->from($db->quoteName('#__facileforms_elements'))
                ->where($db->quoteName('id') . ' = :id')
                ->bind(':id', $targetElementId, ParameterType::INTEGER)
        );
        $target = $this->loadAssocRow();

        $targetForm = self::intValue($target['form'] ?? 0);
        $targetClass = OrphanValueMatcher::typeClass(self::stringValue($target['type'] ?? ''));
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
        $db->setQuery($query, 0, $limit + 1);
        $values = $this->loadAssocRows();
        $truncated = count($values) > $limit;
        $values = array_slice($values, 0, $limit);
        $records = array_values(array_unique(array_map(
            static fn (array $row): int => self::intValue($row['record'] ?? 0),
            $values
        )));
        $held = [];

        if ($records !== []) {
            $heldQuery = $db->createQuery()
                ->select($db->quoteName(['record', 'value']))
                ->from($db->quoteName('#__facileforms_subrecords'))
                ->where($db->quoteName('element') . ' = :target')
                ->whereIn($db->quoteName('record'), $records)
                ->bind(':target', $targetElementId, ParameterType::INTEGER);

            $db->setQuery($heldQuery);
            foreach ($this->loadAssocRows() as $row) {
                $held[self::intValue($row['record'] ?? 0)][] = self::stringValue($row['value'] ?? '');
            }
        }

        $rows = [];
        $from = '';

        foreach ($values as $row) {
            $from = $from !== '' ? $from : self::stringValue($row['name'] ?? '');
            $record = self::intValue($row['record'] ?? 0);
            $value = self::stringValue($row['value'] ?? '');
            $action = match (true) {
                !isset($held[$record]) => 'move',
                in_array($value, $held[$record], true) => 'duplicate',
                default => 'taken',
            };
            $current = implode("\n", $held[$record] ?? []);
            $rows[] = [
                'id' => self::intValue($row['id'] ?? 0),
                'record' => $record,
                'value' => mb_strlen($value) > 160 ? mb_substr($value, 0, 160) . '…' : $value,
                // What the target element already holds for this record, if anything.
                'current' => mb_strlen($current) > 160 ? mb_substr($current, 0, 160) . '…' : $current,
                'action' => $action,
            ];
        }

        return ['from' => $from, 'to' => self::stringValue($target['name'] ?? ''), 'truncated' => $truncated, 'rows' => $rows];
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
            array_map(static fn(mixed $value): string => trim(self::stringValue($value)), $selectedPaths),
            static fn(string $value): bool => $value !== ''
        )));
        $report = (new DatabaseAuditService($this->db, $this->temporaryPath))->run();
        $stalePaths = array_values(array_filter(
            array_map(
                static fn(mixed $value): string => realpath(self::stringValue($value)) ?: '',
                is_array($report['stale_installer_temp_dirs'] ?? null) ? $report['stale_installer_temp_dirs'] : []
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
            self::intList($selectedIds),
            static fn(int $id): bool => $id > 0
        )));
        $report = (new DatabaseAuditService($this->db, $this->temporaryPath))->run();
        $legacy = [];

        foreach (self::assocRows($report['extension_legacy'] ?? []) as $entry) {
            $legacy[self::intValue($entry['extension_id'] ?? 0)] = $entry;
        }

        $errors = [];
        $removed = 0;
        $selected = array_values(array_intersect($selectedIds, array_keys($legacy)));

        foreach ($selected as $extensionId) {
            $entry = $legacy[$extensionId];
            $label = self::stringValue($entry['type'] ?? '') . '/' . self::stringValue($entry['element'] ?? '') . ' (#' . $extensionId . ')';

            try {
                $uninstalled = false;

                try {
                    $uninstalled = (bool) Installer::getInstance()->uninstall(self::stringValue($entry['type'] ?? ''), $extensionId);
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
        $type = self::stringValue($entry['type'] ?? '');
        $element = self::stringValue($entry['element'] ?? '');
        $folder = self::stringValue($entry['folder'] ?? '');
        $base = self::intValue($entry['client_id'] ?? 0) === 1 ? JPATH_ADMINISTRATOR : JPATH_SITE;

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

        return self::intValue($this->db->setQuery($query)->loadResult()) > 0;
    }

    /**
     * @param array<string, mixed> $entry
     */
    private function deleteExtensionRow(int $extensionId, array $entry): void
    {
        if (self::stringValue($entry['type'] ?? '') === 'template') {
            $this->deleteTemplateStyles(self::stringValue($entry['element'] ?? ''), self::intValue($entry['client_id'] ?? 0));
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
        $db->setQuery(
            $db->createQuery()
                ->select($db->quoteName(['id', 'home']))
                ->from($db->quoteName('#__template_styles'))
                ->where($db->quoteName('template') . ' = :template')
                ->where($db->quoteName('client_id') . ' = :client')
                ->bind(':template', $template)
                ->bind(':client', $client, ParameterType::INTEGER)
        );
        $styles = $this->loadAssocRows();

        if ($styles === []) {
            return;
        }

        $styleIds = array_map(
            static fn (array $style): int => self::intValue($style['id'] ?? 0),
            $styles
        );
        $homes = array_values(array_unique(array_filter(
            array_map(static fn (array $style): string => self::stringValue($style['home'] ?? ''), $styles),
            static fn (string $home): bool => $home !== '0' && $home !== ''
        )));

        if (count($homes) > 1) {
            throw new \RuntimeException('The styles of this template are the default of several languages.');
        }

        $db->transactionStart();

        try {
            if ($homes !== []) {
                $coreTemplate = $client === 1 ? 'atum' : 'cassiopeia';
                $coreTemplateForQuery = $coreTemplate;
                $heir = self::intValue($db->setQuery(
                    $db->createQuery()
                        ->select('MIN(' . $db->quoteName('id') . ')')
                        ->from($db->quoteName('#__template_styles'))
                        ->where($db->quoteName('template') . ' = :core')
                        ->where($db->quoteName('client_id') . ' = :client')
                        ->where($db->quoteName('home') . ' = ' . $db->quote('0'))
                        ->bind(':core', $coreTemplateForQuery)
                        ->bind(':client', $client, ParameterType::INTEGER)
                )->loadResult());

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

    /**
     * @param mixed $rows
     * @return list<array<string, mixed>>
     */
    private static function assocRows(mixed $rows): array
    {
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
     * @return array<string, mixed>
     */
    private function loadAssocRow(): array
    {
        $row = $this->db->loadAssoc();

        return is_array($row) ? self::normalizeAssocRow($row) : [];
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
     * @param mixed $values
     * @return list<int>
     */
    private static function intList(mixed $values): array
    {
        if (!is_array($values)) {
            return [];
        }

        $result = [];
        foreach ($values as $value) {
            $result[] = self::intValue($value);
        }

        return $result;
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
}
