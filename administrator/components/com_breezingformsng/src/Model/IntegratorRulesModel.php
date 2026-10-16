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
 * List of the integrator rules.
 */
class IntegratorRulesModel extends ListModel
{
    private const SORTS = ['rules.name', 'rules.type', 'forms.name', 'rules.reference_table', 'rules.published'];

    /**
     * @param array<string, mixed> $config
     */
    public function __construct($config = [], ?MVCFactoryInterface $factory = null)
    {
        $config['filter_fields'] ??= [...self::SORTS, 'published'];

        parent::__construct($config, $factory);
    }

    protected function populateState($ordering = 'rules.name', $direction = 'ASC')
    {
        parent::populateState($ordering, $direction);
    }

    protected function getStoreId($id = '')
    {
        $id .= ':' . $this->getState('filter.search');
        $id .= ':' . $this->getState('filter.published');

        return parent::getStoreId($id);
    }

    protected function getListQuery()
    {
        $db = $this->getDatabase();
        $query = $db->createQuery()
            ->select($db->quoteName(['rules.id', 'rules.name', 'rules.type', 'rules.published']))
            ->select('CONCAT(' . $db->quote($db->getPrefix()) . ', ' . $db->quoteName('rules.reference_table') . ') AS ' . $db->quoteName('reference_table'))
            ->select($db->quoteName('forms.name', 'form_name'))
            ->from($db->quoteName('#__facileforms_integrator_rules', 'rules'))
            ->join(
                'INNER',
                $db->quoteName('#__facileforms_forms', 'forms'),
                $db->quoteName('rules.form_id') . ' = ' . $db->quoteName('forms.id')
            );

        $published = (string) $this->getState('filter.published');

        if (is_numeric($published)) {
            $published = (int) $published;
            $query->where($db->quoteName('rules.published') . ' = :published')
                ->bind(':published', $published, ParameterType::INTEGER);
        }

        $search = trim((string) $this->getState('filter.search'));

        if ($search !== '') {
            $like = '%' . $search . '%';
            $query->where(
                '(' . $db->quoteName('rules.name') . ' LIKE :searchRule'
                . ' OR ' . $db->quoteName('forms.name') . ' LIKE :searchForm'
                . ' OR ' . $db->quoteName('rules.reference_table') . ' LIKE :searchTable)'
            )
                ->bind(':searchRule', $like)
                ->bind(':searchForm', $like)
                ->bind(':searchTable', $like);
        }

        $ordering = (string) $this->getState('list.ordering', 'rules.name');
        $ordering = in_array($ordering, self::SORTS, true) ? $ordering : 'rules.name';
        $direction = strtoupper((string) $this->getState('list.direction', 'ASC')) === 'DESC' ? 'DESC' : 'ASC';

        return $query->order($db->quoteName($ordering) . ' ' . $direction . ', ' . $db->quoteName('rules.id') . ' ASC');
    }
}
