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

namespace Vcmb\Component\BreezingformsNG\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\Database\DatabaseInterface;
use Joomla\CMS\Factory;
use Vcmb\Component\BreezingformsNG\Administrator\Model\RecordModel;
use Vcmb\Component\BreezingformsNG\Administrator\Model\RecordsimportModel;
use Vcmb\Component\BreezingformsNG\Administrator\Service\RecordImporter;

/**
 * @property CMSApplication $app
 */
class RecordsimportController extends BaseController
{
    private const FORM_URL = 'index.php?option=com_breezingformsng&view=recordsimport';
    private const LIST_URL = 'index.php?option=com_breezingformsng&view=records';
    private const DATA_STATE = 'com_breezingformsng.recordsimport.data';

    /**
     * Importing creates records.
     */
    public function execute($task)
    {
        $user = $this->app->getIdentity() ?? throw new \RuntimeException('The current user is not loaded.');

        if (
            !$user->authorise('core.manage', 'com_breezingformsng')
            || !$user->authorise('core.create', 'com_breezingformsng')
        ) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        return parent::execute($task);
    }

    public function import(): void
    {
        $this->checkToken();

        $model = $this->getModel('Recordsimport', 'Administrator');
        $records = $this->getModel('Record', 'Administrator', ['ignore_request' => true]);

        if (!$model instanceof RecordsimportModel || !$records instanceof RecordModel) {
            throw new \RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));
        }

        $data = (array) $this->input->post->get('jform', [], 'array');
        $files = (array) $this->input->files->get('jform', [], 'array');
        $upload = (array) ($files['csv_file'] ?? []);
        $data['csv_file'] = (string) ($upload['name'] ?? '');

        $form = $model->getForm($data, false);

        if (!$form instanceof Form) {
            throw new \RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));
        }

        $valid = $model->validate($form, $data);
        $file = (string) ($upload['tmp_name'] ?? '');

        if ($valid === false) {
            foreach ($model->getErrors() as $error) {
                $this->app->enqueueMessage($error instanceof \Throwable ? $error->getMessage() : (string) $error, 'warning');
            }
        } elseif ((int) ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($file)) {
            $this->app->enqueueMessage(Text::_('JLIB_MEDIA_ERROR_UPLOAD_INPUT'), 'warning');
            $valid = false;
        }

        if (!is_array($valid)) {
            unset($data['csv_file']);
            $this->app->setUserState(self::DATA_STATE, $data);
            $this->setRedirect(Route::_(self::FORM_URL, false));

            return;
        }

        $user = $this->app->getIdentity() ?? throw new \RuntimeException('The current user is not loaded.');
        $importer = new RecordImporter(
            Factory::getContainer()->get(DatabaseInterface::class),
            $records,
            $user
        );
        $imported = $importer->import((int) $valid['form_id'], $file, (string) $valid['encoding']);

        $this->app->setUserState(self::DATA_STATE, null);

        if ($imported > 0) {
            $this->setMessage(Text::plural('COM_BREEZINGFORMSNG_RECORDS_N_IMPORTED', $imported));
        } else {
            $this->setMessage(Text::_('COM_BREEZINGFORMSNG_RECORDS_IMPORT_NONE'), 'warning');
        }

        $this->setRedirect(Route::_(self::LIST_URL, false));
    }

    public function cancel(): void
    {
        $this->checkToken();
        $this->app->setUserState(self::DATA_STATE, null);
        $this->setRedirect(Route::_(self::LIST_URL, false));
    }
}
