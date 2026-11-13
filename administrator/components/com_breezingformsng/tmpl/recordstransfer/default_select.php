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

$dateFormat = Text::_('DATE_FORMAT_LC6');
?>
<div class="card mb-3">
  <div class="card-header"><?= Text::_('COM_BREEZINGFORMSNG_TRANSFER_STEP_SELECT'); ?></div>
  <div class="card-body">
    <div class="border border-primary rounded p-3 mb-4">
      <div class="row g-3 align-items-end">
        <div class="col-md-5">
          <div class="form-label fw-bold"><?= Text::_('COM_BREEZINGFORMSNG_TRANSFER_SOURCE_FORM'); ?></div>
          <div class="form-control-plaintext"><?= $this->sourceFormLink(); ?></div>
        </div>
        <div class="col-md-1 text-center d-none d-md-block pb-2" aria-hidden="true">
          <span class="fa-solid fa-arrow-right-long fs-3 text-primary"></span>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-bold" for="transfer-target"><?= Text::_('COM_BREEZINGFORMSNG_TRANSFER_TARGET_FORM'); ?></label>
          <select name="target" id="transfer-target" class="form-select" required>
            <option value=""><?= Text::_('COM_BREEZINGFORMSNG_TRANSFER_SELECT_FORM'); ?></option>
            <?php foreach ($this->forms as $form) : ?>
                <?php if ((int) $form->id !== $this->sourceId) : ?>
                <option value="<?= (int) $form->id; ?>"<?= (int) $form->id === $this->targetId ? ' selected' : ''; ?>><?= $this->escape((string) $form->title); ?></option>
                <?php endif; ?>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
    </div>
    <p class="text-muted"><?= Text::_('COM_BREEZINGFORMSNG_TRANSFER_SELECT_DESC'); ?></p>
    <?php if ($this->sourceDeleted) : ?>
      <div class="alert alert-info"><?= Text::_('COM_BREEZINGFORMSNG_TRANSFER_SOURCE_DELETED'); ?></div>
    <?php endif; ?>

    <div class="table-responsive">
    <table class="table" id="transferRecords">
      <caption class="visually-hidden"><?= Text::_('COM_BREEZINGFORMSNG_RECORDS'); ?></caption>
      <thead>
        <tr>
          <td class="w-1 text-center"><?= HTMLHelper::_('grid.checkall'); ?></td>
          <th scope="col"><?= Text::_('COM_BREEZINGFORMSNG_ID'); ?></th>
          <th scope="col"><?= Text::_('COM_BREEZINGFORMSNG_SUBMITTED'); ?></th>
          <th scope="col"><?= Text::_('COM_BREEZINGFORMSNG_USER'); ?></th>
          <th scope="col"><?= Text::_('COM_BREEZINGFORMSNG_IP'); ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($this->records as $i => $record) : ?>
            <?php $recordId = (int) $record->id; ?>
          <tr>
            <td class="text-center">
              <input type="checkbox" id="cb<?= $i; ?>" name="cid[]" value="<?= $recordId; ?>" class="form-check-input"
                <?= in_array($recordId, $this->keptIds, true) ? 'checked' : ''; ?> onclick="Joomla.isChecked(this.checked);">
            </td>
            <th scope="row"><label for="cb<?= $i; ?>"><?= $recordId; ?></label></th>
            <td><?= HTMLHelper::_('date', (string) $record->submitted, $dateFormat); ?></td>
            <td><?= $this->escape((string) ($record->user_full_name ?: $record->username)); ?></td>
            <td><?= $this->escape((string) $record->ip); ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    </div>
  </div>
</div>
