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
use Joomla\CMS\Date\Date;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\AdminModel;

/**
 * A code item kept in a package: a script or a piece.
 */
abstract class PackageItemModel extends AdminModel
{
    public function getTable($name = '', $prefix = 'Site', $options = [])
    {
        return parent::getTable($name ?: ucfirst($this->getName()), $prefix, $options);
    }

    public function getForm($data = [], $loadData = true)
    {
        return $this->loadForm('com_breezingformsng.' . $this->getName(), $this->getName(), ['control' => 'jform', 'load_data' => $loadData]);
    }

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
                throw new \RuntimeException((string) $this->getError());
            }

            $copied++;
        }

        return $copied;
    }
}
