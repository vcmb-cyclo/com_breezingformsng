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

use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\ParameterType;

/**
 * List of the code items kept in packages: scripts and pieces.
 */
abstract class PackageListModel extends ListModel
{
    abstract protected function getTableName(): string;

    private const SORTS = ['id', 'package', 'title', 'name', 'type', 'description', 'modified', 'published'];

    public function __construct($config = [], ?MVCFactoryInterface $factory = null)
    {
        $config['filter_fields'] ??= self::SORTS;

        parent::__construct($config, $factory);
    }

    protected function populateState($ordering = 'name', $direction = 'ASC')
    {
        parent::populateState($ordering, $direction);
    }

    protected function getStoreId($id = '')
    {
        $id .= ':' . $this->getState('filter.search');
        $id .= ':' . $this->getState('filter.package');
        $id .= ':' . $this->getState('filter.published');

        return parent::getStoreId($id);
    }

    protected function getListQuery()
    {
        $db = $this->getDatabase();
        $query = $db->createQuery()
            ->select($db->quoteName([
                'id', 'package', 'title', 'name', 'type', 'description', 'created', 'modified', 'published',
            ]))
            ->from($db->quoteName($this->getTableName()));

        $package = (string) $this->getState('filter.package');

        if ($package !== '') {
            $query->where($db->quoteName('package') . ' = :package')
                ->bind(':package', $package);
        }

        $published = (string) $this->getState('filter.published');

        if (is_numeric($published)) {
            $published = (int) $published;
            $query->where($db->quoteName('published') . ' = :published')
                ->bind(':published', $published, ParameterType::INTEGER);
        }

        $search = trim((string) $this->getState('filter.search'));

        if ($search !== '') {
            $like = '%' . $search . '%';
            $query->where(
                '(' . $db->quoteName('title') . ' LIKE :searchTitle'
                . ' OR ' . $db->quoteName('name') . ' LIKE :searchName'
                . ' OR ' . $db->quoteName('description') . ' LIKE :searchDescription)'
            )
                ->bind(':searchTitle', $like)
                ->bind(':searchName', $like)
                ->bind(':searchDescription', $like);
        }

        $ordering = (string) $this->getState('list.ordering', 'name');
        $ordering = in_array($ordering, self::SORTS, true) ? $ordering : 'name';
        $direction = strtoupper((string) $this->getState('list.direction', 'ASC')) === 'DESC' ? 'DESC' : 'ASC';

        return $query->order($db->quoteName($ordering) . ' ' . $direction . ', ' . $db->quoteName('id') . ' DESC');
    }

    /**
     * The item before or after the given one, by id, within the package
     * filter of the list; the ends wrap around.
     */
    public function getAdjacentId(int $itemId, string $direction): int
    {
        $db = $this->getDatabase();
        $package = (string) $this->getState('filter.package');
        $previous = $direction === 'prev';

        foreach ([true, false] as $bounded) {
            $query = $db->createQuery()
                ->select($db->quoteName('id'))
                ->from($db->quoteName($this->getTableName()))
                ->order($db->quoteName('id') . ($previous ? ' DESC' : ' ASC'))
                ->setLimit(1);

            if ($bounded) {
                $query->where($db->quoteName('id') . ($previous ? ' < :itemId' : ' > :itemId'))
                    ->bind(':itemId', $itemId, ParameterType::INTEGER);
            }

            if ($package !== '') {
                $query->where($db->quoteName('package') . ' = :package')
                    ->bind(':package', $package);
            }

            $adjacentId = (int) $db->setQuery($query)->loadResult();

            if ($adjacentId > 0) {
                return $adjacentId;
            }
        }

        return $itemId;
    }
}
