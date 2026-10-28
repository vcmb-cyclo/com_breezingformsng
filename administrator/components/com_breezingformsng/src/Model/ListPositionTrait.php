<?php

/**
 * BreezingForms NG - A Joomla Forms Application
 *
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 *
 * SPDX-License-Identifier: GPL-2.0-or-later
 **/

namespace Vcmb\Component\BreezingformsNG\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\Database\QueryInterface;

/**
 * Position of an item in a list model, with the filters and ordering of the
 * list: the pages of the item screen pagination.
 */
trait ListPositionTrait
{
    /**
     * The id column of the list query.
     */
    abstract protected function getPositionIdColumn(): string;

    /**
     * Position (from 1) of the item in the list and number of items, or null
     * when the filters of the list leave the item out.
     *
     * @return array{position: int, total: int}|null
     */
    public function getPosition(int $itemId): ?array
    {
        $ids = $this->getDatabase()->setQuery($this->getOrderedIdsQuery())->loadColumn();
        $ids = array_values(array_map('intval', $ids));
        $position = array_search($itemId, $ids, true);

        return $position !== false ? ['position' => $position + 1, 'total' => count($ids)] : null;
    }

    /**
     * Id of the item at a position (from 0) of the list.
     */
    public function getIdAt(int $offset): ?int
    {
        if ($offset < 0) {
            return null;
        }

        $id = $this->getDatabase()->setQuery($this->getOrderedIdsQuery(), $offset, 1)->loadResult();

        return $id !== null ? (int) $id : null;
    }

    private function getOrderedIdsQuery(): QueryInterface
    {
        $query = $this->getListQuery();

        if (!$query instanceof QueryInterface) {
            throw new \RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));
        }

        return $query->clear('select')->select($this->getDatabase()->quoteName($this->getPositionIdColumn()));
    }
}
