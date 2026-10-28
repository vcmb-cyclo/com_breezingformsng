<?php

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Site\Service\Integration;

defined('_JEXEC') or die('Direct Access to this location is not allowed.');

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Vcmb\Component\BreezingformsNG\Site\Service\Scripting\StoredPhpExecutor;

/**
 * BreezingForms NG - A Joomla Forms Application
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2008-2020 by Markus Bopp
 * @copyright @copyright  Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt     Released under the terms of the GNU General Public License
 *
 * Reads rules configured in the admin Integrator screen (Super User only) and
 * commits submitted form data into arbitrary third-party tables. The
 * reference table/column names and the "code"/"finalize_code" snippets are
 * admin-authored configuration, not user input — that trust boundary is
 * unchanged from the legacy implementation. What changed: every identifier
 * is now quoted via quoteName() and every value is bound as a query
 * parameter instead of being concatenated into the SQL string.
 **/
class IntegratorRuntime
{
    private ?StoredPhpExecutor $codeExecutorService = null;

    /** @var array<int, list<\stdClass>> */
    private array $itemsByRule = [];

    private DatabaseInterface $db;

    /** @var list<array{rule: \stdClass, items: array<int, array<string, mixed>>}> */
    private $rules = array();

    /** @var array<string, array<mixed>> */
    private $data = array();

    public function __construct(int $formId, DatabaseInterface $database)
    {
        $this->db = $database;
        $this->rules = $this->getRules($formId);
    }

    /**
     * @param int $formId
     * @return list<array{rule: \stdClass, items: array<int, array<string, mixed>>}>
     */
    public function getRules($formId)
    {
        $db = $this->db;
        $query = $db->createQuery()
            ->select([
                'rules.*',
                $db->quoteName('rules.id', 'id'),
                'CONCAT(' . $db->quote($db->getPrefix()) . ', rules.reference_table) AS ' . $db->quoteName('reference_table'),
                $db->quoteName('forms.name', 'form_name'),
                $db->quoteName('forms.id', 'form_id'),
            ])
            ->from($db->quoteName('#__facileforms_integrator_rules', 'rules'))
            ->join('INNER', $db->quoteName('#__facileforms_forms', 'forms') . ' ON ' . $db->quoteName('rules.form_id') . ' = ' . $db->quoteName('forms.id'))
            ->where($db->quoteName('rules.form_id') . ' = :formId1')
            ->where($db->quoteName('forms.id') . ' = :formId2')
            ->where($db->quoteName('rules.published') . ' = 1')
            ->group($db->quoteName('rules.id'))
            ->order($db->quoteName('rules.id'))
            ->bind(':formId1', $formId, ParameterType::INTEGER)
            ->bind(':formId2', $formId, ParameterType::INTEGER);

        $out = array();
        $rules = $db->setQuery($query)->loadObjectList();
        if ($rules) {
            foreach ($rules as $rule) {
                $out[] = ['rule' => $rule, 'items' => array()];
            }
        }
        return $out;
    }

    /**
     * @param int|string $ruleId
     * @return list<\stdClass>
     */
    public function getItems($ruleId)
    {
        $ruleId = (int) $ruleId;

        if (array_key_exists($ruleId, $this->itemsByRule)) {
            return $this->itemsByRule[$ruleId];
        }

        $db = $this->db;
        $query = $db->createQuery()
            ->select([
                'items.*',
                $db->quoteName('elements.name', 'element_name'),
                $db->quoteName('elements.type', 'element_type'),
            ])
            ->from($db->quoteName('#__facileforms_integrator_items', 'items'))
            ->join('INNER', $db->quoteName('#__facileforms_elements', 'elements') . ' ON ' . $db->quoteName('elements.id') . ' = ' . $db->quoteName('items.element_id'))
            ->where($db->quoteName('items.rule_id') . ' = :ruleId')
            ->where($db->quoteName('items.published') . ' = 1')
            ->group($db->quoteName('items.id'))
            ->order($db->quoteName('items.id') . ' DESC')
            ->bind(':ruleId', $ruleId, ParameterType::INTEGER);

        $out = array();
        $items = $db->setQuery($query)->loadObjectList();
        foreach ($items as $item) {
            $out[] = $item;
        }
        return $this->itemsByRule[$ruleId] = $out;
    }

    /**
     * @param int|string $ruleId
     * @return list<\stdClass>
     */
    public function getCriteria($ruleId)
    {
        $db = $this->db;
        $query = $db->createQuery()
            ->select([
                'crit.*',
                $db->quoteName('elements.name', 'element_name'),
                $db->quoteName('elements.type', 'element_type'),
            ])
            ->from($db->quoteName('#__facileforms_integrator_criteria_form', 'crit'))
            ->join('INNER', $db->quoteName('#__facileforms_elements', 'elements') . ' ON ' . $db->quoteName('elements.id') . ' = ' . $db->quoteName('crit.element_id'))
            ->where($db->quoteName('crit.rule_id') . ' = :ruleId')
            ->group($db->quoteName('crit.id'))
            ->order($db->quoteName('crit.id') . ' DESC')
            ->bind(':ruleId', $ruleId, ParameterType::INTEGER);

        try {
            $ret = $db->setQuery($query)->loadObjectList();
        } catch (\Exception $e) {
            $ret = [];
            echo $e->getMessage();
        } // try

        return $ret;
    }

