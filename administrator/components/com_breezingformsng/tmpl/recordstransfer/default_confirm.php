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

$sourceById = [];

foreach ($this->sourceElements as $source) {
    $sourceById[(int) $source['id']] = $source;
}

$used = array_filter($this->map);
$unused = array_filter($this->sourceElements, static fn (array $source): bool => !in_array((int) $source['id'], $used, true));
?>
<div class="card mb-3">
  <div class="card-header"><?= Text::_('COM_BREEZINGFORMSNG_TRANSFER_STEP_CONFIRM'); ?></div>
  <div class="card-body">
    <p class="text-muted"><?= Text::_('COM_BREEZINGFORMSNG_TRANSFER_CONFIRM_DESC'); ?></p>
    <dl class="row">
      <dt class="col-sm-3"><?= Text::_('COM_BREEZINGFORMSNG_TRANSFER_SOURCE_FORM'); ?></dt>
      <dd class="col-sm-9"><?= $this->sourceFormLink(); ?></dd>
      <dt class="col-sm-3"><?= Text::_('COM_BREEZINGFORMSNG_TRANSFER_TARGET_FORM'); ?></dt>
      <dd class="col-sm-9"><?= $this->targetFormLink(); ?></dd>
      <dt class="col-sm-3"><?= Text::_('COM_BREEZINGFORMSNG_TRANSFER_RECORDS'); ?></dt>
      <dd class="col-sm-9"><?= count($this->keptIds); ?></dd>
    </dl>

    <div class="table-responsive">
    <table class="table table-striped" id="transferSummary">
      <caption class="visually-hidden"><?= Text::_('COM_BREEZINGFORMSNG_TRANSFER_STEP_MAPPING'); ?></caption>
      <thead>
        <tr>
          <th scope="col"><?= Text::_('COM_BREEZINGFORMSNG_TRANSFER_SOURCE_FIELD'); ?></th>
          <th scope="col"><?= Text::_('COM_BREEZINGFORMSNG_TRANSFER_TARGET_FIELD'); ?></th>
          <th scope="col"><?= Text::_('COM_BREEZINGFORMSNG_TRANSFER_DEFAULT_VALUE'); ?></th>
          <th scope="col"><?= Text::_('COM_BREEZINGFORMSNG_TRANSFER_EXAMPLE'); ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($this->targetElements as $element) : ?>
            <?php $targetId = (int) $element['id']; ?>
            <?php $source = $sourceById[$this->map[$targetId] ?? 0] ?? null; ?>
            <?php $default = $this->defaults[$targetId] ?? ''; ?>
          <tr>
            <th scope="row"><?= $source !== null ? $this->elementIcon($source) . $this->elementLabel($source) : '<span class="text-muted">' . Text::_('COM_BREEZINGFORMSNG_TRANSFER_NO_SOURCE') . '</span>'; ?></th>
            <td><?= $this->elementIcon($element); ?><?= $this->elementLabel($element); ?></td>
            <td><?= $default !== '' ? $this->escape($default) : '<span class="text-muted">—</span>'; ?></td>
            <?php $example = trim(preg_replace('/\s*\R\s*/u', ' / ', (string) ($this->example[$targetId] ?? '')) ?? ''); ?>
            <td class="text-break"><?= $example !== '' ? $this->escape(mb_strimwidth($example, 0, 120, '…')) : '<span class="text-muted">—</span>'; ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    </div>

    <?php if ($unused !== []) : ?>
      <div class="alert alert-warning">
        <strong><?= Text::_('COM_BREEZINGFORMSNG_TRANSFER_UNMAPPED_SOURCE'); ?></strong>
        <?= implode(', ', array_map(fn (array $source): string => $this->elementIcon($source) . $this->elementLabel($source), $unused)); ?>
      </div>
    <?php endif; ?>

    <div class="form-check">
      <input class="form-check-input" type="checkbox" name="preserve_payment" id="transfer-preserve-payment" value="1">
      <label class="form-check-label" for="transfer-preserve-payment"><?= Text::_('COM_BREEZINGFORMSNG_TRANSFER_PRESERVE_PAYMENT'); ?></label>
    </div>
    <div class="form-check">
      <input class="form-check-input" type="checkbox" name="confirm_payment" id="transfer-confirm-payment" value="1">
      <label class="form-check-label" for="transfer-confirm-payment"><?= Text::_('COM_BREEZINGFORMSNG_TRANSFER_CONFIRM_PAYMENT'); ?></label>
    </div>

    <?php if ($this->canTrash) : ?>
      <div class="form-check">
        <input class="form-check-input" type="checkbox" name="trash" id="transfer-trash" value="1"<?= $this->trash ? ' checked' : ''; ?>>
        <label class="form-check-label" for="transfer-trash"><?= Text::_('COM_BREEZINGFORMSNG_TRANSFER_TRASH_SOURCE'); ?></label>
        <div class="form-text"><?= Text::_('COM_BREEZINGFORMSNG_TRANSFER_TRASH_SOURCE_DESC'); ?></div>
      </div>
    <?php endif; ?>
  </div>
</div>
