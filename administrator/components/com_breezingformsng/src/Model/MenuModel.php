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
use Joomla\CMS\Filter\OutputFilter;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\Database\ParameterType;
use Joomla\Utilities\ArrayHelper;

class MenuModel extends AdminModel
{
    public function getTable($name = 'Menu', $prefix = 'Site', $options = [])
    {
        return parent::getTable($name, $prefix, $options);
    }

    public function getForm($data = [], $loadData = true)
    {
        return $this->loadForm('com_breezingformsng.menu', 'menu', ['control' => 'jform', 'load_data' => $loadData]);
    }

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

        return ArrayHelper::toObject(get_object_vars($table));
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
        $query = $db->getQuery(true)
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
     */
    public function delete(&$pks)
    {
        $pks = ArrayHelper::toInteger((array) $pks);

        if ($pks !== []) {
            $db = $this->getDatabase();
            $query = $db->getQuery(true)
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

            $query = $db->getQuery(true)
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
            throw new \RuntimeException((string) $this->getError());
        }

        return (int) $this->getState($this->getName() . '.id');
    }

    public function syncToJoomlaMenu(MVCFactoryInterface $menusFactory): void
    {
        $db = $this->getDatabase();
        $linkPattern = 'index.php?option=com_breezingformsng&act=run%';
        $existingQuery = $db->getQuery(true)
            ->select($db->quoteName('id'))
            ->from($db->quoteName('#__menu'))
            ->where($db->quoteName('link') . ' LIKE :linkPattern')
            ->where($db->quoteName('checked_out') . ' = 0')
            ->bind(':linkPattern', $linkPattern);
        $existingIds = array_map('intval', $db->setQuery($existingQuery)->loadColumn() ?: []);

        $menuQ = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__facileforms_compmenus'))
            ->where($db->quoteName('published') . ' = 1')
            ->order($db->quoteName('id') . ' ASC');
        $items = $db->setQuery($menuQ)->loadObjectList() ?: [];

        $componentLink = 'index.php?option=com_breezingformsng';
        $parentRowQ = $db->getQuery(true)
            ->select($db->quoteName('id'))
            ->from($db->quoteName('#__menu'))
            ->where($db->quoteName('link') . ' = :componentLink')
            ->where($db->quoteName('client_id') . ' = 1')
            ->bind(':componentLink', $componentLink);
        $rootMenuId = (int) $db->setQuery($parentRowQ)->loadResult();

        if ($rootMenuId < 1) {
            return;
        }

        $extensionType = 'component';
        $element = 'com_breezingformsng';
        $extensionQuery = $db->getQuery(true)
            ->select($db->quoteName('extension_id'))
            ->from($db->quoteName('#__extensions'))
            ->where($db->quoteName('type') . ' = :extensionType')
            ->where($db->quoteName('element') . ' = :element')
            ->bind(':extensionType', $extensionType)
            ->bind(':element', $element);
        $componentId = (int) $db->setQuery($extensionQuery)->loadResult();

        if ($componentId < 1) {
            throw new \RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));
        }

        $idMap = [];

        $db->transactionStart();

        try {
            foreach ($existingIds as $existingId) {
                $table = $menusFactory->createTable('Menu', 'Administrator');

                if (!$table instanceof \Joomla\CMS\Table\Menu || !$table->delete($existingId, true)) {
                    throw new \RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));
                }
            }

            foreach ($items as $item) {
                $parentId = (int) $item->parent;
                $joomlaParentId = $parentId > 0 && isset($idMap[$parentId])
                    ? $idMap[$parentId]
                    : $rootMenuId;
                $title = (string) $item->title;
                $alias = OutputFilter::stringURLSafe($title) . '-' . (int) $item->id;
                $table = $menusFactory->createTable('Menu', 'Administrator');

                if (!$table instanceof \Joomla\CMS\Table\Menu) {
                    throw new \RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));
                }

                $table->setLocation($joomlaParentId, 'last-child');
                $data = [
                    'menutype' => 'main',
                    'title' => $title,
                    'alias' => $alias,
                    'link' => 'index.php?option=com_breezingformsng&act=run&ff_name=' . rawurlencode((string) $item->name),
                    'type' => 'component',
                    'published' => 1,
                    'parent_id' => $joomlaParentId,
                    'component_id' => $componentId,
                    'browserNav' => 0,
                    'access' => 0,
                    'params' => '',
                    'home' => 0,
                    'language' => '*',
                    'client_id' => 1,
                ];

                if (!$table->bind($data) || !$table->check() || !$table->store()) {
                    throw new \RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));
                }

                $idMap[(int) $item->id] = (int) $table->id;
            }

            $db->transactionCommit();
        } catch (\Throwable $exception) {
            $db->transactionRollback();
            throw $exception;
        }
    }
}
