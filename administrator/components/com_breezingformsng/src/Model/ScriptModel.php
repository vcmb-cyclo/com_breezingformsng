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

namespace Vcmb\Component\BreezingformsNG\Administrator\Model;

\defined('_JEXEC') or die;

class ScriptModel extends PackageItemModel
{
    /**
     * Script types with the language key of their name.
     */
    public const TYPES = [
        'Untyped' => 'COM_BREEZINGFORMSNG_SCRIPTS_UNTYPED',
        'Element Init' => 'COM_BREEZINGFORMSNG_SCRIPTS_ELEMENTINIT',
        'Element Action' => 'COM_BREEZINGFORMSNG_SCRIPTS_ELEMENTACTION',
        'Element Validation' => 'COM_BREEZINGFORMSNG_SCRIPTS_ELEMENTVALID',
        'Form Init' => 'COM_BREEZINGFORMSNG_SCRIPTS_FORMINIT',
        'Form Submitted' => 'COM_BREEZINGFORMSNG_SCRIPTS_FORMSUBMIT',
    ];
}
