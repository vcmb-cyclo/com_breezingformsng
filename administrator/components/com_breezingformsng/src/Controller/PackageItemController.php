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
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Versioning\VersionableControllerTrait;
use Vcmb\Component\BreezingformsNG\Administrator\Model\PackageItemModel;
use Vcmb\Component\BreezingformsNG\Administrator\Model\PackageListModel;
use Vcmb\Component\BreezingformsNG\Administrator\Service\ActionLogger;
use Vcmb\Component\BreezingformsNG\Administrator\Service\AreaAccess;

/**
 * Editing of a code item kept in a package: a script or a piece.
 *
 * @property CMSApplication $app
 */
abstract class PackageItemController extends FormController
{
    use VersionableControllerTrait;

    /**
     * Tasks that only display the item; every other task changes or runs code.
     *
     * @return string[]
     */
    abstract protected function readOnlyTasks(): array;

    /**
     * The type of the item in the action log.
     */
    abstract protected function logType(): string;

    public function execute($task)
    {
        $user = $this->app->getIdentity();

        if (!$user->authorise('core.manage', 'com_breezingformsng')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        if (!\in_array(strtolower((string) $task), $this->readOnlyTasks(), true)) {
            AreaAccess::assert($user, AreaAccess::CODE);
        }

        return parent::execute($task);
    }

    /**
     * Opens the test page of the edited item.
     */
    public function test(): void
    {
        $this->setRedirect(Route::_($this->itemUrl($this->input->getInt('id', 0), true), false));
    }

    public function previous(): void
    {
        $this->navigate('prev');
    }

    public function next(): void
    {
        $this->navigate('next');
    }

    /**
     * @param  array<string, mixed>  $validData
     */
    protected function postSaveHook(BaseDatabaseModel $model, $validData = [])
    {
        $id = (int) $model->getState($model->getName() . '.id');
        $name = (string) ($validData['name'] ?? '');

        // A restored version comes without form data (VersionableControllerTrait).
        if ($name === '' && $model instanceof PackageItemModel) {
            $item = $model->getItem($id);
            $name = \is_object($item) ? (string) $item->name : '';
        }

        (new ActionLogger($this->app))->log(ActionLogger::SAVED, $this->logType(), [$id], $name);
    }

    /**
     * Moves to the neighbouring item, staying on the test page when
     * coming from it.
     */
    private function navigate(string $direction): void
    {
        $itemId = $this->input->getInt('id', 0);
        $context = $this->option . '.edit.' . $this->context;
        $list = $this->factory->createModel(ucfirst($this->view_list), 'Administrator');

        if (!$list instanceof PackageListModel) {
            throw new \RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));
        }

        $targetId = $list->getAdjacentId($itemId, $direction);
        $test = $this->input->getInt('test_context', 0) === 1;

        if ($targetId !== $itemId) {
            // Moving to another item checks it out and checks the current one in, as edit and close do.
            $model = $this->getModel();

            if (!$model instanceof PackageItemModel) {
                throw new \RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));
            }

            if (!$model->checkout($targetId)) {
                $this->setMessage(Text::sprintf('JLIB_APPLICATION_ERROR_CHECKOUT_FAILED', $model->getError()), 'error');
                $this->setRedirect(Route::_($this->itemUrl($itemId, $test), false));

                return;
            }

            $model->checkin($itemId);
        }

        $this->releaseEditId($context, $itemId);
        $this->holdEditId($context, $targetId);
        $this->setRedirect(Route::_($this->itemUrl($targetId, $test), false));
    }

    protected function itemUrl(int $itemId, bool $test): string
    {
        $url = 'index.php?option=com_breezingformsng&view=' . $this->view_item . '&id=' . $itemId;

        if (!$test) {
            return $url . '&layout=edit';
        }

        $testMode = $this->input->getCmd('test_mode', '');

        return $url . '&layout=test' . ($testMode !== '' ? '&test_mode=' . $testMode : '');
    }
}
