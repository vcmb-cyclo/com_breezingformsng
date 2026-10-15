<?php
/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 *
 * Source history: admin/integrator.class.php (git mv — Phase 3).
 */

namespace Vcmb\Component\BreezingformsNG\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

class IntegratorModel extends BaseDatabaseModel
{
    private const ALLOWED_OPERATORS = ['=', '<>', '>', '<', '>=', '<=', '%...%', '%...', '...%'];

    private function db(): DatabaseInterface
    {
        return $this->getDatabase();
    }

    public function getRule(int $id): ?\stdClass
    {
        $db = $this->db();
        $query = $db->createQuery()
            ->select([
                'rules.*',
                'rules.id AS id',
                "CONCAT(" . $db->quote($db->getPrefix()) . ", rules.reference_table) AS reference_table",
                'forms.name AS form_name',
                'forms.id AS form_id',
            ])
            ->from($db->quoteName('#__facileforms_integrator_rules', 'rules'))
            ->join('INNER', $db->quoteName('#__facileforms_forms', 'forms') . ' ON rules.form_id = forms.id')
            ->where('rules.id = :id')
            ->group('rules.id')
            ->bind(':id', $id, ParameterType::INTEGER);
        $db->setQuery($query);
        $rows = $db->loadObjectList();
        return count($rows) === 1 ? $rows[0] : null;
    }

    public function getItems(int $ruleId): array
    {
        $db = $this->db();
        $query = $db->createQuery()
            ->select(['items.*', 'elements.name AS element_name', 'elements.type AS element_type'])
            ->from($db->quoteName('#__facileforms_integrator_items', 'items'))
            ->join('INNER', $db->quoteName('#__facileforms_elements', 'elements') . ' ON elements.id = items.element_id')
            ->where('items.rule_id = :ruleId')
            ->group('items.id')
            ->order('items.id DESC')
            ->bind(':ruleId', $ruleId, ParameterType::INTEGER);
        $db->setQuery($query);
        return $db->loadObjectList() ?: [];
    }

    public function getTableNames(): array
    {
        return $this->db()->getTableList();
    }

    public function getTableColumns(string $table): array
    {
        try {
            return $this->db()->getTableColumns($table) ?: [];
        } catch (\Throwable) {
            return [];
        }
    }

    public function getForms(string $filter = 'all'): array
    {
        $db    = $this->db();
        $query = $db->createQuery()
            ->select(['id', 'name', 'published'])
            ->from($db->quoteName('#__facileforms_forms', 'forms'));

        match ($filter) {
            'published'   => $query->where('forms.published = 1'),
            'unpublished' => $query->where('forms.published = 0'),
            default       => null,
        };

        $db->setQuery($query);
        return $db->loadObjectList() ?: [];
    }

    public function getFormElements(int $formId): array
    {
        $db = $this->db();
        $query = $db->createQuery()
            ->select(['id', 'name', 'type'])
            ->from($db->quoteName('#__facileforms_elements'))
            ->where('form = :formId')
            ->bind(':formId', $formId, ParameterType::INTEGER);
        $db->setQuery($query);
        return $db->loadObjectList() ?: [];
    }

    public function saveRule(string $name, int $formId, string $referenceTable, string $type): int
    {
        $db  = $this->db();
        $pfx = $db->getPrefix();
        $tbl = str_starts_with($referenceTable, $pfx)
            ? substr($referenceTable, \strlen($pfx))
            : $referenceTable;
        $type = \in_array($type, ['insert', 'update'], true) ? $type : 'insert';

        $query = $db->createQuery()
            ->insert($db->quoteName('#__facileforms_integrator_rules'))
            ->columns($db->quoteName(['name', 'form_id', 'reference_table', 'type']))
            ->values(':name, :formId, :referenceTable, :type')
            ->bind(':name', $name, ParameterType::STRING)
            ->bind(':formId', $formId, ParameterType::INTEGER)
            ->bind(':referenceTable', $tbl, ParameterType::STRING)
            ->bind(':type', $type, ParameterType::STRING);
        $db->setQuery($query)->execute();
        return (int) $db->insertid();
    }

