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
use Vcmb\Component\BreezingformsNG\Administrator\Service\RecordState;

$record = $this->item;
// Dates are shown in the time zone of the user, named in the tooltip of each
// date with its offset on that date (it changes with daylight saving).
$timezone = $this->timezone;
$localDate = static fn (string $utc): string => sprintf(
    '<span title="%s">%s</span>',
    htmlspecialchars(
        Text::sprintf(
            'COM_BREEZINGFORMSNG_TIMEZONE_WITH_OFFSET',
            $timezone->getName(),
            HTMLHelper::_('date', $utc, 'P')
        ),
        ENT_QUOTES,
        'UTF-8'
    ),
    HTMLHelper::_('date', $utc, Text::_('DATE_FORMAT_LC6')),
);
$optionalDate = static fn (?string $utc): string => $utc !== null && $utc !== '' ? $localDate($utc) : '';
$position = $this->recordPosition !== null
    ? Text::sprintf('COM_BREEZINGFORMSNG_RECORD_POSITION', $this->recordPosition, $this->recordTotal)
    : '';
$value = static fn(mixed $field): string => htmlspecialchars((string) ($field ?? ''), ENT_QUOTES, 'UTF-8');
$yesNo = static fn(mixed $field): string => Text::_((bool) $field ? 'JYES' : 'JNO');
// Viewed/exported flags and the active/archived state switch by Ajax for the
// users allowed to change states (record-flags.js), and are only shown otherwise.
$recordId = (int) $record->id;
$stateIcons = [
    RecordState::PUBLISHED => 'icon-publish text-success',
    RecordState::ARCHIVED  => 'icon-archive',
    RecordState::TRASHED   => 'icon-trash text-danger',
];
$toggle = function (string $attributes, string $title, string $content): string {
    return $this->canEditState
        ? sprintf('<a href="#" class="text-decoration-none" %s title="%s">%s</a>', $attributes, $title, $content)
        : '<span>' . $content . '</span>';
};
// $text is HTML.
$flagToggle = static function (string $column, mixed $on, string $title, string $text) use ($toggle, $recordId): string {
    return $toggle(
        sprintf('data-bf-record-id="%d" data-bf-flag="%s"', $recordId, $column),
        htmlspecialchars(Text::_($title), ENT_QUOTES, 'UTF-8'),
        sprintf(
            '<span class="%s" aria-hidden="true"></span> %s',
            (bool) $on ? 'icon-check text-success' : 'icon-times text-danger',
            $text
        )
    );
};
$state = (int) $record->state;
$stateText = sprintf(
    '<span class="%s" aria-hidden="true"></span> <span class="bf-state-text">%s</span>',
    $stateIcons[$state] ?? $stateIcons[RecordState::PUBLISHED],
    htmlspecialchars(Text::_(RecordState::label($state)), ENT_QUOTES, 'UTF-8')
);
$systemLabel = static fn(string $label, string $description): string => sprintf(
    '<span title="%s">%s</span>',
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
// Joomla pagination over the records list, one record per page. Its admin
// layout submits adminForm (here, the record being edited), so its pages are
// rendered as links, with the markup of joomla.pagination.link. The links go
// through record.navigate, which checks the record being left in.
$pages = $this->pagination?->getPaginationPages() ?? [];
if ($pages !== []) {
    $first = max(1, min($this->pagination->pagesCurrent - 1, $this->pagination->pagesTotal - 2));
    $pages['pages'] = array_filter(
        $pages['pages'],
        static fn (int $number): bool => $number >= $first && $number < $first + 3,
        ARRAY_FILTER_USE_KEY
    );
}
$pageLink = static function (array $page, string $icon = '', string $aria = ''): string {
    $item = $page['data'];
    $display = $icon !== '' ? '<span class="' . $icon . '" aria-hidden="true"></span>' : $item->text;
    $aria = $aria !== '' ? Text::_($aria) : Text::sprintf('JLIB_HTML_GOTO_PAGE', strtolower($item->text));

    if ($page['active']) {
        return sprintf('<li class="page-item"><a aria-label="%s" href="%s" class="page-link">%s</a></li>', $aria, $item->link, $display);
    }

    if ($item->active) {
        return sprintf(
            '<li class="active page-item"><a aria-current="true" aria-label="%s" href="#" class="page-link">%s</a></li>',
            Text::sprintf('JLIB_HTML_PAGE_CURRENT', strtolower($item->text)),
            $display
        );
    }

    return '<li class="disabled page-item"><span class="page-link" aria-hidden="true">' . $display . '</span></li>';
};
$paginationNav = static function (string $spacing) use ($pages, $pageLink, $position): void {
    if ($pages === []) {
        return;
    }
    ?>
  <nav class="pagination__wrapper d-flex flex-wrap align-items-center justify-content-end gap-3 <?= $spacing; ?>" aria-label="<?= Text::_('JLIB_HTML_PAGINATION'); ?>">
    <div><?= $position; ?></div>
    <div class="pagination pagination-toolbar m-0">
      <ul class="pagination m-0">
        <?= $pageLink($pages['start'], 'icon-angle-double-left', 'JLIB_HTML_GOTO_POSITION_START'); ?>
        <?= $pageLink($pages['previous'], 'icon-angle-left', 'JLIB_HTML_GOTO_POSITION_PREVIOUS'); ?>
        <?php foreach ($pages['pages'] as $page): ?>
          <?= $pageLink($page); ?>
        <?php endforeach; ?>
        <?= $pageLink($pages['next'], 'icon-angle-right', 'JLIB_HTML_GOTO_POSITION_NEXT'); ?>
        <?= $pageLink($pages['end'], 'icon-angle-double-right', 'JLIB_HTML_GOTO_POSITION_END'); ?>
      </ul>
    </div>
  </nav>
    <?php
};
?>
<form action="index.php?option=com_breezingformsng&amp;view=record&amp;layout=edit&amp;id=<?= (int) $record->id; ?>" method="post" name="adminForm" id="adminForm">

  <div class="card mb-3">
    <div class="card-header d-flex align-items-center">
      <span><?= Text::_('COM_BREEZINGFORMSNG_RECORD_META'); ?></span>
      <span class="badge bg-light text-dark border ms-2">
        <?= Text::_('COM_BREEZINGFORMSNG_ID'); ?> : <?= (int) $record->id; ?>
      </span>
      <?php $paginationNav('ms-auto'); ?>
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
          <?= $flagToggle('viewed', $record->viewed, 'COM_BREEZINGFORMSNG_TOGGLE_VIEWED', Text::_('COM_BREEZINGFORMSNG_RECORDS_VIEWED')); ?>
          <?= $flagToggle('exported', $record->exported, 'COM_BREEZINGFORMSNG_TOGGLE_EXPORTED', Text::_('COM_BREEZINGFORMSNG_RECORDS_EXPORTED')); ?>
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
            <tr><th><?= $systemLabel('COM_BREEZINGFORMSNG_RECORDS_VIEWED', 'COM_BREEZINGFORMSNG_RECORDS_VIEWED_DESC'); ?></th><td><?= $flagToggle('viewed', $record->viewed, 'COM_BREEZINGFORMSNG_TOGGLE_VIEWED', '<span class="bf-flag-text">' . $yesNo($record->viewed) . '</span>'); ?></td></tr>
            <tr><th><?= $systemLabel('COM_BREEZINGFORMSNG_RECORDS_EXPORTED', 'COM_BREEZINGFORMSNG_RECORDS_EXPORTED_DESC'); ?></th><td><?= $flagToggle('exported', $record->exported, 'COM_BREEZINGFORMSNG_TOGGLE_EXPORTED', '<span class="bf-flag-text">' . $yesNo($record->exported) . '</span>'); ?></td></tr>
            <tr><th><?= $systemLabel('JSTATUS', 'COM_BREEZINGFORMSNG_RECORDS_STATE_DESC'); ?></th><td><?= $state === RecordState::TRASHED ? $stateText : $toggle(sprintf('data-bf-record-id="%d" data-bf-state="%d"', $recordId, $state), htmlspecialchars(Text::_('COM_BREEZINGFORMSNG_TOGGLE_STATE'), ENT_QUOTES, 'UTF-8'), $stateText); ?></td></tr>
            <tr><th><?= $systemLabel('COM_BREEZINGFORMSNG_RECORDS_OPTED', 'COM_BREEZINGFORMSNG_RECORDS_OPTED_DESC'); ?></th><td><?= $yesNo($record->opted); ?></td></tr>
            <?= $sectionHeader('record-section-payment', 'COM_BREEZINGFORMSNG_PAYMENT_INFORMATION'); ?>
            <tr><th><?= $systemLabel('COM_BREEZINGFORMSNG_PAYMENT_TX_ID', 'COM_BREEZINGFORMSNG_PAYMENT_TX_ID_DESC'); ?></th><td><?= $value($record->paypal_tx_id); ?></td></tr>
            <tr><th><?= $systemLabel('COM_BREEZINGFORMSNG_PAYMENT_TX_DATE', 'COM_BREEZINGFORMSNG_PAYMENT_TX_DATE_DESC'); ?></th><td><?= str_starts_with((string) $record->paypal_payment_date, '1970-01-01') ? '' : $value($record->paypal_payment_date); ?></td></tr>
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
            <tr><th><?= $systemLabel('COM_BREEZINGFORMSNG_RECORDS_CHECKED_OUT', 'COM_BREEZINGFORMSNG_RECORDS_CHECKED_OUT_DESC'); ?></th><td><?= $value($record->checked_out_name); ?></td></tr>
            <tr><th><?= $systemLabel('COM_BREEZINGFORMSNG_RECORDS_CHECKED_OUT_TIME', 'COM_BREEZINGFORMSNG_RECORDS_CHECKED_OUT_TIME_DESC'); ?></th><td><?= $optionalDate($record->checked_out_time); ?></td></tr>
            <?= $sectionHeader('record-section-auditinfo', 'COM_BREEZINGFORMSNG_RECORDS_AUDITINFO'); ?>
            <tr><th><?= $systemLabel('COM_BREEZINGFORMSNG_FORMS_MODIFIED', 'COM_BREEZINGFORMSNG_MODIFIED_DESC'); ?></th><td><?= $optionalDate($record->modified); ?></td></tr>
            <tr><th><?= $systemLabel('COM_BREEZINGFORMSNG_RECORDS_MODIFIED_BY', 'COM_BREEZINGFORMSNG_RECORDS_MODIFIED_BY_DESC'); ?></th><td><?= $value($record->modified_by_name); ?></td></tr>
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

<?php $paginationNav('mt-3'); ?>

<?php
// Web assets for this view are registered in Record\HtmlView::addToolbar() —
// useScript() calls placed in the template body do not take effect here.
?>
