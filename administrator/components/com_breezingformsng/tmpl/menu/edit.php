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

\defined('_JEXEC') or die;

/** @var \Vcmb\Component\BreezingformsNG\Administrator\View\Menu\HtmlView $this */

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$action = Route::_('index.php?option=com_breezingformsng&view=menu&layout=edit&id=' . (int) $this->item->id);
?>
<form action="<?= $action; ?>" method="post" name="adminForm" id="adminForm" class="form-validate" aria-label="<?= Text::_('COM_BREEZINGFORMSNG_MENUS_EDIT'); ?>">
  <div class="card">
    <div class="card-header"><?= Text::_('COM_BREEZINGFORMSNG_MENUS_EDIT'); ?></div>
    <div class="card-body">
      <?= $this->form->renderFieldset('details'); ?>
    </div>
  </div>

  <input type="hidden" name="task" value="">
  <?= HTMLHelper::_('form.token'); ?>
</form>
