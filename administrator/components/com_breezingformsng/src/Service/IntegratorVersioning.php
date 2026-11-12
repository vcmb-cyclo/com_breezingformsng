<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Administrator\Service;

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\ContentHistory;
use Joomla\CMS\Versioning\Versioning;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/**
 * Versions of an Integrator rule, kept in the Joomla content history.
 *
 * A version holds the row of the rule and, as JSON texts, the rows of its
 * items and criteria, which a restore writes back.
 */
final class IntegratorVersioning
{
    public const TYPE_ALIAS = 'com_breezingformsng.integrator';

    private const RULES = '#__facileforms_integrator_rules';

    /** Columns a restore leaves as they are. */
    private const KEPT_COLUMNS = ['id', 'form_id', 'created', 'created_by', 'modified', 'modified_by'];

    /** Tables holding the rows of a rule, with their column naming an element. */
    private const CHILDREN = [
        'items' => ['#__facileforms_integrator_items', 'element_id'],
        'criteria_fixed' => ['#__facileforms_integrator_criteria_fixed', null],
        'criteria_form' => ['#__facileforms_integrator_criteria_form', 'element_id'],
        'criteria_joomla' => ['#__facileforms_integrator_criteria_joomla', null],
    ];

    public function __construct(private readonly DatabaseInterface $db)
    {
    }

    /**
     * Stores the current state of the rule, unless the same state is the
     * last version already.
     */
    public function record(int $ruleId, string $note = ''): void
    {
        if ($ruleId <= 0 || !FormVersioning::isEnabled()) {
            return;
        }

        $data = $this->snapshot($ruleId);

        if ($data === null) {
            return;
        }

        if (!Versioning::store(self::TYPE_ALIAS, $ruleId, $data, mb_substr($note, 0, 255))) {
            throw new \RuntimeException(Text::_('COM_BREEZINGFORMSNG_FORM_VERSION_NOT_STORED'));
        }
    }

    /**
     * Stores the current state of the rule as its version of origin, kept
     * forever, when it has no version yet.
     */
    public function keepOrigin(int $ruleId): void
    {
        if (!FormVersioning::isEnabled() || VersionOrigin::missing($this->db, self::TYPE_ALIAS, [$ruleId]) === []) {
            return;
        }

        $this->record($ruleId, Text::_('COM_BREEZINGFORMSNG_FORM_VERSION_NOTE_ORIGIN'));
        VersionOrigin::keepForever($this->db, self::TYPE_ALIAS . '.' . $ruleId);
    }

    public function deleteHistory(int $ruleId): void
    {
        Versioning::delete(self::TYPE_ALIAS, $ruleId);
    }

    /**
     * Puts back a version of a rule, its items and criteria included. Rows
     * naming an element the form no longer has are not restored.
     *
     * @return int The id of the restored rule
     */
    public function restore(int $versionId): int
    {
        $db = $this->db;
        $history = new ContentHistory($db);
        $prefix = self::TYPE_ALIAS . '.';

        if ($versionId <= 0 || !$history->load($versionId) || !str_starts_with((string) $history->item_id, $prefix)) {
            throw new \RuntimeException(Text::_('JLIB_APPLICATION_ERROR_NOT_EXIST'));
        }

        $ruleId = (int) substr((string) $history->item_id, \strlen($prefix));
        $data = json_decode((string) $history->version_data, true);
        $formId = (int) $db->setQuery(
            $db->createQuery()
                ->select($db->quoteName('form_id'))
                ->from($db->quoteName(self::RULES))
                ->where($db->quoteName('id') . ' = :id')
                ->bind(':id', $ruleId, ParameterType::INTEGER)
        )->loadResult();

        if ($ruleId <= 0 || $formId <= 0 || !\is_array($data)) {
            throw new \RuntimeException(Text::_('JLIB_APPLICATION_ERROR_NOT_EXIST'));
        }

        $db->transactionStart();

        try {
            $this->restoreRule($ruleId, $data);

            foreach (self::CHILDREN as $key => [$table, $elementColumn]) {
                $rows = json_decode((string) ($data[$key] ?? '[]'), true);
                $this->restoreRows($ruleId, $formId, $table, $elementColumn, \is_array($rows) ? $rows : []);
            }

            $db->transactionCommit();
        } catch (\Throwable $e) {
            $db->transactionRollback();

            throw $e;
        }

        return $ruleId;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function snapshot(int $ruleId): ?array
    {
        $db = $this->db;
        $rule = $db->setQuery(
            $db->createQuery()
                ->select('*')
                ->from($db->quoteName(self::RULES))
                ->where($db->quoteName('id') . ' = :id')
                ->bind(':id', $ruleId, ParameterType::INTEGER)
        )->loadAssoc();

        if (!\is_array($rule)) {
            return null;
        }

        $data = array_diff_key($rule, array_flip(self::KEPT_COLUMNS));

        foreach (self::CHILDREN as $key => [$table]) {
            $rows = $db->setQuery(
                $db->createQuery()
                    ->select('*')
                    ->from($db->quoteName($table))
                    ->where($db->quoteName('rule_id') . ' = :id')
                    ->order($db->quoteName('id'))
                    ->bind(':id', $ruleId, ParameterType::INTEGER)
            )->loadAssocList() ?: [];
            $data[$key] = json_encode(
                array_map(static fn (array $row): array => array_diff_key($row, ['id' => 1, 'rule_id' => 1]), $rows),
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
            );
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function restoreRule(int $ruleId, array $data): void
    {
        $db = $this->db;
        $existing = $db->getTableColumns(self::RULES);
        $query = $db->createQuery()->update($db->quoteName(self::RULES));

        foreach ($data as $column => $value) {
            if (
                \in_array($column, self::KEPT_COLUMNS, true) || !isset($existing[$column])
                || \array_key_exists($column, self::CHILDREN) || \is_array($value)
            ) {
                continue;
            }

            $query->set($db->quoteName($column) . ' = ' . ($value === null ? 'NULL' : $db->quote((string) $value)));
        }

        $query->where($db->quoteName('id') . ' = :id')->bind(':id', $ruleId, ParameterType::INTEGER);
        $db->setQuery($query)->execute();
    }

    /**
     * @param array<mixed> $rows
     */
    private function restoreRows(int $ruleId, int $formId, string $table, ?string $elementColumn, array $rows): void
    {
        $db = $this->db;
        $db->setQuery(
            $db->createQuery()
                ->delete($db->quoteName($table))
                ->where($db->quoteName('rule_id') . ' = :id')
                ->bind(':id', $ruleId, ParameterType::INTEGER)
        )->execute();

        $columns = $db->getTableColumns($table);
        $elements = $elementColumn === null ? [] : array_map('intval', $db->setQuery(
            $db->createQuery()
                ->select($db->quoteName('id'))
                ->from($db->quoteName('#__facileforms_elements'))
                ->where($db->quoteName('form') . ' = :form')
                ->bind(':form', $formId, ParameterType::INTEGER)
        )->loadColumn());

        foreach ($rows as $row) {
            if (!\is_array($row)) {
                continue;
            }

            if ($elementColumn !== null && !\in_array((int) ($row[$elementColumn] ?? 0), $elements, true)) {
                continue;
            }

            $row = array_intersect_key($row, $columns);
            unset($row['id']);
            $row['rule_id'] = $ruleId;
            $object = (object) $row;
            $db->insertObject($table, $object);
        }
    }
}
