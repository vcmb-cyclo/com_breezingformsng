<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

\defined('_JEXEC') or die;

/** @var \Vcmb\Component\BreezingformsNG\Administrator\View\Records\HtmlView $this */

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;

$record = $this->record;
$tz = Factory::getApplication()->get('offset');
$submitted = htmlspecialchars((string) ($record->submitted ?? ''));
$formSelection = $this->formSelection;
$searchTerm = $this->searchTerm;
$listOrder = $this->listOrder;
$listDirn = $this->listDirn;
$limit = $this->limit;
$limitStart = $this->limitStart;
$value = static fn(mixed $field): string => htmlspecialchars((string) ($field ?? ''), ENT_QUOTES, 'UTF-8');
$yesNo = static fn(mixed $field): string => Text::_((bool) $field ? 'JYES' : 'JNO');
$formEditorUrl = 'index.php?option=com_breezingformsng&task=quickmode.display&form=' . (int) $record->form;
$recordUrl = static function (int $recordId) use ($formSelection, $searchTerm, $listOrder, $listDirn, $limit, $limitStart): string {
  $query = [
    'option'           => 'com_breezingformsng',
    'view'             => 'records',
    'layout'           => 'edit',
    'record_id'        => $recordId,
    'form_selection'   => $formSelection,
    'filter_order'     => $listOrder,
    'filter_order_Dir' => $listDirn,
    'limit'            => $limit,
    'limitstart'       => $limitStart,
  ];
  if ($searchTerm !== '') {
    $query['searchterm'] = $searchTerm;
  }

  return 'index.php?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
};
?>
<form action="index.php?option=com_breezingformsng" method="post" name="adminForm" id="adminForm">

  <div class="card mb-3">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-striped mb-0">
          <tbody>
            <tr class="table-primary"><th colspan="2"><?= Text::_('COM_BREEZINGFORMSNG_RECORD_META'); ?></th></tr>
            <tr><th><?= Text::_('COM_BREEZINGFORMSNG_RECORDS_RECORDID'); ?></th><td><?= (int) $record->id; ?></td></tr>
            <tr><th><?= Text::_('COM_BREEZINGFORMSNG_RECORDS_SUBMITTED'); ?></th><td><?= $submitted; ?></td></tr>
            <tr>
              <th><?= Text::_('COM_BREEZINGFORMSNG_RECORDS_TITLE'); ?></th>
              <td>
                <a href="<?= htmlspecialchars($formEditorUrl, ENT_QUOTES, 'UTF-8'); ?>" title="<?= htmlspecialchars(Text::_('COM_BREEZINGFORMSNG_FORMS_OPEN_EDITOR'), ENT_QUOTES, 'UTF-8'); ?>">
                  <?= $value($record->form_title); ?>
                </a>
                <div class="small text-muted"><?= $value($record->form_name); ?></div>
              </td>
            </tr>
            <tr><th><?= Text::_('COM_BREEZINGFORMSNG_IP'); ?></th><td><?= $value($record->ip); ?></td></tr>
            <tr><th><?= Text::_('COM_BREEZINGFORMSNG_PROCESS_SUBMITTERUSERNAME'); ?></th><td><?= $value($record->username); ?></td></tr>
            <tr><th><?= Text::_('COM_BREEZINGFORMSNG_PROCESS_SUBMITTERFULLNAME'); ?></th><td><?= $value($record->user_full_name); ?></td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="card mb-3">
    <div class="card-body">
      <div class="d-flex align-items-center mb-2">
        <span
          class="text-muted"
          data-bs-toggle="tooltip"
          data-bs-placement="top"
          title="<?= htmlspecialchars(Text::_('COM_BREEZINGFORMSNG_RECORD_VALUES'), ENT_QUOTES, 'UTF-8'); ?>"
          role="button"
          aria-label="<?= htmlspecialchars(Text::_('COM_BREEZINGFORMSNG_RECORD_VALUES'), ENT_QUOTES, 'UTF-8'); ?>">
          <i class="fas fa-info-circle"></i>
        </span>
      </div>
      <?php foreach ($this->recordRows as $row): ?>
        <div class="row mb-3">
          <label class="col-sm-3 col-form-label" for="element_<?= (int) $row['element_id']; ?>">
            <strong><?= htmlspecialchars($row['title']); ?></strong>
            <small class="text-muted">(<?= htmlspecialchars($row['name']); ?>)</small>
          </label>
          <div class="col-sm-9">
            <textarea
              id="element_<?= (int) $row['element_id']; ?>"
              name="element[<?= (int) $row['element_id']; ?>]"
              class="form-control"
              rows="<?= (substr_count($row['value'], "\n") > 0) ? min(10, substr_count($row['value'], "\n") + 2) : 1; ?>"><?= htmlspecialchars($row['value']); ?></textarea>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="card">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-striped mb-0">
          <tbody>
            <tr class="table-primary"><th colspan="2"><?= Text::_('COM_BREEZINGFORMSNG_RECORDS_SUBMINFO'); ?></th></tr>
            <tr><th><?= Text::_('COM_BREEZINGFORMSNG_RECORDS_SUBMITTED'); ?></th><td><?= $submitted; ?></td></tr>
            <tr><th><?= Text::_('COM_BREEZINGFORMSNG_IP'); ?></th><td><?= $value($record->ip); ?></td></tr>
            <tr><th><?= Text::_('COM_BREEZINGFORMSNG_RECORDS_BROWSER'); ?></th><td><?= $value($record->browser); ?></td></tr>
            <tr><th><?= Text::_('COM_BREEZINGFORMSNG_RECORDS_OPSYS'); ?></th><td><?= $value($record->opsys); ?></td></tr>
            <tr><th><?= Text::_('COM_BREEZINGFORMSNG_RECORDS_PROVIDER'); ?></th><td><?= $value($record->provider); ?></td></tr>
            <tr><th><?= Text::_('COM_BREEZINGFORMSNG_USERID'); ?></th><td><?= (int) $record->user_id; ?></td></tr>
            <tr><th><?= Text::_('COM_BREEZINGFORMSNG_PROCESS_SUBMITTERUSERNAME'); ?></th><td><?= $value($record->username); ?></td></tr>
            <tr><th><?= Text::_('COM_BREEZINGFORMSNG_PROCESS_SUBMITTERFULLNAME'); ?></th><td><?= $value($record->user_full_name); ?></td></tr>
            <tr class="table-primary"><th colspan="2"><?= Text::_('COM_BREEZINGFORMSNG_RECORDS_RECORDINFO'); ?></th></tr>
            <tr><th><?= Text::_('COM_BREEZINGFORMSNG_RECORDS_RECORDID'); ?></th><td><?= (int) $record->id; ?></td></tr>
            <tr><th><?= Text::_('COM_BREEZINGFORMSNG_RECORDS_VIEWED'); ?></th><td><?= $yesNo($record->viewed); ?></td></tr>
            <tr><th><?= Text::_('COM_BREEZINGFORMSNG_RECORDS_EXPORTED'); ?></th><td><?= $yesNo($record->exported); ?></td></tr>
            <tr><th><?= Text::_('COM_BREEZINGFORMSNG_RECORDS_ARCHIVED'); ?></th><td><?= $yesNo($record->archived); ?></td></tr>
            <tr><th><?= Text::_('COM_BREEZINGFORMSNG_RECORDS_OPTED'); ?></th><td><?= $yesNo($record->opted); ?></td></tr>
            <tr class="table-primary"><th colspan="2"><?= Text::_('COM_BREEZINGFORMSNG_PAYMENT_INFORMATION'); ?></th></tr>
            <tr><th><?= Text::_('COM_BREEZINGFORMSNG_PAYMENT_TX_ID'); ?></th><td><?= $value($record->paypal_tx_id); ?></td></tr>
            <tr><th><?= Text::_('COM_BREEZINGFORMSNG_PAYMENT_TX_DATE'); ?></th><td><?= $value($record->paypal_payment_date); ?></td></tr>
            <tr><th><?= Text::_('COM_BREEZINGFORMSNG_PAYMENT_TESTACCOUNT'); ?></th><td><?= $yesNo($record->paypal_testaccount); ?></td></tr>
            <tr><th><?= Text::_('COM_BREEZINGFORMSNG_PAYMENT_DOWNLOAD_TRIES'); ?></th><td><?= (int) $record->paypal_download_tries; ?></td></tr>
            <tr class="table-primary"><th colspan="2"><?= Text::_('COM_BREEZINGFORMSNG_RECORDS_FORMINFO'); ?></th></tr>
            <tr><th><?= Text::_('COM_BREEZINGFORMSNG_ID'); ?></th><td><?= (int) $record->form; ?></td></tr>
            <tr><th><?= Text::_('COM_BREEZINGFORMSNG_RECORDS_TITLE'); ?></th><td><?= $value($record->form_title); ?></td></tr>
            <tr><th><?= Text::_('COM_BREEZINGFORMSNG_RECORDS_NAME'); ?></th><td><?= $value($record->form_name); ?></td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <input type="hidden" name="task" value="records.save">
  <input type="hidden" name="record_id" value="<?= (int) $record->id; ?>">
  <input type="hidden" name="cid[]" value="<?= (int) $record->id; ?>">
  <input type="hidden" name="form_selection" value="<?= $formSelection; ?>">
  <input type="hidden" name="searchterm" value="<?= htmlspecialchars($searchTerm, ENT_QUOTES, 'UTF-8'); ?>">
  <input type="hidden" name="filter_order" value="<?= htmlspecialchars($listOrder, ENT_QUOTES, 'UTF-8'); ?>">
  <input type="hidden" name="filter_order_Dir" value="<?= htmlspecialchars($listDirn, ENT_QUOTES, 'UTF-8'); ?>">
  <input type="hidden" name="limit" value="<?= $limit; ?>">
  <input type="hidden" name="limitstart" value="<?= $limitStart; ?>">
  <?= HTMLHelper::_('form.token'); ?>
</form>

<nav class="d-flex justify-content-between mt-3" aria-label="<?= Text::_('JLIB_HTML_PAGINATION'); ?>">
  <?php if ($this->prevRecordId !== null): ?>
    <a class="btn btn-secondary"
      href="<?= htmlspecialchars($recordUrl($this->prevRecordId), ENT_QUOTES, 'UTF-8'); ?>">
      &laquo; <?= Text::_('JPREVIOUS'); ?>
    </a>
  <?php else: ?>
    <span class="btn btn-secondary disabled">&laquo; <?= Text::_('JPREVIOUS'); ?></span>
  <?php endif; ?>

  <?php if ($this->nextRecordId !== null): ?>
    <a class="btn btn-secondary"
      href="<?= htmlspecialchars($recordUrl($this->nextRecordId), ENT_QUOTES, 'UTF-8'); ?>">
      <?= Text::_('JNEXT'); ?> &raquo;
    </a>
  <?php else: ?>
    <span class="btn btn-secondary disabled"><?= Text::_('JNEXT'); ?> &raquo;</span>
  <?php endif; ?>
</nav>

<?php
// Web assets for this view are registered in Records\HtmlView::prepareEditToolbar() —
// useScript() calls placed in the template body do not take effect here.
?>