    /**
     * @param int|string $ruleId
     * @return list<\stdClass>
     */
    public function getCriteriaJoomla($ruleId)
    {
        $db = $this->db;
        $query = $db->createQuery()
            ->select('crit.*')
            ->from($db->quoteName('#__facileforms_integrator_criteria_joomla', 'crit'))
            ->where($db->quoteName('crit.rule_id') . ' = :ruleId')
            ->group($db->quoteName('crit.id'))
            ->order($db->quoteName('crit.id') . ' DESC')
            ->bind(':ruleId', $ruleId, ParameterType::INTEGER);

        return $db->setQuery($query)->loadObjectList();
    }

    /**
     * @param int|string $ruleId
     * @return list<\stdClass>
     */
    public function getCriteriaFixed($ruleId)
    {
        $db = $this->db;
        $query = $db->createQuery()
            ->select('crit.*')
            ->from($db->quoteName('#__facileforms_integrator_criteria_fixed', 'crit'))
            ->where($db->quoteName('crit.rule_id') . ' = :ruleId')
            ->group($db->quoteName('crit.id'))
            ->order($db->quoteName('crit.id') . ' DESC')
            ->bind(':ruleId', $ruleId, ParameterType::INTEGER);

        return $db->setQuery($query)->loadObjectList();
    }

    /**
     * @param array<mixed> $data
     * @return void
     */
    public function field(array $data)
    {
        $this->data['data' . $data[_FF_DATA_ID]] = $data;
        foreach ($this->rules as $i => $rule) {
            $items = $this->getItems($rule['rule']->id);
            $j = 0;
            foreach ($items as $item) {
                if ($item->element_id == $data[_FF_DATA_ID]) {
                    $this->rules[$i]['items'][$j]['item'] = $item;
                    $this->rules[$i]['items'][$j]['data'] = $data;
                }
                $j++;
            }
        }
    }

    /**
     * @param mixed       $value
     * @param string|null $code
     * @return mixed
     */
    public function handleCode($value, $code)
    {
        // The code of an item changes the value it writes, as in BreezingForms: $value = trim($value);
        if (trim((string) $code) != '') {
            $this->codeExecutor()->execute($this, $code, ['value' => &$value]);
        }
        return $value;
    }

    /**
     * @param string|null $code
     * @return void
     */
    public function handleFinalizeCode($code)
    {
        if (trim($code) != '') {
            $this->codeExecutor()->execute($this, $code);
        }
    }

    private function codeExecutor(): StoredPhpExecutor
    {
        return $this->codeExecutorService ??= new StoredPhpExecutor();
    }

    /**
     * @return void
     */
    public function commit()
    {
        foreach ($this->rules as $rule) {
            if ($rule['rule']->type == 'insert') {
                $this->commitInsert($rule);
            } elseif ($rule['rule']->type == 'update') {
                $this->commitUpdate($rule);
            }
        }
    }

    /**
     * Builds an INSERT/UPDATE value list for a rule's items, running each
     * value through its item's configured handleCode() transform first.
     *
     * @param array<int, array<string, mixed>> $items
     * @return array{ok: bool, columns: string[], values: mixed[]}
     */
    private function collectItemValues(array $items): array
    {
        $columns = [];
        $values = [];
        $ok = true;

        foreach ($items as $item) {
            $value = $item['data'][_FF_DATA_VALUE];
            try {
                $value = $this->handleCode($value, $item['item']->code);
            } catch (\Throwable $e) {
                $ok = false;
                break;
            }
            $columns[] = $item['item']->reference_column;
            $values[] = $value;
        }

        return ['ok' => $ok, 'columns' => $columns, 'values' => $values];
    }

    /**
     * @param array{rule: \stdClass, items: array<int, array<string, mixed>>} $rule
     */
    private function commitInsert(array $rule): void
    {
        $collected = $this->collectItemValues($rule['items']);

        if (!$collected['ok'] || count($rule['items']) === 0) {
            return;
        }

        try {
            $this->executeInsert($rule['rule']->reference_table, $collected['columns'], $collected['values']);

            if (trim($rule['rule']->finalize_code) != '') {
                $this->handleFinalizeCode($rule['rule']->finalize_code);
            }
        } catch (\Throwable $e) {
        }
    }

    /**
     * @param array<int, string> $columns
     * @param array<int, mixed>  $values
     */
    private function executeInsert(string $table, array $columns, array $values): void
    {
        $db = $this->db;
        $query = $db->createQuery()
            ->insert($db->quoteName($table))
            ->columns(array_map(fn ($column) => $db->quoteName($column), $columns));

        $placeholders = [];
        foreach ($values as $index => $value) {
            $placeholder = ':insertValue' . $index;
            $placeholders[] = $placeholder;
            $query->bind($placeholder, $values[$index]);
        }
        $query->values(implode(',', $placeholders));

        $db->setQuery($query)->execute();
    }

