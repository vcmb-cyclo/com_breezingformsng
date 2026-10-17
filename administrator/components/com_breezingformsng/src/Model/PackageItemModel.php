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

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Date\Date;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Form\FormFactoryInterface;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Versioning\VersionableModelInterface;
use Joomla\CMS\Versioning\VersionableModelTrait;
use Joomla\Utilities\ArrayHelper;

/**
 * A code item kept in a package: a script or a piece.
 *
 * Its versions use the Joomla 6 version history of models, as articles do:
 * AdminModel::save() stores a version, AdminModel::delete() deletes them.
 */
abstract class PackageItemModel extends AdminModel implements VersionableModelInterface
{
    use VersionableModelTrait;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct($config = [], ?MVCFactoryInterface $factory = null, ?FormFactoryInterface $formFactory = null)
    {
        parent::__construct($config, $factory, $formFactory);

        $this->typeAlias = 'com_breezingformsng.' . $this->getName();
    }

    /**
     * @param string               $name
     * @param string               $prefix
     * @param array<string, mixed> $options
     */
    public function getTable($name = '', $prefix = 'Site', $options = [])
    {
        return parent::getTable($name ?: ucfirst($this->getName()), $prefix, $options);
    }

    /**
     * @param array<string, mixed> $data
     * @param bool                 $loadData
     */
    public function getForm($data = [], $loadData = true)
    {
        return $this->loadForm('com_breezingformsng.' . $this->getName(), $this->getName(), ['control' => 'jform', 'load_data' => $loadData]);
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

        $data = $application->getUserState('com_breezingformsng.edit.' . $this->getName() . '.data', []);

        if (empty($data)) {
            $data = $this->getItem();
        }

        $this->preprocessData('com_breezingformsng.' . $this->getName(), $data);

        return $data;
    }

    /**
     * Stamps the author and the date of the change.
     */
    protected function prepareTable($table)
    {
        $now = (new Date())->toSql();
        $username = (string) $this->getCurrentUser()->username;

        if ((int) $table->id === 0) {
            $table->created = $now;
            $table->created_by = $username;
        }

        $table->package = (string) $table->package;
        $table->modified = $now;
        $table->modified_by = $username;
    }

    /**
     * Joomla stops at the first item it cannot delete and still runs the
     * controller hook: $pks ends up holding the ids actually deleted.
     *
     * @param array<mixed> $pks
     */
    public function delete(&$pks)
    {
        $ids = array_values(array_unique(ArrayHelper::toInteger((array) $pks)));
        $deleted = parent::delete($pks);
        $pks = $ids;

        if ($ids !== []) {
            $db = $this->getDatabase();
            $table = $this->getTable();
            $query = $db->createQuery()
                ->select($db->quoteName('id'))
                ->from($db->quoteName($table->getTableName()))
                ->whereIn($db->quoteName('id'), $ids);
            $pks = array_values(array_diff($ids, array_map('intval', $db->setQuery($query)->loadColumn())));
        }

        return $deleted;
    }

    /**
     * @param int[] $ids
     */
    public function copy(array $ids): int
    {
        $copied = 0;

        foreach ($ids as $id) {
            $source = $this->getTable();

            if (!$source->load($id)) {
                continue;
            }

            $data = get_object_vars($source);
            $data['id'] = 0;
            $this->setState($this->getName() . '.id', 0);

            if (!$this->save($data)) {
                throw new \RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));
            }

            $copied++;
        }

        return $copied;
    }
}
