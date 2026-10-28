<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

\defined('_JEXEC') or die;

/** @var \Vcmb\Component\BreezingformsNG\Administrator\View\Record\HtmlView $this */

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\Session\Session;
use Vcmb\Component\BreezingformsNG\Administrator\Service\RecordState;

$record = $this->item;
// Dates are stored in UTC and shown in the time zone of the user, named next
// to each date with its offset on that date (it changes with daylight saving).
$timezone = Factory::getApplication()->getIdentity()->getTimezone();
$localDate = static fn (string $utc): string => sprintf(
    '%s <span class="small text-muted">%s</span>',
    HTMLHelper::_('date', $utc, Text::_('DATE_FORMAT_LC6')),
    htmlspecialchars(
        Text::sprintf(
            'COM_BREEZINGFORMSNG_TIMEZONE_WITH_OFFSET',
            $timezone->getName(),
            HTMLHelper::_('date', $utc, 'P')
        ),
        ENT_QUOTES,
        'UTF-8'
    ),
);
$optionalDate = static fn (?string $utc): string => $utc !== null && $utc !== '' ? $localDate($utc) : '';
$position = $this->recordPosition !== null
    ? Text::sprintf('COM_BREEZINGFORMSNG_RECORD_POSITION', $this->recordPosition, $this->recordTotal)
    : '';
$value = static fn(mixed $field): string => htmlspecialchars((string) ($field ?? ''), ENT_QUOTES, 'UTF-8');
$yesNo = static fn(mixed $field): string => Text::_((bool) $field ? 'JYES' : 'JNO');
$systemLabel = static fn(string $label, string $description): string => sprintf(
    '<span class="hasTooltip" title="%s">%s</span>',
    htmlspecialchars(Text::_($description), ENT_QUOTES, 'UTF-8'),
    htmlspecialchars(Text::_($label), ENT_QUOTES, 'UTF-8'),
);
$sectionOpened = false;
$sectionHeader = static function (string $id, string $label) use (&$sectionOpened): string {
    $close = $sectionOpened ? '</tbody>' : '';
    $sectionOpened = true;

    return $close . sprintf(
        '<tbody><tr class="table-primary"><th colspan="2">'
            . '<a href="#" class="bf-collapse-toggle d-block text-decoration-none text-reset"'
            . ' data-bs-toggle="collapse" data-bs-target="#%1$s" aria-expanded="true" aria-controls="%1$s">'
            . '<span class="icon-chevron-down bf-collapse-icon" aria-hidden="true"></span> %2$s'
            . '</a></th></tr></tbody><tbody id="%1$s" class="collapse show">',
        $id,
        htmlspecialchars(Text::_($label), ENT_QUOTES, 'UTF-8'),
    );
};
$formEditorUrl = 'index.php?option=com_breezingformsng&task=quickmode.display&form=' . (int) $record->form;
// Previous/next go through record.navigate so the record being left is checked in.
$recordUrl = static fn (int $recordId): string => 'index.php?option=com_breezingformsng&task=record.navigate&id='
    . (int) $record->id . '&target=' . $recordId . '&' . Session::getFormToken() . '=1';
