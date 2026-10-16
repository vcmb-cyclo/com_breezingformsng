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

/** @var \Vcmb\Component\BreezingformsNG\Administrator\View\Script\HtmlView $this */

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$saved = (int) $this->item->id > 0;
$hasUnitTests = $saved && trim((string) $this->item->unit_tests) !== '';
$action = Route::_('index.php?option=com_breezingformsng&view=script&layout=edit&id=' . (int) $this->item->id);
?>
<form action="<?= $action; ?>" method="post" name="adminForm" id="adminForm">
  <?php if (!$this->canEdit): ?>
    <div class="alert alert-info"><?= Text::_('COM_BREEZINGFORMSNG_READ_ONLY_NOTICE'); ?></div>
  <?php endif; ?>

  <fieldset class="border-0 p-0 m-0"<?= $this->canEdit ? '' : ' disabled'; ?>>
    <div class="card mb-3">
      <div class="card-body">
        <?= $this->form->renderFieldset('details'); ?>
      </div>
    </div>

    <div class="card">
      <div class="card-body">
        <?= $this->form->renderField('code'); ?>
        <?= $this->form->renderField('unit_tests'); ?>

        <div class="text-muted">
          <?= Text::_('COM_BREEZINGFORMSNG_TEST_UNIT_TESTS_FORMAT_HINT'); ?>:
          <code>'12/ 02/2023 ' -> '12/02/2023'</code><br>
          <code>' abc ' -> 'abc'</code><br>
          <code>'' -> ''</code><br>
          <?= Text::_('COM_BREEZINGFORMSNG_TEST_UNIT_TESTS_TYPES_HINT'); ?>
        </div>
        <div class="mt-3">
          <button type="button" id="bf-edit-unit-tests-button" class="btn btn-secondary"
            onclick="return runUnitTestsFromEdit();"<?= $hasUnitTests ? '' : ' disabled aria-disabled="true"'; ?>>
            <span class="icon-play" aria-hidden="true"></span>
            <?= Text::_('COM_BREEZINGFORMSNG_TEST_UNIT_TESTS'); ?>
          </button>
        </div>
        <div id="bf-edit-script-unit-tests-status" class="alert mt-3" style="display:none;">
          <strong><?= Text::_('COM_BREEZINGFORMSNG_TEST_UNIT_TESTS'); ?>:</strong>
          <div id="bf-edit-script-unit-tests-summary"></div>
          <div id="bf-edit-script-unit-tests-details-wrap" style="display:none;">
            <div><strong><?= Text::_('COM_BREEZINGFORMSNG_TEST_DETAIL'); ?>:</strong></div>
            <pre id="bf-edit-script-unit-tests-details"></pre>
          </div>
        </div>
      </div>
    </div>
  </fieldset>

  <input type="hidden" name="task" value="">
  <input type="hidden" name="test_mode" value="">
  <?= HTMLHelper::_('form.token'); ?>
</form>
