<?php
/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Vcmb\Component\BreezingformsNG\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\ParameterType;
use Joomla\Database\QueryInterface;
use Vcmb\Component\BreezingformsNG\Administrator\Service\RecordState;

class RecordsModel extends ListModel
{
    private const DEFAULT_ORDERING = 'records.submitted';

    private const SORTS = [
        'records.id',
        'records.submitted',
        'forms.title',
        'records.ip',
        'records.username',
        'records.viewed',
        'records.exported',
        'records.state',
        'records.modified',
    ];

    /**
     * @param array<string, mixed> $config
     */
    public function __construct($config = [], ?MVCFactoryInterface $factory = null)
    {
        $config['filter_fields'] ??= [...self::SORTS, 'form', 'state', 'orphan_element'];

        parent::__construct($config, $factory);
    }

    protected function populateState($ordering = self::DEFAULT_ORDERING, $direction = 'DESC')
    {
        parent::populateState($ordering, $direction);
    }

    protected function getStoreId($id = '')
    {
        $id .= ':' . $this->getState('filter.search');
        $id .= ':' . $this->getState('filter.form');
        $id .= ':' . $this->getState('filter.state');
        $id .= ':' . $this->getState('filter.orphan_element');

        return parent::getStoreId($id);
    }

    protected function getListQuery()
    {
        $db = $this->getDatabase();
        $query = $db->createQuery()
            ->select([
                'records.id', 'records.submitted', 'records.modified', 'records.ip',
                'records.user_id', 'records.username', 'records.user_full_name',
                'records.viewed', 'records.exported', 'records.state', 'records.paypal_tx_id',
                'records.checked_out', 'records.checked_out_time',
                // A deleted form keeps the title and name its records stored.
                'COALESCE(forms.title, records.title) AS form_title',
                'COALESCE(forms.name, records.name) AS form_name',
                'forms.id AS form_id',
            ])
            ->select($db->quoteName('editor.name', 'editor'))
            ->from($db->quoteName('#__facileforms_records', 'records'))
            ->join('LEFT', $db->quoteName('#__facileforms_forms', 'forms') . ' ON forms.id = records.form')
            ->join(
                'LEFT',
                $db->quoteName('#__users', 'editor'),
                $db->quoteName('editor.id') . ' = ' . $db->quoteName('records.checked_out')
            );

        $this->applyFilters($query);

        return $query->order($this->getOrderSql());
    }

    /**
     * The records immediately before and after the current one, and its
     * 1-based position, in the same ordered result set as the records list.
     * Position and neighbours are null when the record is outside that list.
     *
     * Bounded queries only (no id list is loaded): the sort value of the
     * current record, the size of the list, the rows ahead of it and the two
     * neighbours found with a keyset condition on the list ordering.
     *
     * @return array{prev: ?int, next: ?int, position: ?int, total: int}
     */
    public function getNavigation(int $recordId): array
    {
        $db = $this->getDatabase();
        [$orderCol, $dir] = $this->getOrdering();

        $current = $this->getOrderedIdsQuery(false)
            ->select($db->quoteName($orderCol, 'nav_value'))
            ->where($db->quoteName('records.id') . ' = :navId')
            ->bind(':navId', $recordId, ParameterType::INTEGER);
        $currentRow = $db->setQuery($current, 0, 1)->loadAssoc();

        $total = (int) $db->setQuery($this->getOrderedIdsQuery(false, 'COUNT(*)'))->loadResult();

        if (!\is_array($currentRow)) {
            return ['prev' => null, 'next' => null, 'position' => null, 'total' => $total];
        }

        $value = $currentRow['nav_value'] ?? null;
        $value = $value === null ? null : (string) $value;
        $ascending = $dir === 'ASC';
        $before = $ascending ? 'below' : 'above';
        $after = $ascending ? 'above' : 'below';

        $position = $this->getOrderedIdsQuery(false, 'COUNT(*)');
        $this->whereRelativeTo($position, $orderCol, $value, $recordId, $before);
        $ahead = (int) $db->setQuery($position)->loadResult();

        $reverse = $ascending ? 'DESC' : 'ASC';
        $prev = $this->getOrderedIdsQuery(false);
        $this->whereRelativeTo($prev, $orderCol, $value, $recordId, $before);
        $prev->order($orderCol . ' ' . $reverse . ($orderCol !== 'records.id' ? ', records.id ' . $reverse : ''));
        $prevId = $db->setQuery($prev, 0, 1)->loadResult();

        $next = $this->getOrderedIdsQuery();
        $this->whereRelativeTo($next, $orderCol, $value, $recordId, $after);
        $nextId = $db->setQuery($next, 0, 1)->loadResult();

        return [
            'prev' => $prevId !== null ? (int) $prevId : null,
            'next' => $nextId !== null ? (int) $nextId : null,
            'position' => $ahead + 1,
            'total' => $total,
        ];
    }

