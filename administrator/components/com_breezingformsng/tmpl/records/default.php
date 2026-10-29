<?php
/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

\defined('_JEXEC') or die;

/** @var \Vcmb\Component\BreezingformsNG\Administrator\View\Records\HtmlView $this */
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Vcmb\Component\BreezingformsNG\Administrator\Service\RecordState;

$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
$user = Factory::getApplication()->getIdentity() ?? throw new \RuntimeException('The current user is not loaded.');
$userId = (int) $user->id;
$canManageCheckin = $user->authorise('core.manage', 'com_checkin');
// Dates are stored in UTC and shown in the time zone of the user.
$dateFormat = Text::_('DATE_FORMAT_LC6');
$dateTitle = $this->escape(Text::sprintf('COM_BREEZINGFORMSNG_RECORDS_DATES_TIMEZONE', $user->getTimezone()->getName()));

// State icons with the record wording (Active rather than Published). Like a
// Joomla published icon, a click archives an active record and makes an
// archived one active again; trashed records are only shown.
$canEditState = $user->authorise('core.edit.state', 'com_breezingformsng');
$stateIcons = [
    RecordState::PUBLISHED => ['archive', '', RecordState::label(RecordState::PUBLISHED), RecordState::label(RecordState::PUBLISHED), true, 'publish', 'publish'],
    RecordState::ARCHIVED  => ['publish', '', RecordState::label(RecordState::ARCHIVED), RecordState::label(RecordState::ARCHIVED), true, 'archive', 'archive'],
    RecordState::TRASHED   => ['', '', '', RecordState::label(RecordState::TRASHED), true, 'trash', 'trash text-danger'],
];

// jgrid.state reads its fallback state 0 even when the value is found.
$stateIcons[0] = $stateIcons[RecordState::PUBLISHED];

