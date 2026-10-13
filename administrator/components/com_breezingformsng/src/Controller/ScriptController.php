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

use Vcmb\Component\BreezingformsNG\Administrator\Service\ActionLogger;

class ScriptController extends PackageItemController
{
    /**
     * A script runs in the browser, so its test page changes nothing.
     */
    protected function readOnlyTasks(): array
    {
        return ['edit', 'cancel', 'previous', 'next', 'test'];
    }

    protected function logType(): string
    {
        return ActionLogger::SCRIPT;
    }
}
