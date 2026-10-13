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

class ScriptsController extends PackageListController
{
    protected function logType(): string
    {
        return ActionLogger::SCRIPT;
    }

    protected function copiedMessage(): string
    {
        return 'COM_BREEZINGFORMSNG_SCRIPTS_N_COPIED';
    }
}
