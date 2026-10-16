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
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\Database\DatabaseInterface;
use Vcmb\Component\BreezingformsNG\Administrator\Model\FormModel;
use Vcmb\Component\BreezingformsNG\Administrator\Model\QuickmodeModel;
use Vcmb\Component\BreezingformsNG\Administrator\Service\ContentBuilderFormSync;
use Vcmb\Component\BreezingformsNG\Administrator\Service\FormVersioning;

/**
 * Versions of a form. The Joomla Versions dialog sends its Restore button to
 * form.loadhistory, after the type alias com_breezingformsng.form.
 *
 * @property CMSApplication $app
 */
class FormController extends BaseController
{
    /**
     * Restores the chosen version as a new version of the form, then reopens
     * the form in QuickMode.
     *
     * The options, the structure and the new version are written in one
     * transaction: a failure leaves the form as it was. The form stays checked
     * out to the user, who restores from the QuickMode editor and goes back
     * to it; the editor checks it in when it is closed.
     */
    public function loadhistory(): void
    {
        $this->checkToken('get');

        $redirect = 'index.php?option=com_breezingformsng&view=forms';

        if (!$this->app->getIdentity()->authorise('core.edit', 'com_breezingformsng')) {
            $this->setMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
            $this->setRedirect(Route::_($redirect, false));

            return;
        }

        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $versioning = new FormVersioning($db);

        try {
            $version = $versioning->load($this->input->getInt('version_id', 0));
            $formId = $version['formId'];
            $forms = $this->getModel('Form', 'Administrator', ['ignore_request' => true]);
            $quickmode = $this->getModel('Quickmode', 'Administrator', ['ignore_request' => true]);

            if (!$forms instanceof FormModel || !$quickmode instanceof QuickmodeModel) {
                throw new \RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));
            }

            // A form someone else has open in QuickMode is not overwritten.
            if (!$forms->checkout($formId)) {
                throw new \RuntimeException((string) $forms->getError());
            }

            $redirect = 'index.php?option=com_breezingformsng&task=quickmode.display&form=' . $formId;
            $db->transactionStart();

            try {
                $versioning->restoreOptions($formId, $version['columns']);
                $quickmode->save(
                    $formId,
                    FormVersioning::resetMissingElementIds($version['structure'], $versioning->elementIds($formId))
                );
                $current = $versioning->record($formId);
                $db->transactionCommit();
            } catch (\Throwable $e) {
                $db->transactionRollback();

                throw $e;
            }

            $this->setMessage(Text::sprintf('COM_BREEZINGFORMSNG_FORM_VERSION_RESTORED', $version['version'], $current));
        } catch (\Throwable $e) {
            $this->setMessage(Text::sprintf('COM_BREEZINGFORMSNG_FORM_VERSION_RESTORE_FAILED', $e->getMessage()), 'error');
            $this->setRedirect(Route::_($redirect, false));

            return;
        }

        // ContentBuilder NG keeps its own copy of the elements: it follows the
        // restored form, and catches up at the next save if this fails (logged).
        if (!(new ContentBuilderFormSync($this->app, $db))->sync($formId)) {
            $this->app->enqueueMessage(Text::_('COM_BREEZINGFORMSNG_FORM_VERSION_SYNC_FAILED'), 'warning');
        }

        $this->setRedirect(Route::_($redirect, false));
    }
}
