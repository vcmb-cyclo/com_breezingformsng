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

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\Database\ParameterType;
use Joomla\Utilities\ArrayHelper;

class MenuModel extends AdminModel
{
    /**
     * @param array<string, mixed> $options
     */
    public function getTable($name = 'Menu', $prefix = 'Site', $options = [])
    {
        return parent::getTable($name, $prefix, $options);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function getForm($data = [], $loadData = true)
    {
        return $this->loadForm('com_breezingformsng.menu', 'menu', ['control' => 'jform', 'load_data' => $loadData]);
    }

    /**
     * @return array<string, mixed>|object|false
     */
    protected function loadFormData()
    {
        $application = Factory::getApplication();

        if (!$application instanceof CMSApplication) {
            throw new \RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));
        }

        $data = $application->getUserState('com_breezingformsng.edit.menu.data', []);

        if (empty($data)) {
            $data = $this->getItem();
        }

        $this->preprocessData('com_breezingformsng.menu', $data);

        return $data;
    }

    /**
     * The params column holds free text here, not a registry.
     */
    public function getItem($pk = null)
    {
        $pk = (int) ($pk ?: $this->getState($this->getName() . '.id'));
        $table = $this->getTable();

        if ($pk > 0 && !$table->load($pk)) {
            $this->setError($table->getError() ?: Text::_('JLIB_APPLICATION_ERROR_NOT_EXIST'));

            return false;
        }

        $item = ArrayHelper::toObject(get_object_vars($table));

        return $item instanceof \stdClass ? $item : false;
    }

    /**
     * A new item goes last among its siblings.
     */
    protected function prepareTable($table)
    {
        $table->package = (string) $table->package;

        if ((int) $table->id > 0) {
            return;
        }

        $db = $this->getDatabase();
        $parent = (int) $table->parent;
        $package = $table->package;
        $query = $db->createQuery()
            ->select('COALESCE(MAX(' . $db->quoteName('ordering') . '), 0) + 1')
            ->from($db->quoteName('#__facileforms_compmenus'))
            ->where($db->quoteName('parent') . ' = :parent')
            ->where($db->quoteName('package') . ' = :package')
            ->bind(':parent', $parent, ParameterType::INTEGER)
            ->bind(':package', $package);
        $table->ordering = (int) $db->setQuery($query)->loadResult();
    }

    /**
     * Items are ordered among the siblings of their package.
     */
    protected function getReorderConditions($table)
    {
        $db = $this->getDatabase();

        return [
            $db->quoteName('parent') . ' = ' . (int) $table->parent,
            $db->quoteName('package') . ' = ' . $db->quote((string) $table->package),
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public function save($data)
    {
        if (!parent::save($data)) {
            return false;
        }

        $table = $this->getTable();

        if ($table->load((int) $this->getState($this->getName() . '.id'))) {
            $table->reorder($this->getReorderConditions($table));
        }

        return true;
    }

    /**
     * Deletes the items together with their children.
     *
     * @param array<int|string, mixed> $pks
     */
    public function delete(&$pks)
    {
        $pks = ArrayHelper::toInteger((array) $pks);

        if ($pks !== []) {
            $db = $this->getDatabase();
            $query = $db->createQuery()
                ->select($db->quoteName('id'))
                ->from($db->quoteName('#__facileforms_compmenus'))
                ->whereIn($db->quoteName('parent'), $pks);
            $children = array_map('intval', $db->setQuery($query)->loadColumn());
            $pks = array_values(array_unique([...$pks, ...$children]));
        }

        return parent::delete($pks);
    }

    /**
     * Copies the items together with their children.
     *
     * @param int[] $ids
     */
    public function copy(array $ids): void
    {
        $db = $this->getDatabase();

        foreach ($ids as $id) {
            $source = $this->getTable();

            if (!$source->load($id)) {
                continue;
            }

            $data = get_object_vars($source);
            $data['id'] = 0;
            $data['title'] = $source->title . ' (copy)';
            $newId = $this->store($data);

            $query = $db->createQuery()
                ->select($db->quoteName('id'))
                ->from($db->quoteName('#__facileforms_compmenus'))
                ->where($db->quoteName('parent') . ' = :parent')
                ->bind(':parent', $id, ParameterType::INTEGER);

            foreach (array_map('intval', $db->setQuery($query)->loadColumn()) as $childId) {
                $child = $this->getTable();

                if (!$child->load($childId)) {
                    continue;
                }

                $childData = get_object_vars($child);
                $childData['id'] = 0;
                $childData['parent'] = $newId;
                $this->store($childData);
            }
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function store(array $data): int
    {
        $this->setState($this->getName() . '.id', 0);

        if (!$this->save($data)) {
            throw new \RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));
        }

        return (int) $this->getState($this->getName() . '.id');
    }
}
