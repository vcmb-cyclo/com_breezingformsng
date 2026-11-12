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

declare(strict_types=1);

// Psalm loads the classes it analyses, unlike PHPStan: the Joomla core they
// extend must be autoloadable, from the copy extracted for both tools.
require_once __DIR__ . '/phpstan-bootstrap.php';
require_once __DIR__ . '/joomla-stubs/libraries/vendor/autoload.php';
