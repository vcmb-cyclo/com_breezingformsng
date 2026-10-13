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

/**
 * PHPUnit bootstrap for com_breezingformsng.
 *
 * Every class in this component starts with `\defined('_JEXEC') or die;`
 * (the standard Joomla direct-access guard) - it must be defined before
 * any component class is loaded, or the guard kills the PHP process
 * outright instead of throwing a catchable error.
 *
 * This scaffold intentionally targets pure-logic classes only (no Joomla
 * framework classes/services stubbed here). Tests for code that touches
 * Factory::getApplication(), the database, Text::_(), etc. need their own
 * mocks/doubles set up per test - that's out of scope for this bootstrap.
 */

\define('_JEXEC', 1);

require __DIR__ . '/../vendor/autoload.php';
