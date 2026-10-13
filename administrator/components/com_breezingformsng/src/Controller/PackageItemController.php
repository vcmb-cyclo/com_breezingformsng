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
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Router\Route;
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

    protected function postSaveHook(BaseDatabaseModel $model, $validData = [])
    {
        (new ActionLogger($this->app))->log(
            ActionLogger::SAVED,
            $this->logType(),
            [(int) $model->getState($model->getName() . '.id')],
            (string) ($validData['name'] ?? '')
        );
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

        $this->releaseEditId($context, $itemId);
        $this->holdEditId($context, $targetId);
        $this->setRedirect(Route::_($this->itemUrl($targetId, $this->input->getInt('test_context', 0) === 1), false));
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
