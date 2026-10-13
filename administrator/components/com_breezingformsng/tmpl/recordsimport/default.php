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

/** @var \Vcmb\Component\BreezingformsNG\Administrator\View\Recordsimport\HtmlView $this */

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
?>
<form action="<?= Route::_('index.php?option=com_breezingformsng&view=recordsimport'); ?>" method="post"
  enctype="multipart/form-data" name="adminForm" id="adminForm" class="form-validate">
  <div class="card">
    <div class="card-header"><?= Text::_('COM_BREEZINGFORMSNG_IMPORT_CSV_TITLE'); ?></div>
    <div class="card-body">
      <p class="text-muted"><?= Text::_('COM_BREEZINGFORMSNG_IMPORT_CSV_DESC'); ?></p>
      <?= $this->form->renderFieldset('import'); ?>
    </div>
  </div>

  <input type="hidden" name="task" value="">
  <?= HTMLHelper::_('form.token'); ?>
</form>