$headerTitle = static fn (string $key): string => htmlspecialchars(Text::_($key), ENT_QUOTES, 'UTF-8');
?>
<form action="index.php?option=com_breezingformsng&amp;view=records" method="post" name="adminForm" id="adminForm">
  <div id="j-main-container" class="j-main-container">
    <?= LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>

    <?php if ($this->items === []): ?>
      <div class="alert alert-info">
        <span class="icon-info-circle" aria-hidden="true"></span><span class="visually-hidden"><?= Text::_('INFO'); ?></span>
        <?= Text::_('COM_BREEZINGFORMSNG_NO_RECORDS_FOUND'); ?>
      </div>
    <?php else: ?>
      <table class="table" id="recordList">
        <caption class="visually-hidden">
          <?= Text::_('COM_BREEZINGFORMSNG_RECORDS'); ?>,
          <span id="orderedBy"><?= Text::_('JGLOBAL_SORTED_BY'); ?> </span>,
          <span id="filteredBy"><?= Text::_('JGLOBAL_FILTERED_BY'); ?></span>
        </caption>
        <thead>
          <tr>
            <td class="w-1 text-center"><?= HTMLHelper::_('grid.checkall'); ?></td>
            <th scope="col" title="<?= $headerTitle('COM_BREEZINGFORMSNG_ID_DESC'); ?>"><?= HTMLHelper::_('searchtools.sort', 'COM_BREEZINGFORMSNG_ID', 'records.id', $listDirn, $listOrder); ?></th>
            <th scope="col" title="<?= $headerTitle('COM_BREEZINGFORMSNG_RECORDS_FORM_DESC'); ?>"><?= HTMLHelper::_('searchtools.sort', 'COM_BREEZINGFORMSNG_FORM', 'forms.title', $listDirn, $listOrder); ?></th>
            <th scope="col" title="<?= $headerTitle('COM_BREEZINGFORMSNG_IP_DESC'); ?>"><?= HTMLHelper::_('searchtools.sort', 'COM_BREEZINGFORMSNG_IP', 'records.ip', $listDirn, $listOrder); ?></th>
            <th scope="col" title="<?= $headerTitle('COM_BREEZINGFORMSNG_USER_DESC'); ?>"><?= HTMLHelper::_('searchtools.sort', 'COM_BREEZINGFORMSNG_USER', 'records.username', $listDirn, $listOrder); ?></th>
            <th scope="col" class="text-center" title="<?= $headerTitle('COM_BREEZINGFORMSNG_VIEWED_DESC'); ?>"><?= HTMLHelper::_('searchtools.sort', 'COM_BREEZINGFORMSNG_VIEWED', 'records.viewed', $listDirn, $listOrder); ?></th>
            <th scope="col" class="text-center" title="<?= $headerTitle('COM_BREEZINGFORMSNG_EXPORTED_DESC'); ?>"><?= HTMLHelper::_('searchtools.sort', 'COM_BREEZINGFORMSNG_EXPORTED', 'records.exported', $listDirn, $listOrder); ?></th>
            <th scope="col" class="text-center" title="<?= $headerTitle('COM_BREEZINGFORMSNG_RECORDS_STATE_DESC'); ?>"><?= HTMLHelper::_('searchtools.sort', 'JSTATUS', 'records.state', $listDirn, $listOrder); ?></th>
            <th scope="col" title="<?= $headerTitle('COM_BREEZINGFORMSNG_SUBMITTED_DESC'); ?>"><?= HTMLHelper::_('searchtools.sort', 'COM_BREEZINGFORMSNG_SUBMITTED', 'records.submitted', $listDirn, $listOrder); ?></th>
            <th scope="col" title="<?= $headerTitle('COM_BREEZINGFORMSNG_MODIFIED_DESC'); ?>"><?= HTMLHelper::_('searchtools.sort', 'COM_BREEZINGFORMSNG_FORMS_MODIFIED', 'records.modified', $listDirn, $listOrder); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($this->items as $i => $rec): ?>
            <?php $recId = (int) $rec->id; ?>
            <tr class="row<?= $i % 2; ?>">
              <td class="text-center"><?= HTMLHelper::_('grid.id', $i, $recId, false, 'cid', 'cb', (string) $recId); ?></td>
              <td><?= $recId; ?></td>
              <th scope="row">
                <?php if ((int) $rec->checked_out > 0): ?>
                  <?= HTMLHelper::_('jgrid.checkedout', $i, (string) $rec->editor, $rec->checked_out_time, 'records.', $canManageCheckin || (int) $rec->checked_out === $userId); ?>
                <?php endif; ?>
                <?php if ($this->canOpen): ?>
                  <a href="index.php?option=com_breezingformsng&amp;task=record.edit&amp;id=<?= $recId; ?>" title="<?= $headerTitle('JACTION_EDIT'); ?>">
                    <?= $this->escape((string) $rec->form_title); ?>
                  </a>
                <?php else: ?>
                  <?= $this->escape((string) $rec->form_title); ?>
                <?php endif; ?>
              </th>
              <td><?= $this->escape((string) $rec->ip); ?></td>
              <td><?= $this->escape((string) ($rec->user_full_name ?: $rec->username)); ?></td>
              <td class="text-center">
                <?php if ($canEditState): ?>
                  <a href="#" data-bf-record-id="<?= $recId; ?>" data-bf-flag="viewed" title="<?= $headerTitle('COM_BREEZINGFORMSNG_TOGGLE_VIEWED'); ?>">
                    <span class="<?= $rec->viewed ? 'icon-check text-success' : 'icon-times text-danger'; ?>" aria-hidden="true"></span>
                  </a>
                <?php else: ?>
                  <span class="<?= $rec->viewed ? 'icon-check text-success' : 'icon-times text-danger'; ?>" aria-hidden="true"></span>
                <?php endif; ?>
              </td>
              <td class="text-center">
                <?php if ($canEditState): ?>
                  <a href="#" data-bf-record-id="<?= $recId; ?>" data-bf-flag="exported" title="<?= $headerTitle('COM_BREEZINGFORMSNG_TOGGLE_EXPORTED'); ?>">
                    <span class="<?= $rec->exported ? 'icon-check text-success' : 'icon-times text-danger'; ?>" aria-hidden="true"></span>
                  </a>
                <?php else: ?>
                  <span class="<?= $rec->exported ? 'icon-check text-success' : 'icon-times text-danger'; ?>" aria-hidden="true"></span>
                <?php endif; ?>
              </td>
              <td class="text-center"><?= HTMLHelper::_('jgrid.state', $stateIcons, (int) $rec->state, $i, 'records.', $canEditState && (int) $rec->state !== RecordState::TRASHED); ?></td>
              <td title="<?= $dateTitle; ?>"><?= HTMLHelper::_('date', (string) $rec->submitted, $dateFormat); ?></td>
              <td<?= $rec->modified ? ' title="' . $dateTitle . '"' : ''; ?>><?= $rec->modified ? HTMLHelper::_('date', (string) $rec->modified, $dateFormat) : '—'; ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <?= $this->pagination->getListFooter(); ?>
    <?php endif; ?>

    <input type="hidden" name="task" value="">
    <input type="hidden" name="boxchecked" value="0">
    <?= HTMLHelper::_('form.token'); ?>
  </div>
</form>

<?php
// Web assets for this view are registered in Records\HtmlView::display() —
// useScript() calls placed in the template body do not take effect here.
?>