    /**
     * Id of the record at a position (from 0) of the list, with its filters
     * and ordering: the target of the record screen pagination.
     */
    public function getIdAt(int $offset): ?int
    {
        if ($offset < 0) {
            return null;
        }

        $id = $this->getDatabase()->setQuery($this->getOrderedIdsQuery(), $offset, 1)->loadResult();

        return $id !== null ? (int) $id : null;
    }

    private function getOrderedIdsQuery(bool $ordered = true, string $select = 'records.id'): QueryInterface
    {
        $db = $this->getDatabase();
        $query = $db->createQuery()
            ->select($select === 'records.id' ? $db->quoteName('records.id') : $select)
            ->from($db->quoteName('#__facileforms_records', 'records'))
            ->join('LEFT', $db->quoteName('#__facileforms_forms', 'forms') . ' ON forms.id = records.form');

        $this->applyFilters($query);

        return $ordered ? $query->order($this->getOrderSql()) : $query;
    }

    /**
     * Restricts a list query to the rows strictly below (ascending order, NULL
     * first) or above the given sort value, ties being broken by the id in the
     * direction of the list, as in getOrderSql().
     *
     * @param 'below'|'above' $side
     */
    private function whereRelativeTo(
        QueryInterface $query,
        string $orderCol,
        ?string $value,
        int $recordId,
        string $side
    ): void {
        $db = $this->getDatabase();
        $id = $db->quoteName('records.id');
        $below = $side === 'below';
        $idCompare = $id . ($below ? ' < ' : ' > ') . ':navId';
        $query->bind(':navId', $recordId, ParameterType::INTEGER);

        if ($orderCol === 'records.id') {
            $query->where($idCompare);

            return;
        }

        $col = $db->quoteName($orderCol);

        if ($value === null) {
            // NULL sorts first in ascending order: below it only the NULL rows with a smaller id.
            $query->where($below
                ? '(' . $col . ' IS NULL AND ' . $idCompare . ')'
                : '(' . $col . ' IS NOT NULL OR (' . $col . ' IS NULL AND ' . $idCompare . '))');

            return;
        }

        $type = \in_array($orderCol, ['records.viewed', 'records.exported', 'records.state'], true)
            ? ParameterType::INTEGER
            : ParameterType::STRING;
        $bound = $type === ParameterType::INTEGER ? (int) $value : $value;
        $query->bind(':navValue', $bound, $type);
        $query->where($below
            ? '(' . $col . ' IS NULL OR ' . $col . ' < :navValue OR (' . $col . ' = :navValue AND ' . $idCompare . '))'
            : '(' . $col . ' > :navValue OR (' . $col . ' = :navValue AND ' . $idCompare . '))');
    }

    /** @return array{0: string, 1: 'ASC'|'DESC'} */
    private function getOrdering(): array
    {
        $orderCol = (string) $this->getState('list.ordering', self::DEFAULT_ORDERING);
        $orderCol = in_array($orderCol, self::SORTS, true) ? $orderCol : self::DEFAULT_ORDERING;
        $dir = strtoupper((string) $this->getState('list.direction', 'DESC')) === 'ASC' ? 'ASC' : 'DESC';

        return [$orderCol, $dir];
    }

