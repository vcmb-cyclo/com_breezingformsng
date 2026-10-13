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

\defined('_JEXEC') or die;

/** @var \Vcmb\Component\BreezingformsNG\Administrator\View\Script\HtmlView $this */

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Vcmb\Component\BreezingformsNG\Administrator\Model\ScriptModel;

$action = Route::_('index.php?option=com_breezingformsng&view=script&layout=test&id=' . (int) $this->item->id);
?>
<form action="<?= $action; ?>" method="post" name="adminForm" id="adminForm">
  <div id="bf-script-auto-unit-warning" class="alert alert-warning" style="display:none;">
    <span class="icon-warning text-warning" aria-hidden="true"></span>
    <span id="bf-script-auto-unit-warning-text"></span>
  </div>
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="m-0"><?php echo Text::_('COM_BREEZINGFORMSNG_TEST_SCRIPT'); ?></h2>
    <button type="button" class="btn btn-primary" onclick="return bfRunAllScriptTests();">
      <span class="icon-play" aria-hidden="true"></span>
      <?php echo Text::_('COM_BREEZINGFORMSNG_TEST_RUN'); ?>
    </button>
  </div>
  <h3><?php echo htmlspecialchars((string) $this->item->title, ENT_QUOTES); ?></h3>
  <div class="card mb-3 bg-light">
    <div class="card-body">
      <div class="row">
        <div class="col-sm-6 col-md-3">
          <strong><?php echo Text::_('COM_BREEZINGFORMSNG_TEST_SCRIPT_ID'); ?>:</strong> <?php echo (int) $this->item->id; ?>
        </div>
        <div class="col-sm-6 col-md-3">
          <strong><?php echo Text::_('COM_BREEZINGFORMSNG_SCRIPTS_PACKAGE'); ?>:</strong> <?php echo htmlspecialchars((string) $this->item->package, ENT_QUOTES); ?>
        </div>
        <div class="col-sm-6 col-md-3">
          <strong><?php echo Text::_('COM_BREEZINGFORMSNG_SCRIPTS_NAME'); ?>:</strong> <?php echo htmlspecialchars((string) $this->item->name, ENT_QUOTES); ?>
        </div>
        <div class="col-sm-6 col-md-3">
          <strong><?php echo Text::_('COM_BREEZINGFORMSNG_SCRIPTS_TYPE'); ?>:</strong> <?php echo isset(ScriptModel::TYPES[$this->item->type]) ? Text::_(ScriptModel::TYPES[$this->item->type]) : '???'; ?>
        </div>
      </div>
    </div>
  </div>
  <div class="card mb-3 bg-light">
    <div class="card-body">
      <div class="row">
        <div class="col-sm-6 col-md-3">
          <strong><?php echo Text::_('COM_BREEZINGFORMSNG_TEST_CREATED'); ?>:</strong> <?php echo !empty($this->item->created) ? HTMLHelper::date($this->item->created, 'Y-m-d H:i', true) : '-'; ?>
        </div>
        <div class="col-sm-6 col-md-3">
          <strong><?php echo Text::_('COM_BREEZINGFORMSNG_TEST_CREATED_BY'); ?>:</strong> <?php echo htmlspecialchars((string) ($this->item->created_by ?? ''), ENT_QUOTES); ?>
        </div>
        <div class="col-sm-6 col-md-3">
          <strong><?php echo Text::_('COM_BREEZINGFORMSNG_TEST_MODIFIED'); ?>:</strong> <?php echo !empty($this->item->modified) ? HTMLHelper::date($this->item->modified, 'Y-m-d H:i', true) : '-'; ?>
        </div>
        <div class="col-sm-6 col-md-3">
          <strong><?php echo Text::_('COM_BREEZINGFORMSNG_TEST_MODIFIED_BY'); ?>:</strong> <?php echo htmlspecialchars((string) ($this->item->modified_by ?? ''), ENT_QUOTES); ?>
        </div>
      </div>
    </div>
  </div>
  <?php if (!empty($this->item->description)) { ?>
    <div class="card mb-3">
      <div class="card-header"><?php echo Text::_('COM_BREEZINGFORMSNG_SCRIPTS_DESCRIPTION'); ?></div>
      <div class="card-body">
        <div class="form-control bg-light" style="white-space: pre-wrap;">
          <?php echo HTMLHelper::_('content.prepare', $this->item->description); ?>
        </div>
      </div>
    </div>
  <?php } ?>
  <div class="accordion" id="bfScriptCodeAccordion">
    <div class="accordion-item bg-light">
      <h2 class="accordion-header" id="bfScriptCodeHeading">
        <button class="accordion-button collapsed bg-light" type="button" data-bs-toggle="collapse"
          data-bs-target="#bfScriptCodeCollapse" aria-expanded="false" aria-controls="bfScriptCodeCollapse">
          <?php echo Text::_('COM_BREEZINGFORMSNG_TEST_SCRIPT_CODE'); ?>
        </button>
      </h2>
      <div id="bfScriptCodeCollapse" class="accordion-collapse collapse" aria-labelledby="bfScriptCodeHeading"
        data-bs-parent="#bfScriptCodeAccordion">
        <div class="accordion-body bg-light">
          <pre><?php echo htmlspecialchars((string) $this->item->code, ENT_QUOTES); ?></pre>
        </div>
      </div>
    </div>
  </div>
  <?php if (trim((string) $this->item->unit_tests) !== '') { ?>
    <div class="accordion mt-3" id="bfScriptUnitTestsAccordion">
      <div class="accordion-item bg-light">
        <h2 class="accordion-header" id="bfScriptUnitTestsHeading">
          <button class="accordion-button collapsed bg-light" type="button" data-bs-toggle="collapse"
            data-bs-target="#bfScriptUnitTestsCollapse" aria-expanded="false" aria-controls="bfScriptUnitTestsCollapse">
            <?php echo Text::_('COM_BREEZINGFORMSNG_TEST_UNIT_TESTS'); ?>
          </button>
        </h2>
        <div id="bfScriptUnitTestsCollapse" class="accordion-collapse collapse" aria-labelledby="bfScriptUnitTestsHeading"
          data-bs-parent="#bfScriptUnitTestsAccordion">
          <div class="accordion-body bg-light">
            <pre><?php echo htmlspecialchars((string) $this->item->unit_tests, ENT_QUOTES); ?></pre>
          </div>
        </div>
      </div>
    </div>
  <?php } ?>
  <div class="card mb-3 bg-light">
    <div class="card-body">
      <label for="bf-script-function"><strong><?php echo Text::_('COM_BREEZINGFORMSNG_TEST_FUNCTION'); ?></strong></label>
      <input type="text" id="bf-script-function" class="form-control" value="<?php echo htmlspecialchars((string) $this->functionName, ENT_QUOTES); ?>" />
      <small class="text-muted"><?php echo Text::_('COM_BREEZINGFORMSNG_TEST_VALUES_HELP'); ?></small>
    </div>
  </div>
  <div class="card mb-3">
    <div class="card-header"><?php echo Text::_('COM_BREEZINGFORMSNG_TEST_ARGUMENTS'); ?></div>
    <div class="card-body">
      <table cellpadding="4" cellspacing="0" border="0" class="adminlist table table-striped">
        <tr>
          <th><?php echo Text::_('COM_BREEZINGFORMSNG_TEST_PARAMETER'); ?></th>
          <th><?php echo Text::_('COM_BREEZINGFORMSNG_TEST_VALUE'); ?></th>
          <th></th>
        </tr>
        <?php if (!count($this->paramNames)) { ?>
          <tr>
            <td colspan="3"><?php echo Text::_('COM_BREEZINGFORMSNG_TEST_NO_PARAMETER_DETECTED'); ?></td>
          </tr>
        <?php } else { ?>
          <?php $lastParamIndex = count($this->paramNames) - 1; ?>
          <?php for ($i = 0; $i < count($this->paramNames); $i++) { ?>
            <?php
            $name = $this->paramNames[$i];
            $default = isset($this->paramDefaults[$i]) ? $this->paramDefaults[$i] : '';
            ?>
            <tr>
              <td><?php echo htmlspecialchars($name, ENT_QUOTES); ?></td>
              <td>
                <input
                  type="text"
                  class="inputbox bf-test-arg"
                  data-param="<?php echo htmlspecialchars($name, ENT_QUOTES); ?>"
                  value="<?php echo htmlspecialchars($default, ENT_QUOTES); ?>" />
              </td>
              <td>
                <?php if ($i === $lastParamIndex) { ?>
                  <button type="button" class="btn btn-primary" onclick="return bfRunAllScriptTests();">
                    <span class="icon-play" aria-hidden="true"></span>
                    <?php echo Text::_('COM_BREEZINGFORMSNG_TEST_RUN'); ?>
                  </button>
                <?php } ?>
              </td>
            </tr>
          <?php } ?>
        <?php } ?>
      </table>
    </div>
  </div>
  <div id="bf-script-test-error" class="alert alert-danger bf-piece-test-alert" style="display:none;">
    <span class="icon-times text-danger" aria-hidden="true"></span>
    <?php echo Text::_('COM_BREEZINGFORMSNG_TEST_INVALID'); ?>: <span id="bf-script-test-error-message"></span>
    <div id="bf-script-test-error-output-wrap" style="display:none;">
      <div><strong><?php echo Text::_('COM_BREEZINGFORMSNG_TEST_OUTPUT'); ?>:</strong></div>
      <pre id="bf-script-test-error-output"></pre>
    </div>
    <div id="bf-script-test-error-result-wrap" style="display:none;">
      <div><strong><?php echo Text::_('COM_BREEZINGFORMSNG_TEST_RESULT'); ?>:</strong></div>
      <pre id="bf-script-test-error-result"></pre>
    </div>
    <div><strong><?php echo Text::_('COM_BREEZINGFORMSNG_TEST_PARAMETERS'); ?>:</strong></div>
    <pre id="bf-script-test-error-params"></pre>
  </div>
  <div id="bf-script-test-output-wrap" style="display:none;">
    <p><strong><?php echo Text::_('COM_BREEZINGFORMSNG_TEST_OUTPUT'); ?>:</strong></p>
    <pre id="bf-script-test-output"></pre>
  </div>
  <div id="bf-script-test-status" class="alert" style="display:none;">
    <strong><?php echo Text::_('COM_BREEZINGFORMSNG_TEST_RESULT'); ?>:</strong>
    <pre id="bf-script-test-result"></pre>
    <div id="bf-script-test-status-warning" style="display:none;">
      <span class="icon-warning text-warning" aria-hidden="true"></span>
      <?php echo Text::_('COM_BREEZINGFORMSNG_TEST_WARNING_EMPTY_RESULT'); ?>
    </div>
    <div id="bf-script-test-status-success" style="display:none;">
      <span class="icon-check text-success" aria-hidden="true"></span>
      <?php echo Text::_('COM_BREEZINGFORMSNG_TEST_EXECUTED'); ?>
    </div>
    <div id="bf-script-test-status-invalid" style="display:none;">
      <span class="icon-times text-danger" aria-hidden="true"></span>
      <?php echo Text::_('COM_BREEZINGFORMSNG_TEST_INVALID_FALSE_RESULT'); ?>
    </div>
    <div id="bf-script-test-status-params-wrap" style="display:none;">
      <div><strong><?php echo Text::_('COM_BREEZINGFORMSNG_TEST_PARAMETERS'); ?>:</strong></div>
      <pre id="bf-script-test-status-params"></pre>
    </div>
  </div>
  <div id="bf-script-unit-tests-status" class="alert" style="display:none;">
    <strong><?php echo Text::_('COM_BREEZINGFORMSNG_TEST_UNIT_TESTS'); ?>:</strong>
    <div id="bf-script-unit-tests-summary"></div>
    <div id="bf-script-unit-tests-details-wrap" style="display:none;">
      <div><strong><?php echo Text::_('COM_BREEZINGFORMSNG_TEST_DETAIL'); ?>:</strong></div>
      <pre id="bf-script-unit-tests-details"></pre>
    </div>
  </div>
  <input type="hidden" name="task" value="">
  <input type="hidden" name="test_context" value="1">
  <input type="hidden" name="test_mode" value="<?= $this->escape($this->testMode); ?>">
  <?= HTMLHelper::_('form.token'); ?>
</form>
