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

class ScriptModel extends AdminModel
{
    /**
     * Script types with the language key of their name.
     */
    public const TYPES = [
        'Untyped' => 'COM_BREEZINGFORMSNG_SCRIPTS_UNTYPED',
        'Element Init' => 'COM_BREEZINGFORMSNG_SCRIPTS_ELEMENTINIT',
        'Element Action' => 'COM_BREEZINGFORMSNG_SCRIPTS_ELEMENTACTION',
        'Element Validation' => 'COM_BREEZINGFORMSNG_SCRIPTS_ELEMENTVALID',
        'Form Init' => 'COM_BREEZINGFORMSNG_SCRIPTS_FORMINIT',
        'Form Submitted' => 'COM_BREEZINGFORMSNG_SCRIPTS_FORMSUBMIT',
    ];

    public function getTable($name = 'Script', $prefix = 'Site', $options = [])
    {
        return parent::getTable($name, $prefix, $options);
    }

    public function getForm($data = [], $loadData = true)
    {
        return $this->loadForm('com_breezingformsng.script', 'script', ['control' => 'jform', 'load_data' => $loadData]);
    }

    protected function loadFormData()
    {
        $application = Factory::getApplication();

        if (!$application instanceof CMSApplication) {
            throw new \RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));
        }

        $data = $application->getUserState('com_breezingformsng.edit.script.data', []);

        if (empty($data)) {
            $data = $this->getItem();
        }

        $this->preprocessData('com_breezingformsng.script', $data);

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
