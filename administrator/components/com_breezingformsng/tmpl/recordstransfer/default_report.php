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

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$copied = (int) ($this->report['copied'] ?? 0);
$trashed = (int) ($this->report['trashed'] ?? 0);
?>
<div class="card mb-3">
  <div class="card-header"><?= Text::_('COM_BREEZINGFORMSNG_TRANSFER_STEP_REPORT'); ?></div>
  <div class="card-body">
    <div class="alert alert-success">
      <?= Text::plural('COM_BREEZINGFORMSNG_TRANSFER_N_COPIED', $copied); ?>
    </div>
    <?php if ($trashed > 0) : ?>
      <div class="alert alert-info"><?= Text::plural('COM_BREEZINGFORMSNG_TRANSFER_N_TRASHED', $trashed); ?></div>
    <?php endif; ?>
    <?php if (!empty($this->report['trashFailed'])) : ?>
      <div class="alert alert-warning"><?= Text::_('COM_BREEZINGFORMSNG_TRANSFER_TRASH_FAILED'); ?></div>
    <?php endif; ?>
    <p>
      <?= $this->sourceFormLink(); ?>
      <span class="fa-solid fa-arrow-right-long" aria-hidden="true"></span>
      <?= $this->targetFormLink(); ?>
    </p>
    <a class="btn btn-primary" href="<?= Route::_('index.php?option=com_breezingformsng&view=records&filter[form]=' . $this->targetId); ?>">
      <?= Text::_('COM_BREEZINGFORMSNG_TRANSFER_VIEW_TARGET'); ?>
    </a>
  </div>
</div>
