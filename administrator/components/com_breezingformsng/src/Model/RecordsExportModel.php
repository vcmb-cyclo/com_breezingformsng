<?php

/**
 * BreezingForms NG - A Joomla Forms Application
 *
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 *
 * SPDX-License-Identifier: GPL-2.0-or-later
 **/

namespace Vcmb\Component\BreezingformsNG\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Database\ParameterType;
use Joomla\Database\QueryInterface;
use Vcmb\Component\BreezingformsNG\Administrator\Service\RecordState;

/**
 * Reads the records an export covers.
 */
class RecordsExportModel extends BaseDatabaseModel
{
    /**
     * The records of an explicit id selection or, without one, of the form
     * and state filters, ordered by submission date descending.
     *
     * @param int[] $ids
     *
     * @return object[]
     */
    public function getRecords(array $ids, int $formSelection, string $stateFilter): array
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__facileforms_records'))
            ->order($db->quoteName('submitted') . ' DESC');

        if ($ids) {
            $query->whereIn($db->quoteName('id'), $ids, ParameterType::INTEGER);
        } else {
            $this->applyListFilters($query, $formSelection, $stateFilter);
        }

        $db->setQuery($query);
        return $db->loadObjectList();
    }

    /**
     * Without an explicit selection, an export covers what the list shows:
     * the chosen form and the chosen state.
     */
    private function applyListFilters(QueryInterface $query, int $formSelection, string $stateFilter): void
    {
        $db = $this->getDatabase();

        if ($formSelection) {
            $query->where($db->quoteName('form') . ' = :formSelection')
                ->bind(':formSelection', $formSelection, ParameterType::INTEGER);
        }

        $states = RecordState::filterStates($stateFilter);

        if ($states !== null) {
            $query->whereIn($db->quoteName('state'), $states);
        }
    }

    /**
     * Counts the records of an export selection, without loading them. Used
     * to keep the Excel export within its row ceiling.
     *
     * @param int[] $ids
     */
    public function countRecords(array $ids, int $formSelection, string $stateFilter): int
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__facileforms_records'));

        if ($ids) {
            $query->whereIn($db->quoteName('id'), $ids, ParameterType::INTEGER);
        } else {
            $this->applyListFilters($query, $formSelection, $stateFilter);
        }

        $db->setQuery($query);
        return (int) $db->loadResult();
    }

    /**
     * The distinct published element fields of the given forms, or of every
     * form when none is given.
     *
     * @param int[] $formIds
     *
     * @return object[]
     */
    public function getElementFields(array $formIds): array
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select('DISTINCT *')
            ->from($db->quoteName('#__facileforms_elements'))
            ->where($db->quoteName('published') . ' = 1')
            ->whereNotIn($db->quoteName('name'), ['bfFakeName', 'bfFakeName2', 'bfFakeName3', 'bfFakeName4', 'bfFakeName5'], ParameterType::STRING)
            ->order($db->quoteName('ordering'));

        if ($formIds) {
            $query->whereIn($db->quoteName('form'), $formIds, ParameterType::INTEGER);
        }

        $db->setQuery($query);
        return $db->loadObjectList();
    }
}
