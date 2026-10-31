<?php
/**
 * BreezingForms NG - A Joomla Forms Application
 *
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2008-2020 by Markus Bopp
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 *
 * SPDX-License-Identifier: GPL-2.0-or-later
 **/

defined('_JEXEC') or die('Direct Access to this location is not allowed.');

// Version constants, reserved names and runmodes stay here; the engine
// implementation is split into native services while public runtime helpers remain available.

global $ff_version, $ff_resnames, $ff_request, $ff_target;

$ff_version = '5.0.0 Beta (build 1)';
$ff_target = 0;

$ff_resnames = array(
	'ff_name',
	'ff_form',
	'ff_border',
	'ff_align',
	'ff_runmode',
	'ff_page',
	'ff_task',
	'ff_target',
	'ff_frame',
	'ff_suffix',
	'ff_top'
);

// The site always runs in frontend mode. Stored scripts and pieces may still
// compare $ff_processor->runmode with these public constants.
DEFINE('_FF_RUNMODE_FRONTEND', 0);
DEFINE('_FF_RUNMODE_BACKEND', 1);
DEFINE('_FF_RUNMODE_PREVIEW', 2);

require_once __DIR__ . '/runtime_functions.php';
