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

/** @var \Vcmb\Component\BreezingformsNG\Administrator\View\Piece\HtmlView $this */

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$row = $this->item;
$functionName = $this->functionName;
$paramNames = $this->paramNames;
$paramDefaults = $this->paramDefaults;
$paramValues = $this->paramValues;
$result = $this->result;
$output = $this->output;
$error = $this->error;
$errorDetails = $this->errorDetails;
$testMode = $this->testMode;
$unitTestResult = $this->unitTestResult;

$unitTestFailures = !empty($unitTestResult['failures']) ? count($unitTestResult['failures']) : 0;
$showAutoOpenUnitWarning = $this->autoOpened
    && $unitTestResult !== []
    && ((isset($unitTestResult['error']) && $unitTestResult['error'] !== '') || $unitTestFailures > 0);
$autoOpenUnitWarningText = $unitTestFailures > 0
    ? Text::plural('COM_BREEZINGFORMSNG_TEST_UNIT_FAILURES', $unitTestFailures)
    : Text::_('COM_BREEZINGFORMSNG_TEST_UNIT_FAILURES_ON_OPEN');
$action = Route::_('index.php?option=com_breezingformsng&view=piece&layout=test&id=' . (int) $row->id);
?>
<form action="<?= $action; ?>" method="post" name="adminForm" id="adminForm">
  <?php if ($showAutoOpenUnitWarning) { ?>
    <div id="bf-piece-auto-unit-warning" class="alert alert-warning">
      <span class="icon-warning text-warning" aria-hidden="true"></span>
      <?php echo htmlspecialchars($autoOpenUnitWarningText, ENT_QUOTES); ?>
    </div>
  <?php } ?>
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="m-0"><?php echo Text::_('COM_BREEZINGFORMSNG_TEST_PHP_PIECE'); ?></h2>
    <button type="submit" class="btn btn-primary">
      <span class="icon-play" aria-hidden="true"></span>
      <?php echo Text::_('COM_BREEZINGFORMSNG_TEST_RUN'); ?>
    </button>
  </div>
  <h3><?php echo htmlspecialchars($row->title, ENT_QUOTES); ?></h3>
  <div class="card mb-3 bg-light">
    <div class="card-body">
      <div class="row">
        <div class="col-sm-6 col-md-4">
          <strong><?php echo Text::_('COM_BREEZINGFORMSNG_TEST_PHP_PIECE_ID'); ?>:</strong> <?php echo (int) $row->id; ?>
        </div>
        <div class="col-sm-6 col-md-4">
          <strong><?php echo Text::_('COM_BREEZINGFORMSNG_SCRIPTS_PACKAGE'); ?>:</strong> <?php echo htmlspecialchars($row->package, ENT_QUOTES); ?>
        </div>
        <div class="col-sm-6 col-md-4">
          <strong><?php echo Text::_('COM_BREEZINGFORMSNG_TEST_FUNCTION'); ?>:</strong> <?php echo htmlspecialchars($functionName, ENT_QUOTES); ?>
        </div>
      </div>
    </div>
  </div>
  <div class="card mb-3 bg-light">
    <div class="card-body">
      <div class="row">
        <div class="col-sm-6 col-md-3">
          <strong><?php echo Text::_('COM_BREEZINGFORMSNG_TEST_CREATED'); ?>:</strong> <?php echo $row->created ? HTMLHelper::date($row->created, 'Y-m-d H:i', true) : '-'; ?>
        </div>
        <div class="col-sm-6 col-md-3">
          <strong><?php echo Text::_('COM_BREEZINGFORMSNG_TEST_CREATED_BY'); ?>:</strong> <?php echo htmlspecialchars((string) $row->created_by, ENT_QUOTES); ?>
        </div>
        <div class="col-sm-6 col-md-3">
          <strong><?php echo Text::_('COM_BREEZINGFORMSNG_TEST_MODIFIED'); ?>:</strong> <?php echo $row->modified ? HTMLHelper::date($row->modified, 'Y-m-d H:i', true) : '-'; ?>
        </div>
        <div class="col-sm-6 col-md-3">
          <strong><?php echo Text::_('COM_BREEZINGFORMSNG_TEST_MODIFIED_BY'); ?>:</strong> <?php echo htmlspecialchars((string) $row->modified_by, ENT_QUOTES); ?>
        </div>
      </div>
    </div>
  </div>
  <?php if (!empty($row->description)) { ?>
    <div class="card mb-3">
      <div class="card-header"><?php echo Text::_('COM_BREEZINGFORMSNG_PIECES_DESCRIPTION'); ?></div>
      <div class="card-body">
        <div class="form-control bg-light" style="white-space: pre-wrap;">
          <?php echo HTMLHelper::_('content.prepare', $row->description); ?>
        </div>
      </div>
    </div>
  <?php } ?>
  <div class="accordion" id="bfPieceCodeAccordion">
    <div class="accordion-item bg-light">
      <h2 class="accordion-header" id="bfPieceCodeHeading">
        <button class="accordion-button collapsed bg-light" type="button" data-bs-toggle="collapse"
          data-bs-target="#bfPieceCodeCollapse" aria-expanded="false" aria-controls="bfPieceCodeCollapse">
          <?php echo Text::_('COM_BREEZINGFORMSNG_TEST_PIECE_CODE'); ?>
        </button>
      </h2>
      <div id="bfPieceCodeCollapse" class="accordion-collapse collapse" aria-labelledby="bfPieceCodeHeading"
        data-bs-parent="#bfPieceCodeAccordion">
        <div class="accordion-body bg-light">
          <pre><?php echo htmlspecialchars($row->code, ENT_QUOTES); ?></pre>
        </div>
      </div>
    </div>
    <?php if (trim((string) $row->unit_tests) !== '') { ?>
      <div class="accordion-item bg-light mt-3">
        <h2 class="accordion-header" id="bfPieceUnitTestsHeading">
          <button class="accordion-button collapsed bg-light" type="button" data-bs-toggle="collapse"
            data-bs-target="#bfPieceUnitTestsCollapse" aria-expanded="false" aria-controls="bfPieceUnitTestsCollapse">
            <?php echo Text::_('COM_BREEZINGFORMSNG_TEST_UNIT_TESTS'); ?>
          </button>
        </h2>
        <div id="bfPieceUnitTestsCollapse" class="accordion-collapse collapse" aria-labelledby="bfPieceUnitTestsHeading"
          data-bs-parent="#bfPieceCodeAccordion">
          <div class="accordion-body bg-light">
            <pre><?php echo htmlspecialchars((string) $row->unit_tests, ENT_QUOTES); ?></pre>
          </div>
        </div>
      </div>
    <?php } ?>

    <?php if (empty($functionName)) { ?>
    <p><?php echo Text::_('COM_BREEZINGFORMSNG_TEST_UNABLE_TO_DETECT_FUNCTION_SIGNATURE_PIECE'); ?></p>
  <?php } else { ?>
    <table cellpadding="4" cellspacing="1" border="0" class="adminform" style="width:100%;">
      <tr>
        <th align="left"><?php echo Text::_('COM_BREEZINGFORMSNG_TEST_PARAMETER'); ?></th>
        <th align="left"><?php echo Text::_('COM_BREEZINGFORMSNG_TEST_VALUE'); ?></th>
      </tr>
      <?php
      if (!count($paramNames)) {
        ?>
        <tr>
          <td><?php echo Text::_('COM_BREEZINGFORMSNG_TEST_NO_PARAMETER'); ?></td>
          <td>-</td>
          <td></td>
        </tr>
        <?php
      } else {
        $lastParamIndex = count($paramNames) - 1;
        for ($i = 0; $i < count($paramNames); $i++) {
          $name = $paramNames[$i];
          $default = isset($paramDefaults[$i]) ? $paramDefaults[$i] : '';
          $value = isset($paramValues[$i]) ? $paramValues[$i] : $default;
          ?>
          <tr>
            <td><?php echo htmlspecialchars($name, ENT_QUOTES); ?> :</td>
            <td>
              <input type="hidden" name="test_param_names[]" value="<?php echo htmlspecialchars($name, ENT_QUOTES); ?>" />
              <input type="hidden" name="test_param_defaults[]" value="<?php echo htmlspecialchars($default, ENT_QUOTES); ?>" />
              <input type="text" name="test_param_values[]" value="<?php echo htmlspecialchars($value, ENT_QUOTES); ?>" class="inputbox" />
            </td>
            <td>
              <?php if ($i === $lastParamIndex) { ?>
                <button type="submit" class="btn btn-primary">
                  <span class="icon-play" aria-hidden="true"></span>
                  <?php echo Text::_('COM_BREEZINGFORMSNG_TEST_RUN'); ?>
                </button>
              <?php } ?>
            </td>
          </tr>
          <?php
        }
      }
      ?>
    </table>
  <?php } ?>

  <?php
  $isEmptyResult = $result === '';
  $isSuccess = $result !== false && $result !== null && !$isEmptyResult;
  ?>
  <?php if ($error !== '') { ?>
    <div class="alert alert-danger bf-piece-test-alert">
      <span class="icon-times text-danger" aria-hidden="true"></span>
      <?php echo Text::_('COM_BREEZINGFORMSNG_TEST_INVALID'); ?>: <?php echo htmlspecialchars($error, ENT_QUOTES); ?>
      <?php if ($output !== '') { ?>
        <div><strong><?php echo Text::_('COM_BREEZINGFORMSNG_TEST_OUTPUT'); ?>:</strong></div>
        <pre><?php echo htmlspecialchars($output, ENT_QUOTES); ?></pre>
      <?php } ?>
      <?php if ($result !== null) { ?>
        <div><strong><?php echo Text::_('COM_BREEZINGFORMSNG_TEST_RESULT'); ?>:</strong></div>
        <pre><?php echo htmlspecialchars(var_export($result, true), ENT_QUOTES); ?></pre>
      <?php } ?>
      <?php if (!empty($errorDetails)) { ?>
        <div><strong><?php echo Text::_('COM_BREEZINGFORMSNG_TEST_EXCEPTION'); ?>:</strong></div>
        <pre><?php echo htmlspecialchars(print_r($errorDetails, true), ENT_QUOTES); ?></pre>
      <?php } ?>
      <div><strong><?php echo Text::_('COM_BREEZINGFORMSNG_TEST_PARAMETERS'); ?>:</strong></div>
      <pre><?php echo htmlspecialchars(print_r(array_combine($paramNames, $paramValues), true), ENT_QUOTES); ?></pre>
    </div>
  <?php } ?>
  <?php if ($error === '' && $output !== '') { ?>
    <p><strong><?php echo Text::_('COM_BREEZINGFORMSNG_TEST_OUTPUT'); ?>:</strong></p>
    <pre><?php echo htmlspecialchars($output, ENT_QUOTES); ?></pre>
  <?php } ?>
  <?php if ($error === '' && $result !== null) { ?>
    <div class="alert <?php echo $isEmptyResult ? 'alert-warning' : ($isSuccess ? 'alert-success' : 'alert-danger'); ?>">
      <strong><?php echo Text::_('COM_BREEZINGFORMSNG_TEST_RESULT'); ?>:</strong>
      <pre><?php echo htmlspecialchars(var_export($result, true), ENT_QUOTES); ?></pre>
      <?php if ($isEmptyResult) { ?>
        <div>
          <span class="icon-warning text-warning" aria-hidden="true"></span>
          <?php echo Text::_('COM_BREEZINGFORMSNG_TEST_WARNING_EMPTY_RESULT'); ?>
        </div>
      <?php } elseif ($isSuccess) { ?>
        <div>
          <span class="icon-check text-success" aria-hidden="true"></span>
          <?php echo Text::_('COM_BREEZINGFORMSNG_TEST_EXECUTED'); ?>
        </div>
      <?php } else { ?>
        <div>
          <span class="icon-times text-danger" aria-hidden="true"></span>
          <?php echo Text::_('COM_BREEZINGFORMSNG_TEST_INVALID_FALSE_RESULT'); ?>
        </div>
      <?php } ?>
      <?php if (!$isSuccess && !$isEmptyResult) { ?>
        <div><strong><?php echo Text::_('COM_BREEZINGFORMSNG_TEST_PARAMETERS'); ?>:</strong></div>
        <pre><?php echo htmlspecialchars(print_r(array_combine($paramNames, $paramValues), true), ENT_QUOTES); ?></pre>
      <?php } ?>
    </div>
  <?php } ?>
  <?php if (!empty($unitTestResult)) { ?>
    <?php
    $unitAlertClass = isset($unitTestResult['error']) ? 'alert-danger' : (isset($unitTestResult['warning']) ? 'alert-warning' : (empty($unitTestResult['failures']) ? 'alert-success' : 'alert-warning'));
    ?>
    <div class="alert <?php echo $unitAlertClass; ?>">
      <strong><?php echo Text::_('COM_BREEZINGFORMSNG_TEST_UNIT_TESTS'); ?>:</strong>
      <?php if (isset($unitTestResult['error'])) { ?>
        <div><?php echo htmlspecialchars($unitTestResult['error'], ENT_QUOTES); ?></div>
      <?php } elseif (isset($unitTestResult['warning'])) { ?>
        <div><?php echo htmlspecialchars($unitTestResult['warning'], ENT_QUOTES); ?></div>
      <?php } else { ?>
        <div><?php echo (int) $unitTestResult['passed']; ?>/<?php echo (int) $unitTestResult['total']; ?> <?php echo Text::_('COM_BREEZINGFORMSNG_TEST_PASSED_SHORT'); ?></div>
        <?php if (!empty($unitTestResult['failures'])) { ?>
          <div><strong><?php echo Text::_('COM_BREEZINGFORMSNG_TEST_DETAIL'); ?>:</strong></div>
          <pre><?php echo htmlspecialchars(implode("\n\n", $unitTestResult['failures']), ENT_QUOTES); ?></pre>
        <?php } ?>
      <?php } ?>
    </div>
  <?php } ?>

  <input type="hidden" name="task" value="piece.runTest">
  <input type="hidden" name="test_function" value="<?= $this->escape($functionName); ?>">
  <input type="hidden" name="test_context" value="1">
  <input type="hidden" name="test_mode" value="<?= $this->escape($testMode); ?>">
  <input type="hidden" name="auto_open_tests" value="0">
  <?= HTMLHelper::_('form.token'); ?>
</form>
