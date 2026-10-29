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
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Router\Route;
use Joomla\String\Inflector;
use Joomla\Utilities\ArrayHelper;
use Vcmb\Component\BreezingformsNG\Administrator\Model\PackageItemModel;
use Vcmb\Component\BreezingformsNG\Administrator\Service\ActionLogger;
use Vcmb\Component\BreezingformsNG\Administrator\Service\AjaxStateService;
use Vcmb\Component\BreezingformsNG\Administrator\Service\AreaAccess;

/**
 * List actions on the code items kept in packages: scripts and pieces.
 *
 * @property CMSApplication $app
 */
abstract class PackageListController extends AdminController
{
    protected $text_prefix = 'COM_BREEZINGFORMSNG';

    /**
     * The type of the items in the action log.
     */
    abstract protected function logType(): string;

    /**
     * The language key of the message telling how many items were copied.
     */
    abstract protected function copiedMessage(): string;

    /**
     * Every task of the list changes code.
     */
    public function execute($task)
    {
        $user = $this->app->getIdentity() ?? throw new \RuntimeException('The current user is not loaded.');

        if (!$user->authorise('core.manage', 'com_breezingformsng')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        AreaAccess::assert($user, AreaAccess::CODE);

        return parent::execute($task);
    }

    /**
     * @param string               $name
     * @param string               $prefix
     * @param array<string, mixed> $config
     */
    public function getModel($name = '', $prefix = 'Administrator', $config = ['ignore_request' => true])
    {
        return parent::getModel($name ?: ucfirst(Inflector::singularize($this->view_list)), $prefix, $config);
    }

    public function publish()
    {
        $ids = ArrayHelper::toInteger((array) $this->input->get('cid', [], 'array'));
        $published = $this->getTask() === 'publish';

        parent::publish();

        if ($ids !== []) {
            (new ActionLogger($this->app))->log(
                $published ? ActionLogger::PUBLISHED : ActionLogger::UNPUBLISHED,
                $this->logType(),
                $ids
            );
        }
    }

    /**
     * Publishes or unpublishes one item from the published icon of the list,
     * by Ajax, as the forms list does.
     */
    public function setPublished(): void
    {
        $id = $this->input->post->getInt('id', 0);
        $state = AjaxStateService::normalizeState($this->input->post->getInt('state', 0));
        $model = $this->getModel();
        $pks = [$id];
        $user = $this->app->getIdentity() ?? throw new \RuntimeException('The current user is not loaded.');

        $error = match (true) {
            !$this->checkToken('post', false) => Text::_('JINVALID_TOKEN'),
            !$user->authorise('core.edit.state', 'com_breezingformsng') => Text::_('JLIB_APPLICATION_ERROR_EDITSTATE_NOT_PERMITTED'),
            $id <= 0 || !$model instanceof PackageItemModel => Text::_('JERROR_AN_ERROR_HAS_OCCURRED'),
            !$model->publish($pks, $state) => (string) $model->getError(),
            default => null,
        };

        if ($error === null) {
            (new ActionLogger($this->app))->log($state === 1 ? ActionLogger::PUBLISHED : ActionLogger::UNPUBLISHED, $this->logType(), [$id]);
        }

        $this->app->mimeType = 'application/json';
        $this->app->setHeader('Content-Type', 'application/json; charset=utf-8', true);
        $this->app->sendHeaders();
        echo new JsonResponse($error === null ? AjaxStateService::success($state) : AjaxStateService::error($error));
        $this->app->close();
    }

    public function copy(): void
    {
        $this->checkToken();

        $ids = ArrayHelper::toInteger((array) $this->input->get('cid', [], 'array'));
        $model = $this->getModel();

        if (!$model instanceof PackageItemModel) {
            throw new \RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));
        }

        if ($ids === []) {
            $this->setMessage(Text::_($this->text_prefix . '_NO_ITEM_SELECTED'), 'warning');
        } else {
            $copied = $model->copy($ids);
            (new ActionLogger($this->app))->log(ActionLogger::COPIED, $this->logType(), $ids);
            $this->setMessage(Text::plural($this->copiedMessage(), $copied));
        }

        $this->setRedirect(Route::_('index.php?option=com_breezingformsng&view=' . $this->view_list, false));
    }

    protected function postDeleteHook(BaseDatabaseModel $model, $id = null)
    {
        $deleted = ArrayHelper::toInteger((array) $id);

        if ($deleted !== []) {
            (new ActionLogger($this->app))->log(ActionLogger::DELETED, $this->logType(), $deleted);
        }
    }
}
