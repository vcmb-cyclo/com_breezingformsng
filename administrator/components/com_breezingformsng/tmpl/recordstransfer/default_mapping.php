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
?>
<div class="card mb-3">
  <div class="card-header"><?= Text::_('COM_BREEZINGFORMSNG_TRANSFER_STEP_MAPPING'); ?></div>
  <div class="card-body">
    <p class="text-muted"><?= Text::_('COM_BREEZINGFORMSNG_TRANSFER_MAPPING_DESC'); ?></p>
    <?php if ($this->sourceDeleted) : ?>
      <div class="alert alert-info"><?= Text::_('COM_BREEZINGFORMSNG_TRANSFER_SOURCE_DELETED'); ?></div>
    <?php endif; ?>
    <p>
      <strong><?= $this->sourceFormLink(); ?></strong>
      <span class="fa-solid fa-arrow-right-long" aria-hidden="true"></span>
      <strong><?= $this->targetFormLink(); ?></strong>
    </p>
    <div class="table-responsive">
    <table class="table table-striped" id="transferMapping">
      <caption class="visually-hidden"><?= Text::_('COM_BREEZINGFORMSNG_TRANSFER_STEP_MAPPING'); ?></caption>
      <thead>
        <tr>
          <th scope="col"><?= Text::_('COM_BREEZINGFORMSNG_TRANSFER_SOURCE_FIELD'); ?></th>
          <th scope="col"><?= Text::_('COM_BREEZINGFORMSNG_TRANSFER_TARGET_FIELD'); ?></th>
          <th scope="col"><?= Text::_('COM_BREEZINGFORMSNG_TRANSFER_DEFAULT_VALUE'); ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($this->targetElements as $element) : ?>
            <?php $targetId = (int) $element['id']; ?>
          <tr<?= ($this->map[$targetId] ?? 0) > 0 ? ' class="bf-transfer-assigned"' : ''; ?>>
            <td>
              <label class="visually-hidden" for="map-<?= $targetId; ?>"><?= Text::_('COM_BREEZINGFORMSNG_TRANSFER_SOURCE_FIELD'); ?> — <?= $this->elementLabel($element); ?></label>
              <?php $selected = $sourceById[$this->map[$targetId] ?? 0] ?? null; ?>
              <?php $selectedIcon = $selected !== null ? $this->elementIconUrl($selected) : ''; ?>
              <div class="d-flex align-items-center gap-2">
                <img class="bf-transfer-icon flex-shrink-0<?= $selectedIcon === '' ? ' invisible' : ''; ?>" width="20" height="20" alt=""<?= $selectedIcon === '' ? '' : ' src="' . $this->escape($selectedIcon) . '"'; ?>>
                <select name="map[<?= $targetId; ?>]" id="map-<?= $targetId; ?>" class="form-select bf-transfer-source" data-bf-transfer-default="default-<?= $targetId; ?>">
                  <option value="0" data-icon=""><?= Text::_('COM_BREEZINGFORMSNG_TRANSFER_NO_SOURCE'); ?></option>
                  <?php foreach ($this->sourceOptions as $source) : ?>
                    <option value="<?= (int) $source['id']; ?>" class="<?= $this->elementIconClass($source); ?>" data-icon="<?= $this->escape($this->elementIconUrl($source)); ?>"<?= (int) $source['id'] === ($this->map[$targetId] ?? 0) ? ' selected' : ''; ?>><?= $this->elementLabel($source); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </td>
            <th scope="row">
              <?= $this->elementIcon($element); ?><?= $this->elementLabel($element); ?>
              <?php if (in_array($targetId, $this->requiredIds, true)) : ?>
                <span class="icon-asterisk bf-transfer-required text-danger" aria-hidden="true"></span><span class="visually-hidden"><?= Text::_('JREQUIRED'); ?></span>
              <?php endif; ?>
            </th>
            <td>
              <label class="visually-hidden" for="default-<?= $targetId; ?>"><?= Text::_('COM_BREEZINGFORMSNG_TRANSFER_DEFAULT_VALUE'); ?> — <?= $this->elementLabel($element); ?></label>
              <input type="text" name="defaults[<?= $targetId; ?>]" id="default-<?= $targetId; ?>" class="form-control"
                value="<?= $this->escape($this->defaults[$targetId] ?? ''); ?>"<?= ($this->map[$targetId] ?? 0) > 0 ? ' disabled' : ''; ?>>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    </div>
    <p class="form-text"><?= Text::_('COM_BREEZINGFORMSNG_TRANSFER_DEFAULT_VALUE_DESC'); ?></p>
    <?php if ($this->requiredIds !== []) : ?>
      <p class="form-text"><span class="icon-asterisk bf-transfer-required text-danger" aria-hidden="true"></span> <?= Text::_('COM_BREEZINGFORMSNG_TRANSFER_REQUIRED_NOTE'); ?></p>
    <?php endif; ?>
  </div>
</div>
