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

namespace Vcmb\Component\BreezingformsNG\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Router\Route;

class RecordController extends FormController
{
    public function save($key = null, $urlVar = null)
    {
        try {
            return parent::save($key, $urlVar);
        } catch (\OutOfBoundsException $exception) {
            $this->app->setUserState(
                $this->option . '.edit.' . $this->context . '.data',
                $this->input->post->get('jform', [], 'array')
            );
            $this->setMessage(Text::sprintf('JLIB_APPLICATION_ERROR_SAVE_FAILED', $exception->getMessage()), 'error');
            $this->setRedirect(Route::_(
                'index.php?option=' . $this->option . '&view=' . $this->view_item
                    . $this->getRedirectToItemAppend($this->input->getInt('id'), $urlVar ?? 'id'),
                false
            ));

            return false;
        }
    }

    /**
     * Records come from the submitted forms; none is created here.
     */
    protected function allowAdd($data = [])
    {
        return false;
    }
}
