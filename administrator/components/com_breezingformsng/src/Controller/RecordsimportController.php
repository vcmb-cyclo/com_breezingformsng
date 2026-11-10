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

        $uploadError = (int) ($upload['error'] ?? UPLOAD_ERR_NO_FILE);

        // Every problem of the submission is told at once: the form fields,
        // then the upload, so a second attempt does not reveal another one.
        $formErrors = $valid === false;
        if ($formErrors) {
            foreach ($model->getErrors() as $error) {
                $this->app->enqueueMessage($error instanceof \Throwable ? $error->getMessage() : (string) $error, 'warning');
            }
        }

        if ($uploadError !== UPLOAD_ERR_OK) {
            // The file field is required: its validation already tells a missing file.
            if ($uploadError !== UPLOAD_ERR_NO_FILE || !$formErrors) {
                $key = match ($uploadError) {
                    UPLOAD_ERR_NO_FILE => 'COM_BREEZINGFORMSNG_RECORDS_IMPORT_UPLOAD_REQUIRED',
                    UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'COM_BREEZINGFORMSNG_RECORDS_IMPORT_UPLOAD_TOO_LARGE',
                    default => 'COM_BREEZINGFORMSNG_RECORDS_IMPORT_UPLOAD_FAILED',
                };
                $this->app->enqueueMessage(Text::_($key), 'warning');
            }
            $valid = false;
        } elseif (!$formErrors && !is_uploaded_file($file)) {
            $this->app->enqueueMessage(Text::_('COM_BREEZINGFORMSNG_RECORDS_IMPORT_UPLOAD_FAILED'), 'warning');
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
            $ignored = $importer->getIgnoredColumns();
            if ($ignored !== []) {
                // File headers are untrusted and may be very long. Never include cell values.
                $names = array_map(static function (string $name): string {
                    $name = preg_replace('/[\x00-\x1f\x7f]/', ' ', $name) ?? '';
                    return '“' . htmlspecialchars(mb_strimwidth($name, 0, 80, '…'), ENT_QUOTES, 'UTF-8') . '”';
                }, array_slice($ignored, 0, 20));
                if (count($ignored) > 20) {
                    $names[] = '…';
                }
                $this->app->enqueueMessage(Text::sprintf(
                    'COM_BREEZINGFORMSNG_RECORDS_IMPORT_SKIPPED_COLUMNS',
                    implode(', ', $names)
                ), 'warning');
            }
        } else {
            $key = $importer->getError() ?? 'COM_BREEZINGFORMSNG_RECORDS_IMPORT_NONE';
            $name = basename(str_replace('\\', '/', (string) ($upload['name'] ?? '')));
            $message = match ($key) {
                'COM_BREEZINGFORMSNG_RECORDS_IMPORT_FILE_UNREADABLE',
                'COM_BREEZINGFORMSNG_RECORDS_IMPORT_HEADER_INVALID',
                'COM_BREEZINGFORMSNG_RECORDS_IMPORT_EMPTY' => Text::sprintf(
                    $key,
                    htmlspecialchars($name, ENT_QUOTES, 'UTF-8')
                ),
                'COM_BREEZINGFORMSNG_RECORDS_IMPORT_ENCODING_FAILED' => Text::sprintf(
                    $key,
                    htmlspecialchars((string) $valid['encoding'], ENT_QUOTES, 'UTF-8')
                ),
                default => Text::_($key),
            };
            $this->setMessage($message, 'warning');
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
