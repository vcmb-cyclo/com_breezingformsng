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

class MenusModel extends ListModel
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct($config = [], ?MVCFactoryInterface $factory = null)
    {
        $config['filter_fields'] ??= ['package', 'published'];

        parent::__construct($config, $factory);
    }

    protected function getStoreId($id = '')
    {
        $id .= ':' . $this->getState('filter.search');
        $id .= ':' . $this->getState('filter.package');
        $id .= ':' . $this->getState('filter.published');

        return parent::getStoreId($id);
    }

    /**
     * Top level items first, each level in its saved order.
     */
    protected function getListQuery()
    {
        $db = $this->getDatabase();
        $query = $db->createQuery()
            ->select($db->quoteName([
                'menus.id', 'menus.package', 'menus.parent', 'menus.ordering',
                'menus.published', 'menus.title', 'menus.name', 'menus.page',
            ]))
            ->select($db->quoteName('parents.title', 'parent_title'))
            ->from($db->quoteName('#__facileforms_compmenus', 'menus'))
            ->join(
                'LEFT',
                $db->quoteName('#__facileforms_compmenus', 'parents'),
                $db->quoteName('parents.id') . ' = ' . $db->quoteName('menus.parent')
            )
            ->order([$db->quoteName('menus.parent') . ' ASC', $db->quoteName('menus.ordering') . ' ASC']);

        $package = (string) $this->getState('filter.package');

        if ($package !== '') {
            $query->where($db->quoteName('menus.package') . ' = :package')
                ->bind(':package', $package);
        }

        $published = (string) $this->getState('filter.published');

        if (is_numeric($published)) {
            $published = (int) $published;
            $query->where($db->quoteName('menus.published') . ' = :published')
                ->bind(':published', $published, ParameterType::INTEGER);
        }

        $search = trim((string) $this->getState('filter.search'));

        if ($search !== '') {
            $like = '%' . $search . '%';
            $query->where(
                '(' . $db->quoteName('menus.title') . ' LIKE :searchTitle'
                . ' OR ' . $db->quoteName('menus.name') . ' LIKE :searchName)'
            )
                ->bind(':searchTitle', $like)
                ->bind(':searchName', $like);
        }

        return $query;
    }
}