    /**
     * @param array{rule: \stdClass, items: array<int, array<string, mixed>>} $rule
     */
    private function commitUpdate(array $rule): void
    {
        $collected = $this->collectItemValues($rule['items']);

        if (!$collected['ok'] || count($rule['items']) === 0) {
            return;
        }

        $criteria = $this->collectCriteria($rule['rule']->id);

        $db = $this->db;
        $query = $db->createQuery()->update($db->quoteName((string) $rule['rule']->reference_table));

        foreach ($collected['columns'] as $index => $column) {
            $placeholder = ':updateValue' . $index;
            $query->set($db->quoteName($column) . ' = ' . $placeholder);
            $query->bind($placeholder, $collected['values'][$index]);
        }

        $whereBindIndex = 0;
        $whereString = $this->buildCriteriaClauses($criteria, $query, $whereBindIndex);

        if ($whereString !== '') {
            $query->where($whereString);
        }

        try {
            $db->setQuery($query);
            $db->execute();

            // on update and no affected rows, we might like to add the row
            if ($db->getAffectedRows() <= 0) {
                $this->executeInsert($rule['rule']->reference_table, $collected['columns'], $collected['values']);
            }

            if (trim($rule['rule']->finalize_code) != '') {
                $this->handleFinalizeCode($rule['rule']->finalize_code);
            }
        } catch (\Throwable $e) {
        }
    }

    /**
     * Builds the combined WHERE fragment for an update rule (form/Joomla/fixed
     * criteria), binding every compared value as a query parameter. Each
     * criterion's own `andor` (AND/OR) glues it to the *previous* clause —
     * this can mix AND and OR arbitrarily across the three criteria groups,
     * exactly as the legacy string-concatenation version did, so the whole
     * fragment is built as one raw string rather than via repeated
     * QueryBuilder::where() calls (which only support one uniform glue).
     *
     * @param array{form: list<\stdClass>, joomla: list<\stdClass>, fixed: list<\stdClass>} $criteria
     * @param \Joomla\Database\QueryInterface $query
     */
    private function buildCriteriaClauses(array $criteria, $query, int &$bindIndex): string
    {
        $db = $this->db;
        $clauses = '';

        $appendClause = function (string $andor, string $column, string $operator, $value) use ($db, $query, &$bindIndex, &$clauses) {
            if ($clauses !== '') {
                $clauses .= ' ' . $andor . ' ';
            }

            $placeholder = ':criteriaValue' . $bindIndex++;
            $like = null;

            switch ($operator) {
                case '%...%':
                    $like = '%' . $value . '%';
                    break;
                case '%...':
                    $like = '%' . $value;
                    break;
                case '...%':
                    $like = $value . '%';
                    break;
            }

            if ($like !== null) {
                $query->bind($placeholder, $like);
                $clauses .= $db->quoteName($column) . ' LIKE ' . $placeholder;
            } else {
                $query->bind($placeholder, $value);
                $clauses .= $db->quoteName($column) . ' ' . $operator . ' ' . $placeholder;
            }
        };

        if (!empty($criteria['form'])) {
            foreach ($criteria['form'] as $crit) {
                $value = $this->data['data' . $crit->element_id][_FF_DATA_VALUE] ?? '';
                $appendClause($crit->andor, $crit->reference_column, $crit->operator, $value);
            }
        }

        if (!empty($criteria['joomla'])) {
            foreach ($criteria['joomla'] as $crit) {
                switch ($crit->joomla_object) {
                    case 'Userid':
                        $jobject = Factory::getApplication()->getIdentity()->get('id', '');
                        break;
                    case 'Username':
                        $jobject = Factory::getApplication()->getIdentity()->get('username', '');
                        break;
                    case 'Language':
                        $jobject = Factory::getApplication()->getLanguage()->getName();
                        break;
                    case 'Date':
                        $jobject = (new \Joomla\CMS\Date\Date())->toSql();
                        break;
                    default:
                        $jobject = '';
                }
                $appendClause($crit->andor, $crit->reference_column, $crit->operator, $jobject);
            }
        }

        if (!empty($criteria['fixed'])) {
            foreach ($criteria['fixed'] as $crit) {
                $appendClause($crit->andor, $crit->reference_column, $crit->operator, $crit->fixed_value);
            }
        }

        return $clauses;
    }

    /**
     * @param int|string $ruleId
     * @return array{form: list<\stdClass>, joomla: list<\stdClass>, fixed: list<\stdClass>}
     */
    public function collectCriteria($ruleId)
    {
        $crit['form'] = $this->getCriteria($ruleId);
        $crit['joomla'] = $this->getCriteriaJoomla($ruleId);
        $crit['fixed'] = $this->getCriteriaFixed($ruleId);
        return $crit;
    }
}