    public function deleteRules(array $ids): void
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if (empty($ids)) {
            return;
        }
        $db = $this->db();
        foreach ([
            ['#__facileforms_integrator_rules', 'id'],
            ['#__facileforms_integrator_items', 'rule_id'],
            ['#__facileforms_integrator_criteria_form', 'rule_id'],
            ['#__facileforms_integrator_criteria_joomla', 'rule_id'],
            ['#__facileforms_integrator_criteria_fixed', 'rule_id'],
        ] as [$tbl, $col]) {
            $query = $db->createQuery()
                ->delete($db->quoteName($tbl))
                ->whereIn($db->quoteName($col), $ids, ParameterType::INTEGER);
            $db->setQuery($query)->execute();
        }
    }

    public function publishRule(int $id, int $state): void
    {
        $db    = $this->db();
        $query = $db->createQuery()
            ->update($db->quoteName('#__facileforms_integrator_rules'))
            ->set($db->quoteName('published') . ' = :state')
            ->where($db->quoteName('id') . ' = :id')
            ->bind(':state', $state, ParameterType::INTEGER)
            ->bind(':id', $id, ParameterType::INTEGER);
        $db->setQuery($query)->execute();
    }

    public function getCriteria(int $ruleId): array
    {
        $db = $this->db();
        $query = $db->createQuery()
            ->select(['crit.*', 'elements.name AS element_name', 'elements.type AS element_type'])
            ->from($db->quoteName('#__facileforms_integrator_criteria_form', 'crit'))
            ->join('INNER', $db->quoteName('#__facileforms_elements', 'elements') . ' ON elements.id = crit.element_id')
            ->where('crit.rule_id = :ruleId')
            ->group('crit.id')
            ->order('crit.id DESC')
            ->bind(':ruleId', $ruleId, ParameterType::INTEGER);
        $db->setQuery($query);
        return $db->loadObjectList() ?: [];
    }

    public function addCriteria(int $ruleId, string $operator, string $referenceColumn, int $elementId, string $andor): void
    {
        if (!\in_array($operator, self::ALLOWED_OPERATORS, true)) {
            return;
        }
        $andor = \in_array($andor, ['AND', 'OR'], true) ? $andor : 'AND';
        $db    = $this->db();
        $query = $db->createQuery()
            ->insert($db->quoteName('#__facileforms_integrator_criteria_form'))
            ->columns($db->quoteName(['rule_id', 'operator', 'reference_column', 'element_id', 'andor']))
            ->values(':ruleId, :operator, :referenceColumn, :elementId, :andor')
            ->bind(':ruleId', $ruleId, ParameterType::INTEGER)
            ->bind(':operator', $operator, ParameterType::STRING)
            ->bind(':referenceColumn', $referenceColumn, ParameterType::STRING)
            ->bind(':elementId', $elementId, ParameterType::INTEGER)
            ->bind(':andor', $andor, ParameterType::STRING);
        $db->setQuery($query)->execute();
    }

    public function removeCriteria(int $id): void
    {
        $db    = $this->db();
        $query = $db->createQuery()
            ->delete($db->quoteName('#__facileforms_integrator_criteria_form'))
            ->where($db->quoteName('id') . ' = :id')
            ->bind(':id', $id, ParameterType::INTEGER);
        $db->setQuery($query)->execute();
    }

    public function getCriteriaJoomla(int $ruleId): array
    {
        $db = $this->db();
        $query = $db->createQuery()
            ->select('crit.*')
            ->from($db->quoteName('#__facileforms_integrator_criteria_joomla', 'crit'))
            ->where('crit.rule_id = :ruleId')
            ->group('crit.id')
            ->order('crit.id DESC')
            ->bind(':ruleId', $ruleId, ParameterType::INTEGER);
        $db->setQuery($query);
        return $db->loadObjectList() ?: [];
    }

    public function addCriteriaJoomla(int $ruleId, string $operator, string $referenceColumn, string $joomlaObject, string $andor): void
    {
        if (!\in_array($operator, self::ALLOWED_OPERATORS, true)) {
            return;
        }
        $allowed = ['Userid', 'Username', 'Language', 'Date'];
        $joomlaObject = \in_array($joomlaObject, $allowed, true) ? $joomlaObject : 'Userid';
        $andor = \in_array($andor, ['AND', 'OR'], true) ? $andor : 'AND';
        $db      = $this->db();
        $query   = $db->createQuery()
            ->insert($db->quoteName('#__facileforms_integrator_criteria_joomla'))
            ->columns($db->quoteName(['rule_id', 'operator', 'reference_column', 'joomla_object', 'andor']))
            ->values(':ruleId, :operator, :referenceColumn, :joomlaObject, :andor')
            ->bind(':ruleId', $ruleId, ParameterType::INTEGER)
            ->bind(':operator', $operator, ParameterType::STRING)
            ->bind(':referenceColumn', $referenceColumn, ParameterType::STRING)
            ->bind(':joomlaObject', $joomlaObject, ParameterType::STRING)
            ->bind(':andor', $andor, ParameterType::STRING);
        $db->setQuery($query)->execute();
    }

    public function removeCriteriaJoomla(int $id): void
    {
        $db    = $this->db();
        $query = $db->createQuery()
            ->delete($db->quoteName('#__facileforms_integrator_criteria_joomla'))
            ->where($db->quoteName('id') . ' = :id')
            ->bind(':id', $id, ParameterType::INTEGER);
        $db->setQuery($query)->execute();
    }

    public function getCriteriaFixed(int $ruleId): array
    {
        $db = $this->db();
        $query = $db->createQuery()
            ->select('crit.*')
            ->from($db->quoteName('#__facileforms_integrator_criteria_fixed', 'crit'))
            ->where('crit.rule_id = :ruleId')
            ->group('crit.id')
            ->order('crit.id DESC')
            ->bind(':ruleId', $ruleId, ParameterType::INTEGER);
        $db->setQuery($query);
        return $db->loadObjectList() ?: [];
    }

    public function addCriteriaFixed(int $ruleId, string $operator, string $referenceColumn, string $fixedValue, string $andor): void
    {
        if (!\in_array($operator, self::ALLOWED_OPERATORS, true)) {
            return;
        }
        $andor = \in_array($andor, ['AND', 'OR'], true) ? $andor : 'AND';
        $db    = $this->db();
        $query = $db->createQuery()
            ->insert($db->quoteName('#__facileforms_integrator_criteria_fixed'))
            ->columns($db->quoteName(['rule_id', 'operator', 'reference_column', 'fixed_value', 'andor']))
            ->values(':ruleId, :operator, :referenceColumn, :fixedValue, :andor')
            ->bind(':ruleId', $ruleId, ParameterType::INTEGER)
            ->bind(':operator', $operator, ParameterType::STRING)
            ->bind(':referenceColumn', $referenceColumn, ParameterType::STRING)
            ->bind(':fixedValue', $fixedValue, ParameterType::STRING)
            ->bind(':andor', $andor, ParameterType::STRING);
        $db->setQuery($query)->execute();
    }

    public function removeCriteriaFixed(int $id): void
    {
        $db    = $this->db();
        $query = $db->createQuery()
            ->delete($db->quoteName('#__facileforms_integrator_criteria_fixed'))
            ->where($db->quoteName('id') . ' = :id')
            ->bind(':id', $id, ParameterType::INTEGER);
        $db->setQuery($query)->execute();
    }

    public function addItem(int $ruleId, int $elementId, string $referenceColumn): void
    {
        $db    = $this->db();
        $query = $db->createQuery()
            ->insert($db->quoteName('#__facileforms_integrator_items'))
            ->columns($db->quoteName(['rule_id', 'element_id', 'reference_column']))
            ->values(':ruleId, :elementId, :referenceColumn')
            ->bind(':ruleId', $ruleId, ParameterType::INTEGER)
            ->bind(':elementId', $elementId, ParameterType::INTEGER)
            ->bind(':referenceColumn', $referenceColumn, ParameterType::STRING);
        $db->setQuery($query)->execute();
    }

    public function removeItem(int $id): void
    {
        $db    = $this->db();
        $query = $db->createQuery()
            ->delete($db->quoteName('#__facileforms_integrator_items'))
            ->where($db->quoteName('id') . ' = :id')
            ->bind(':id', $id, ParameterType::INTEGER);
        $db->setQuery($query)->execute();
    }

    public function saveCode(int $itemId, int $ruleId, string $code): void
    {
        $db    = $this->db();
        $query = $db->createQuery()
            ->update($db->quoteName('#__facileforms_integrator_items'))
            ->set($db->quoteName('code') . ' = :code')
            ->where($db->quoteName('id') . ' = :itemId')
            ->where($db->quoteName('rule_id') . ' = :ruleId')
            ->bind(':code', $code, ParameterType::STRING)
            ->bind(':itemId', $itemId, ParameterType::INTEGER)
            ->bind(':ruleId', $ruleId, ParameterType::INTEGER);
        $db->setQuery($query)->execute();
    }

    public function saveFinalizeCode(int $ruleId, string $code): void
    {
        $db    = $this->db();
        $query = $db->createQuery()
            ->update($db->quoteName('#__facileforms_integrator_rules'))
            ->set($db->quoteName('finalize_code') . ' = :code')
            ->where($db->quoteName('id') . ' = :ruleId')
            ->bind(':code', $code, ParameterType::STRING)
            ->bind(':ruleId', $ruleId, ParameterType::INTEGER);
        $db->setQuery($query)->execute();
    }

    public function publishItem(int $id, int $state): void
    {
        $db    = $this->db();
        $query = $db->createQuery()
            ->update($db->quoteName('#__facileforms_integrator_items'))
            ->set($db->quoteName('published') . ' = :state')
            ->where($db->quoteName('id') . ' = :id')
            ->bind(':state', $state, ParameterType::INTEGER)
            ->bind(':id', $id, ParameterType::INTEGER);
        $db->setQuery($query)->execute();
    }
}
