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
use Joomla\CMS\MVC\Model\FormModel;

/**
 * The form of the CSV import of records.
 */
class RecordsimportModel extends FormModel
{
    public function getForm($data = [], $loadData = true)
    {
        return $this->loadForm(
            'com_breezingformsng.recordsimport',
            'recordsimport',
            ['control' => 'jform', 'load_data' => $loadData]
        );
    }

    /**
     * What was last submitted or, on opening, the form the list is filtered on.
     */
    protected function loadFormData()
    {
        $application = Factory::getApplication();

        if (!$application instanceof CMSApplication) {
            throw new \RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));
        }

        $data = $application->getUserState('com_breezingformsng.recordsimport.data', []);

        if (empty($data)) {
            $filters = (array) $application->getUserState('com_breezingformsng.records.filter', []);
            $data = ['form_id' => (int) ($filters['form'] ?? 0) ?: ''];
        }

        $this->preprocessData('com_breezingformsng.recordsimport', $data);

        return $data;
    }
}