?>
<form action="index.php?option=com_breezingformsng&amp;view=record&amp;layout=edit&amp;id=<?= (int) $record->id; ?>" method="post" name="adminForm" id="adminForm">

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

        <?php if ($position !== ''): ?>
          <span class="align-self-center small text-muted"><?= $position; ?></span>
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
      <div class="d-flex align-items-start">
        <div>
          <div>
            <strong><?= Text::_('COM_BREEZINGFORMSNG_RECORDS_TITLE'); ?></strong>
            <a href="<?= htmlspecialchars($formEditorUrl, ENT_QUOTES, 'UTF-8'); ?>" title="<?= htmlspecialchars(Text::_('COM_BREEZINGFORMSNG_FORMS_OPEN_EDITOR'), ENT_QUOTES, 'UTF-8'); ?>">
              <?= $value($record->form_title); ?>
            </a>
            <span class="small text-muted"><?= $value($record->form_name); ?></span>
          </div>
        </div>
        <div class="d-flex gap-3 ms-auto">
          <a href="#" class="text-decoration-none"
            data-bf-flag="bfrecord_viewed"
            onclick="bfToggleFlag(<?= (int) $record->id; ?>, 'bfrecord_viewed', this); return false;"
            title="<?= htmlspecialchars(Text::_('COM_BREEZINGFORMSNG_TOGGLE_VIEWED'), ENT_QUOTES, 'UTF-8'); ?>">
            <span class="<?= $record->viewed ? 'icon-check text-success' : 'icon-times text-danger'; ?>"></span>
            <?= Text::_('COM_BREEZINGFORMSNG_RECORDS_VIEWED'); ?>
          </a>
          <a href="#" class="text-decoration-none"
            data-bf-flag="bfrecord_exported"
            onclick="bfToggleFlag(<?= (int) $record->id; ?>, 'bfrecord_exported', this); return false;"
            title="<?= htmlspecialchars(Text::_('COM_BREEZINGFORMSNG_TOGGLE_EXPORTED'), ENT_QUOTES, 'UTF-8'); ?>">
            <span class="<?= $record->exported ? 'icon-check text-success' : 'icon-times text-danger'; ?>"></span>
            <?= Text::_('COM_BREEZINGFORMSNG_RECORDS_EXPORTED'); ?>
          </a>
        </div>
      </div>
    </div>
  </div>

  <div class="card mb-3">
    <table class="table mb-0">
      <tbody>
        <tr class="table-primary">
          <th>
            <a href="#" class="bf-collapse-toggle d-block text-decoration-none text-reset"
              data-bs-toggle="collapse" data-bs-target="#record-section-values" aria-expanded="true" aria-controls="record-section-values">
              <span class="icon-chevron-down bf-collapse-icon" aria-hidden="true"></span> <?= Text::_('COM_BREEZINGFORMSNG_RECORD_VALUES'); ?>
            </a>
          </th>
        </tr>
      </tbody>
    </table>
    <div id="record-section-values" class="collapse show bf-record-values">
      <div class="card-body">
        <?= $this->form->renderFieldset('values'); ?>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-striped mb-0">
            <?= $sectionHeader('record-section-subinfo', 'COM_BREEZINGFORMSNG_RECORDS_SUBMINFO'); ?>
            <tr><th><?= $systemLabel('COM_BREEZINGFORMSNG_RECORDS_SUBMITTED', 'COM_BREEZINGFORMSNG_RECORDS_SUBMITTED_DESC'); ?></th><td><?= $localDate((string) $record->submitted); ?></td></tr>
            <tr><th><?= $systemLabel('COM_BREEZINGFORMSNG_IP', 'COM_BREEZINGFORMSNG_IP_DESC'); ?></th><td><?= $value($record->ip); ?></td></tr>
            <tr><th><?= $systemLabel('COM_BREEZINGFORMSNG_RECORDS_BROWSER', 'COM_BREEZINGFORMSNG_RECORDS_BROWSER_DESC'); ?></th><td><?= $value($record->browser); ?></td></tr>
            <tr><th><?= $systemLabel('COM_BREEZINGFORMSNG_RECORDS_OPSYS', 'COM_BREEZINGFORMSNG_RECORDS_OPSYS_DESC'); ?></th><td><?= $value($record->opsys); ?></td></tr>
            <tr><th><?= $systemLabel('COM_BREEZINGFORMSNG_RECORDS_PROVIDER', 'COM_BREEZINGFORMSNG_RECORDS_PROVIDER_DESC'); ?></th><td><?= $value($record->provider); ?></td></tr>
            <tr><th><?= $systemLabel('COM_BREEZINGFORMSNG_USERID', 'COM_BREEZINGFORMSNG_USERID_DESC'); ?></th><td><?= (int) $record->user_id; ?></td></tr>
            <tr><th><?= $systemLabel('COM_BREEZINGFORMSNG_PROCESS_SUBMITTERUSERNAME', 'COM_BREEZINGFORMSNG_PROCESS_SUBMITTERUSERNAME_DESC'); ?></th><td><?= $value($record->username); ?></td></tr>
            <tr><th><?= $systemLabel('COM_BREEZINGFORMSNG_PROCESS_SUBMITTERFULLNAME', 'COM_BREEZINGFORMSNG_PROCESS_SUBMITTERFULLNAME_DESC'); ?></th><td><?= $value($record->user_full_name); ?></td></tr>
            <?= $sectionHeader('record-section-recordinfo', 'COM_BREEZINGFORMSNG_RECORDS_RECORDINFO'); ?>
            <tr><th><?= $systemLabel('COM_BREEZINGFORMSNG_RECORDS_VIEWED', 'COM_BREEZINGFORMSNG_RECORDS_VIEWED_DESC'); ?></th><td><a href="#" class="text-decoration-none" data-bf-flag="bfrecord_viewed" onclick="bfToggleFlag(<?= (int) $record->id; ?>, 'bfrecord_viewed', this); return false;" title="<?= htmlspecialchars(Text::_('COM_BREEZINGFORMSNG_TOGGLE_VIEWED'), ENT_QUOTES, 'UTF-8'); ?>"><span class="<?= $record->viewed ? 'icon-check text-success' : 'icon-times text-danger'; ?>"></span> <span class="bf-flag-text"><?= $yesNo($record->viewed); ?></span></a></td></tr>
            <tr><th><?= $systemLabel('COM_BREEZINGFORMSNG_RECORDS_EXPORTED', 'COM_BREEZINGFORMSNG_RECORDS_EXPORTED_DESC'); ?></th><td><a href="#" class="text-decoration-none" data-bf-flag="bfrecord_exported" onclick="bfToggleFlag(<?= (int) $record->id; ?>, 'bfrecord_exported', this); return false;" title="<?= htmlspecialchars(Text::_('COM_BREEZINGFORMSNG_TOGGLE_EXPORTED'), ENT_QUOTES, 'UTF-8'); ?>"><span class="<?= $record->exported ? 'icon-check text-success' : 'icon-times text-danger'; ?>"></span> <span class="bf-flag-text"><?= $yesNo($record->exported); ?></span></a></td></tr>
            <tr><th><?= $systemLabel('JSTATUS', 'COM_BREEZINGFORMSNG_RECORDS_STATE_DESC'); ?></th><td><?= Text::_(RecordState::label((int) $record->state)); ?></td></tr>
            <tr><th><?= $systemLabel('COM_BREEZINGFORMSNG_RECORDS_OPTED', 'COM_BREEZINGFORMSNG_RECORDS_OPTED_DESC'); ?></th><td><?= $yesNo($record->opted); ?></td></tr>
            <?= $sectionHeader('record-section-payment', 'COM_BREEZINGFORMSNG_PAYMENT_INFORMATION'); ?>
            <tr><th><?= $systemLabel('COM_BREEZINGFORMSNG_PAYMENT_TX_ID', 'COM_BREEZINGFORMSNG_PAYMENT_TX_ID_DESC'); ?></th><td><?= $value($record->paypal_tx_id); ?></td></tr>
            <tr><th><?= $systemLabel('COM_BREEZINGFORMSNG_PAYMENT_TX_DATE', 'COM_BREEZINGFORMSNG_PAYMENT_TX_DATE_DESC'); ?></th><td><?= $value($record->paypal_payment_date); ?></td></tr>
            <tr><th><?= $systemLabel('COM_BREEZINGFORMSNG_PAYMENT_TESTACCOUNT', 'COM_BREEZINGFORMSNG_PAYMENT_TESTACCOUNT_DESC'); ?></th><td><?= $yesNo($record->paypal_testaccount); ?></td></tr>
            <tr><th><?= $systemLabel('COM_BREEZINGFORMSNG_PAYMENT_DOWNLOAD_TRIES', 'COM_BREEZINGFORMSNG_PAYMENT_DOWNLOAD_TRIES_DESC'); ?></th><td><?= (int) $record->paypal_download_tries; ?></td></tr>
            <?= $sectionHeader('record-section-forminfo', 'COM_BREEZINGFORMSNG_RECORDS_FORMINFO'); ?>
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
            <tr><th><?= $systemLabel('COM_BREEZINGFORMSNG_RECORDS_CHECKED_OUT', 'COM_BREEZINGFORMSNG_RECORDS_CHECKED_OUT_DESC'); ?></th><td><?= $value($record->checked_out); ?></td></tr>
            <tr><th><?= $systemLabel('COM_BREEZINGFORMSNG_RECORDS_CHECKED_OUT_TIME', 'COM_BREEZINGFORMSNG_RECORDS_CHECKED_OUT_TIME_DESC'); ?></th><td><?= $optionalDate($record->checked_out_time); ?></td></tr>
            <?= $sectionHeader('record-section-auditinfo', 'COM_BREEZINGFORMSNG_RECORDS_AUDITINFO'); ?>
            <tr><th><?= $systemLabel('COM_BREEZINGFORMSNG_FORMS_MODIFIED', 'COM_BREEZINGFORMSNG_MODIFIED_DESC'); ?></th><td><?= $optionalDate($record->modified); ?></td></tr>
            <tr><th><?= $systemLabel('COM_BREEZINGFORMSNG_RECORDS_MODIFIED_BY', 'COM_BREEZINGFORMSNG_RECORDS_MODIFIED_BY_DESC'); ?></th><td><?= $value($record->modified_by); ?></td></tr>
            <tr><th><?= $systemLabel('COM_BREEZINGFORMSNG_RECORDS_MODIFIED_USER_ID', 'COM_BREEZINGFORMSNG_RECORDS_MODIFIED_USER_ID_DESC'); ?></th><td><?= (int) $record->modified_user_id; ?></td></tr>
            <tr><th><?= $systemLabel('COM_BREEZINGFORMSNG_RECORDS_OPT_IP', 'COM_BREEZINGFORMSNG_RECORDS_OPT_IP_DESC'); ?></th><td><?= $value($record->opt_ip); ?></td></tr>
            <tr><th><?= $systemLabel('COM_BREEZINGFORMSNG_RECORDS_OPT_DATE', 'COM_BREEZINGFORMSNG_RECORDS_OPT_DATE_DESC'); ?></th><td><?= $optionalDate($record->opt_date); ?></td></tr>
            <tr><th><?= $systemLabel('COM_BREEZINGFORMSNG_RECORDS_OPT_TOKEN', 'COM_BREEZINGFORMSNG_RECORDS_OPT_TOKEN_DESC'); ?></th><td><?= $value($record->opt_token); ?></td></tr>
            <tr><th><?= $systemLabel('COM_BREEZINGFORMSNG_RECORDS_RECORD_TITLE', 'COM_BREEZINGFORMSNG_RECORDS_RECORD_TITLE_DESC'); ?></th><td><?= $value($record->title); ?></td></tr>
            <tr><th><?= $systemLabel('COM_BREEZINGFORMSNG_RECORDS_RECORD_NAME', 'COM_BREEZINGFORMSNG_RECORDS_RECORD_NAME_DESC'); ?></th><td><?= $value($record->name); ?></td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <?= $this->form->getInput('id'); ?>
  <input type="hidden" name="task" value="">
  <input type="hidden" name="cid[]" value="<?= (int) $record->id; ?>">
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

  <?php if ($position !== ''): ?>
    <span class="align-self-center text-muted"><?= $position; ?></span>
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
// Web assets for this view are registered in Record\HtmlView::addToolbar() —
// useScript() calls placed in the template body do not take effect here.
?>
