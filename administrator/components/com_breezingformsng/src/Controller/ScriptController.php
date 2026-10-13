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
use Vcmb\Component\BreezingformsNG\Administrator\Model\ScriptsModel;
use Vcmb\Component\BreezingformsNG\Administrator\Service\ActionLogger;
use Vcmb\Component\BreezingformsNG\Administrator\Service\AreaAccess;

/**
 * @property CMSApplication $app
 */
class ScriptController extends FormController
{
    /** Tasks that only display a script; every other task changes code. */
    private const READ_ONLY_TASKS = ['edit', 'cancel', 'previous', 'next', 'test'];

    private const EDIT_CONTEXT = 'com_breezingformsng.edit.script';

    public function execute($task)
    {
        $user = $this->app->getIdentity();

        if (!$user->authorise('core.manage', 'com_breezingformsng')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        if (!\in_array(strtolower((string) $task), self::READ_ONLY_TASKS, true)) {
            AreaAccess::assert($user, AreaAccess::CODE);
        }

        return parent::execute($task);
    }

    /**
     * Opens the test page of the edited script.
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
            ActionLogger::SCRIPT,
            [(int) $model->getState($model->getName() . '.id')],
            (string) ($validData['name'] ?? '')
        );
    }

    /**
     * Moves to the neighbouring script, staying on the test page when
     * coming from it.
     */
    private function navigate(string $direction): void
    {
        $scriptId = $this->input->getInt('id', 0);
        $list = $this->factory->createModel('Scripts', 'Administrator');

        if (!$list instanceof ScriptsModel) {
            throw new \RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));
        }

        $targetId = $list->getAdjacentId($scriptId, $direction);

        $this->releaseEditId(self::EDIT_CONTEXT, $scriptId);
        $this->holdEditId(self::EDIT_CONTEXT, $targetId);
        $this->setRedirect(Route::_($this->itemUrl($targetId, $this->input->getInt('test_context', 0) === 1), false));
    }

    private function itemUrl(int $scriptId, bool $test): string
    {
        $url = 'index.php?option=com_breezingformsng&view=script&id=' . $scriptId;

        if (!$test) {
            return $url . '&layout=edit';
        }

        $testMode = $this->input->getCmd('test_mode', '');

        return $url . '&layout=test' . ($testMode !== '' ? '&test_mode=' . $testMode : '');
    }
}
