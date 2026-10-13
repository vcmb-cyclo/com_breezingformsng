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

    public function __construct($config = [], ?MVCFactoryInterface $factory = null)
    {
        $config['filter_fields'] ??= [...self::SORTS, 'form', 'state'];

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

        return parent::getStoreId($id);
    }

    protected function getListQuery()
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select([
                'records.id', 'records.submitted', 'records.modified', 'records.ip',
                'records.user_id', 'records.username', 'records.user_full_name',
                'records.viewed', 'records.exported', 'records.state', 'records.paypal_tx_id',
                'forms.title AS form_title', 'forms.name AS form_name', 'forms.id AS form_id',
            ])
            ->from($db->quoteName('#__facileforms_records', 'records'))
            ->join('INNER', $db->quoteName('#__facileforms_forms', 'forms') . ' ON forms.id = records.form');

        $this->applyFilters($query);

        return $query->order($this->getOrderSql());
    }

    /**
     * Return the record immediately before or after the current one in the
     * same ordered result set as the records list.
     */
    public function getAdjacentRecordId(int $recordId, string $direction): ?int
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select($db->quoteName('records.id'))
            ->from($db->quoteName('#__facileforms_records', 'records'))
            ->join('INNER', $db->quoteName('#__facileforms_forms', 'forms') . ' ON forms.id = records.form');

        $this->applyFilters($query);
        $db->setQuery($query->order($this->getOrderSql()));

        $recordIds = array_map('intval', $db->loadColumn());
        $position = array_search($recordId, $recordIds, true);

        if ($position === false) {
            return null;
        }

        $adjacentPosition = $direction === 'prev' ? $position - 1 : $position + 1;

        return $recordIds[$adjacentPosition] ?? null;
    }

    private function getOrderSql(): string
    {
        $orderCol = (string) $this->getState('list.ordering', self::DEFAULT_ORDERING);
        $orderCol = in_array($orderCol, self::SORTS, true) ? $orderCol : self::DEFAULT_ORDERING;
        $dir = strtoupper((string) $this->getState('list.direction', 'DESC')) === 'ASC' ? 'ASC' : 'DESC';

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

        $states = RecordState::filterStates((string) $this->getState('filter.state'));

        if ($states !== null) {
            $query->whereIn($db->quoteName('records.state'), $states);
        }

        if ($searchTerm === '') {
            return;
        }

        $like = '%' . $searchTerm . '%';
        $conditions = [
            'records.id = :searchExact',
            $db->quoteName('records.ip') . ' LIKE :searchLike1',
            $db->quoteName('records.username') . ' LIKE :searchLike2',
            $db->quoteName('records.user_full_name') . ' LIKE :searchLike3',
            $db->quoteName('forms.title') . ' LIKE :searchLike4',
            $db->quoteName('forms.name') . ' LIKE :searchLike5',
            $db->quoteName('records.paypal_tx_id') . ' LIKE :searchLike6',
        ];

        if ($formId > 0 || $states !== null) {
            $query->extendWhere('AND', $conditions, 'OR');
        } else {
            $query->where($conditions, 'OR');
        }

        $query->bind(':searchExact', $searchTerm, ParameterType::STRING)
            ->bind(':searchLike1', $like, ParameterType::STRING)
            ->bind(':searchLike2', $like, ParameterType::STRING)
            ->bind(':searchLike3', $like, ParameterType::STRING)
            ->bind(':searchLike4', $like, ParameterType::STRING)
            ->bind(':searchLike5', $like, ParameterType::STRING)
            ->bind(':searchLike6', $like, ParameterType::STRING);
    }
}
