<?php
/**
 * BreezingForms NG - A Joomla Forms Application
 *
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2008-2020 by Markus Bopp
 * @copyright Copyright (C) 2024-2026 by XDA+GIL - EVH
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 * */

defined('_JEXEC') or die('Direct Access to this location is not allowed.');

use Vcmb\Component\BreezingformsNG\Site\Service\Rendering\QuickMode\BootstrapRenderer;

/**
 * Facade kept for backward compatibility: PHP stored in the database
 * (facileforms_pieces.code, forms.piece*code) and external callers may
 * reference this class by name. The implementation now lives in
 * Site\Service\Rendering\QuickMode\BootstrapRenderer (Phase 9c, Bootstrap-theme).
 */
class BFQuickModeBootstrap extends BootstrapRenderer
{
}
