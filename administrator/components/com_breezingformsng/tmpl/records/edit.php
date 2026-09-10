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
$systemLabel = static fn(string $label, string $description): string => sprintf(
    '<span class="hasTooltip" title="%s">%s</span>',
    htmlspecialchars(Text::_($description), ENT_QUOTES, 'UTF-8'),
    htmlspecialchars(Text::_($label), ENT_QUOTES, 'UTF-8'),
);
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
    <div class="card-header d-flex align-items-center">
      <span><?= Text::_('COM_BREEZINGFORMSNG_RECORD_META'); ?></span>
      <span class="badge bg-light text-dark border ms-2">
        <?= Text::_('COM_BREEZINGFORMSNG_ID'); ?> : <?= (int) $record->id; ?>
      </span>
      <div class="d-flex gap-2 ms-auto">
        <?php if ($this->prevRecordId !== null): ?>
          <a class="btn btn-sm btn-outline-secondary"
            href="<?= htmlspecialchars($recordUrl($this->prevRecordId), ENT_QUOTES, 'UTF-8'); ?>">
            &laquo; <?= Text::_('JPREVIOUS'); ?>
          </a>
        <?php else: ?>
          <span class="btn btn-sm btn-outline-secondary disabled">&laquo; <?= Text::_('JPREVIOUS'); ?></span>
        <?php endif; ?>

        <?php if ($this->nextRecordId !== null): ?>
          <a class="btn btn-sm btn-outline-secondary"
            href="<?= htmlspecialchars($recordUrl($this->nextRecordId), ENT_QUOTES, 'UTF-8'); ?>">
            <?= Text::_('JNEXT'); ?> &raquo;
          </a>
        <?php else: ?>
          <span class="btn btn-sm btn-outline-secondary disabled"><?= Text::_('JNEXT'); ?> &raquo;</span>
        <?php endif; ?>
      </div>
    </div>
    <div class="card-body py-2">
      <div>
        <strong><?= Text::_('COM_BREEZINGFORMSNG_RECORDS_TITLE'); ?></strong>
        <a href="<?= htmlspecialchars($formEditorUrl, ENT_QUOTES, 'UTF-8'); ?>" title="<?= htmlspecialchars(Text::_('COM_BREEZINGFORMSNG_FORMS_OPEN_EDITOR'), ENT_QUOTES, 'UTF-8'); ?>">
          <?= $value($record->form_title); ?>
        </a>
        <span class="small text-muted"><?= $value($record->form_name); ?></span>
      </div>
      <div class="small text-muted mt-1">
        <strong><?= Text::_('COM_BREEZINGFORMSNG_RECORDS_SUBMITTED'); ?></strong> <?= $submitted; ?>
      </div>
    </div>
  </div>

  <div class="card mb-3">
    <div class="card-header"><?= Text::_('COM_BREEZINGFORMSNG_RECORD_VALUES'); ?></div>
    <div class="card-body">
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
            <tr><th><?= $systemLabel('COM_BREEZINGFORMSNG_RECORDS_SUBMITTED', 'COM_BREEZINGFORMSNG_RECORDS_SUBMITTED_DESC'); ?></th><td><?= $submitted; ?></td></tr>
            <tr><th><?= $systemLabel('COM_BREEZINGFORMSNG_IP', 'COM_BREEZINGFORMSNG_IP_DESC'); ?></th><td><?= $value($record->ip); ?></td></tr>
            <tr><th><?= $systemLabel('COM_BREEZINGFORMSNG_RECORDS_BROWSER', 'COM_BREEZINGFORMSNG_RECORDS_BROWSER_DESC'); ?></th><td><?= $value($record->browser); ?></td></tr>
            <tr><th><?= $systemLabel('COM_BREEZINGFORMSNG_RECORDS_OPSYS', 'COM_BREEZINGFORMSNG_RECORDS_OPSYS_DESC'); ?></th><td><?= $value($record->opsys); ?></td></tr>
            <tr><th><?= $systemLabel('COM_BREEZINGFORMSNG_RECORDS_PROVIDER', 'COM_BREEZINGFORMSNG_RECORDS_PROVIDER_DESC'); ?></th><td><?= $value($record->provider); ?></td></tr>
            <tr><th><?= $systemLabel('COM_BREEZINGFORMSNG_USERID', 'COM_BREEZINGFORMSNG_USERID_DESC'); ?></th><td><?= (int) $record->user_id; ?></td></tr>
            <tr><th><?= $systemLabel('COM_BREEZINGFORMSNG_PROCESS_SUBMITTERUSERNAME', 'COM_BREEZINGFORMSNG_PROCESS_SUBMITTERUSERNAME_DESC'); ?></th><td><?= $value($record->username); ?></td></tr>
            <tr><th><?= $systemLabel('COM_BREEZINGFORMSNG_PROCESS_SUBMITTERFULLNAME', 'COM_BREEZINGFORMSNG_PROCESS_SUBMITTERFULLNAME_DESC'); ?></th><td><?= $value($record->user_full_name); ?></td></tr>
            <tr class="table-primary"><th colspan="2"><?= Text::_('COM_BREEZINGFORMSNG_RECORDS_RECORDINFO'); ?></th></tr>
            <tr><th><?= $systemLabel('COM_BREEZINGFORMSNG_RECORDS_RECORDID', 'COM_BREEZINGFORMSNG_ID_DESC'); ?></th><td><?= (int) $record->id; ?></td></tr>
            <tr><th><?= $systemLabel('COM_BREEZINGFORMSNG_RECORDS_VIEWED', 'COM_BREEZINGFORMSNG_RECORDS_VIEWED_DESC'); ?></th><td><?= $yesNo($record->viewed); ?></td></tr>
            <tr><th><?= $systemLabel('COM_BREEZINGFORMSNG_RECORDS_EXPORTED', 'COM_BREEZINGFORMSNG_RECORDS_EXPORTED_DESC'); ?></th><td><?= $yesNo($record->exported); ?></td></tr>
            <tr><th><?= $systemLabel('COM_BREEZINGFORMSNG_RECORDS_ARCHIVED', 'COM_BREEZINGFORMSNG_RECORDS_ARCHIVED_DESC'); ?></th><td><?= $yesNo($record->archived); ?></td></tr>
            <tr><th><?= $systemLabel('COM_BREEZINGFORMSNG_RECORDS_OPTED', 'COM_BREEZINGFORMSNG_RECORDS_OPTED_DESC'); ?></th><td><?= $yesNo($record->opted); ?></td></tr>
            <tr class="table-primary"><th colspan="2"><?= Text::_('COM_BREEZINGFORMSNG_PAYMENT_INFORMATION'); ?></th></tr>
            <tr><th><?= $systemLabel('COM_BREEZINGFORMSNG_PAYMENT_TX_ID', 'COM_BREEZINGFORMSNG_PAYMENT_TX_ID_DESC'); ?></th><td><?= $value($record->paypal_tx_id); ?></td></tr>
            <tr><th><?= $systemLabel('COM_BREEZINGFORMSNG_PAYMENT_TX_DATE', 'COM_BREEZINGFORMSNG_PAYMENT_TX_DATE_DESC'); ?></th><td><?= $value($record->paypal_payment_date); ?></td></tr>
            <tr><th><?= $systemLabel('COM_BREEZINGFORMSNG_PAYMENT_TESTACCOUNT', 'COM_BREEZINGFORMSNG_PAYMENT_TESTACCOUNT_DESC'); ?></th><td><?= $yesNo($record->paypal_testaccount); ?></td></tr>
            <tr><th><?= $systemLabel('COM_BREEZINGFORMSNG_PAYMENT_DOWNLOAD_TRIES', 'COM_BREEZINGFORMSNG_PAYMENT_DOWNLOAD_TRIES_DESC'); ?></th><td><?= (int) $record->paypal_download_tries; ?></td></tr>
            <tr class="table-primary"><th colspan="2"><?= Text::_('COM_BREEZINGFORMSNG_RECORDS_FORMINFO'); ?></th></tr>
            <tr><th><?= $systemLabel('COM_BREEZINGFORMSNG_ID', 'COM_BREEZINGFORMSNG_RECORDS_FORM_ID_DESC'); ?></th><td><?= (int) $record->form; ?></td></tr>
            <tr>
              <th><?= $systemLabel('COM_BREEZINGFORMSNG_RECORDS_TITLE', 'COM_BREEZINGFORMSNG_RECORDS_TITLE_DESC'); ?></th>
              <td>
                <a href="<?= htmlspecialchars($formEditorUrl, ENT_QUOTES, 'UTF-8'); ?>" title="<?= htmlspecialchars(Text::_('COM_BREEZINGFORMSNG_FORMS_OPEN_EDITOR'), ENT_QUOTES, 'UTF-8'); ?>">
                  <?= $value($record->form_title); ?>
                </a>
              </td>
            </tr>
            <tr><th><?= $systemLabel('COM_BREEZINGFORMSNG_RECORDS_NAME', 'COM_BREEZINGFORMSNG_RECORDS_NAME_DESC'); ?></th><td><?= $value($record->form_name); ?></td></tr>
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
