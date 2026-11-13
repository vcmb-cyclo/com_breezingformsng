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

/** @var \Vcmb\Component\BreezingformsNG\Administrator\View\Recordstransfer\HtmlView $this */

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$layout = $this->step;
$steps = ['select', 'mapping', 'confirm', 'report'];
?>
<form action="<?= Route::_('index.php?option=com_breezingformsng&view=recordstransfer&layout=' . $layout); ?>" method="post" name="adminForm" id="adminForm">
  <ul class="nav nav-pills mb-3" aria-label="<?= $this->escape(Text::_('COM_BREEZINGFORMSNG_TRANSFER_TITLE')); ?>">
    <?php foreach ($steps as $i => $step) : ?>
      <li class="nav-item">
        <span class="nav-link<?= $step === $layout ? ' active' : ' disabled'; ?>"<?= $step === $layout ? ' aria-current="step"' : ''; ?>>
          <?= $i + 1; ?>. <?= Text::_('COM_BREEZINGFORMSNG_TRANSFER_STEP_' . strtoupper($step)); ?>
        </span>
      </li>
    <?php endforeach; ?>
  </ul>

  <?= $this->loadTemplate($layout); ?>

  <input type="hidden" name="task" value="">
  <input type="hidden" name="boxchecked" value="<?= $layout === 'select' ? count($this->keptIds) : 0; ?>">
  <?= HTMLHelper::_('form.token'); ?>
</form>
