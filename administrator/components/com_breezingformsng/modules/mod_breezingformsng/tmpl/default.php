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

declare(strict_types=1);

defined('_JEXEC') or die;

if (!isset($form) || !is_string($form)) {
    throw new \RuntimeException(\Joomla\CMS\Language\Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));
}

echo $form;
