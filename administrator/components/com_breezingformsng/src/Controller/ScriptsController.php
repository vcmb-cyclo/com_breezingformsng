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

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Router\Route;
use Joomla\Utilities\ArrayHelper;
use Vcmb\Component\BreezingformsNG\Administrator\Model\ScriptModel;
use Vcmb\Component\BreezingformsNG\Administrator\Service\ActionLogger;
use Vcmb\Component\BreezingformsNG\Administrator\Service\AreaAccess;

/**
 * @property CMSApplication $app
 */
class ScriptsController extends AdminController
{
    protected $text_prefix = 'COM_BREEZINGFORMSNG';

    /**
     * Every task of the list changes scripts, which is code.
     */
    public function execute($task)
    {
        $user = $this->app->getIdentity();

        if (!$user->authorise('core.manage', 'com_breezingformsng')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        AreaAccess::assert($user, AreaAccess::CODE);

        return parent::execute($task);
    }

    public function getModel($name = 'Script', $prefix = 'Administrator', $config = ['ignore_request' => true])
    {
        return parent::getModel($name, $prefix, $config);
    }

    public function publish()
    {
        $ids = ArrayHelper::toInteger((array) $this->input->get('cid', [], 'array'));
        $published = $this->getTask() === 'publish';

        parent::publish();

        if ($ids !== []) {
            (new ActionLogger($this->app))->log(
                $published ? ActionLogger::PUBLISHED : ActionLogger::UNPUBLISHED,
                ActionLogger::SCRIPT,
                $ids
            );
        }
    }

    public function copy(): void
    {
        $this->checkToken();

        $ids = ArrayHelper::toInteger((array) $this->input->get('cid', [], 'array'));
        $model = $this->getModel();

        if (!$model instanceof ScriptModel) {
            throw new \RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));
        }

        if ($ids === []) {
            $this->setMessage(Text::_($this->text_prefix . '_NO_ITEM_SELECTED'), 'warning');
        } else {
            $copied = $model->copy($ids);
            (new ActionLogger($this->app))->log(ActionLogger::COPIED, ActionLogger::SCRIPT, $ids);
            $this->setMessage(Text::plural('COM_BREEZINGFORMSNG_SCRIPTS_N_COPIED', $copied));
        }

        $this->setRedirect(Route::_('index.php?option=com_breezingformsng&view=scripts', false));
    }

    protected function postDeleteHook(BaseDatabaseModel $model, $id = null)
    {
        $deleted = ArrayHelper::toInteger((array) $id);

        if ($deleted !== []) {
            (new ActionLogger($this->app))->log(ActionLogger::DELETED, ActionLogger::SCRIPT, $deleted);
        }
    }
}