    private function getOrderSql(): string
    {
        [$orderCol, $dir] = $this->getOrdering();

        return $orderCol . ' ' . $dir . ($orderCol !== 'records.id' ? ', records.id ' . $dir : '');
    }

    private function applyFilters(QueryInterface $query): void
    {
        $db = $this->getDatabase();
        $formId = (int) $this->getState('filter.form');
        $searchTerm = trim((string) $this->getState('filter.search'));

        if ($formId > 0) {
            $query->where('records.form = :formId')
                ->bind(':formId', $formId, ParameterType::INTEGER);
        }

        // Records holding values of an element that no longer exists (About audit).
        $orphanElementId = (int) $this->getState('filter.orphan_element');

        if ($orphanElementId > 0) {
            $values = $db->createQuery()
                ->select('1')
                ->from($db->quoteName('#__facileforms_subrecords', 'orphan_values'))
                ->join(
                    'LEFT',
                    $db->quoteName('#__facileforms_elements', 'orphan_elements')
                        . ' ON ' . $db->quoteName('orphan_elements.id') . ' = ' . $db->quoteName('orphan_values.element')
                )
                ->where($db->quoteName('orphan_values.record') . ' = ' . $db->quoteName('records.id'))
                ->where($db->quoteName('orphan_values.element') . ' = :orphanElementId')
                ->where($db->quoteName('orphan_elements.id') . ' IS NULL');
            $query->where('EXISTS (' . (string) $values . ')')
                ->bind(':orphanElementId', $orphanElementId, ParameterType::INTEGER);
        }

        $states = RecordState::filterStates((string) $this->getState('filter.state'));

        if ($states !== null) {
            $query->whereIn($db->quoteName('records.state'), $states);
        }

        if ($searchTerm === '') {
            return;
        }

        // "id:12" searches the record id only, as in Joomla lists; a number
        // alone matches the id or the texts.
        if (preg_match('/^id:\s*(\d+)$/i', $searchTerm, $match) === 1) {
            $searchId = (int) $match[1];
            $query->where($db->quoteName('records.id') . ' = :searchId')
                ->bind(':searchId', $searchId, ParameterType::INTEGER);

            return;
        }

        $like = '%' . $searchTerm . '%';
        $conditions = [
            $db->quoteName('records.ip') . ' LIKE :searchLike1',
            $db->quoteName('records.username') . ' LIKE :searchLike2',
            $db->quoteName('records.user_full_name') . ' LIKE :searchLike3',
            $db->quoteName('forms.title') . ' LIKE :searchLike4',
            $db->quoteName('forms.name') . ' LIKE :searchLike5',
            $db->quoteName('records.paypal_tx_id') . ' LIKE :searchLike6',
            $db->quoteName('records.title') . ' LIKE :searchLike7',
            $db->quoteName('records.name') . ' LIKE :searchLike8',
        ];

        if (ctype_digit($searchTerm)) {
            $searchId = (int) $searchTerm;
            $conditions[] = $db->quoteName('records.id') . ' = :searchId';
            $query->bind(':searchId', $searchId, ParameterType::INTEGER);
        }

        if ($formId > 0 || $states !== null) {
            $query->extendWhere('AND', $conditions, 'OR');
        } else {
            $query->where($conditions, 'OR');
        }

        $query->bind(':searchLike1', $like, ParameterType::STRING)
            ->bind(':searchLike2', $like, ParameterType::STRING)
            ->bind(':searchLike3', $like, ParameterType::STRING)
            ->bind(':searchLike4', $like, ParameterType::STRING)
            ->bind(':searchLike5', $like, ParameterType::STRING)
            ->bind(':searchLike6', $like, ParameterType::STRING)
            ->bind(':searchLike7', $like, ParameterType::STRING)
            ->bind(':searchLike8', $like, ParameterType::STRING);
    }
}
