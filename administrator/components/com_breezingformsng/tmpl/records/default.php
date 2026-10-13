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
use Joomla\CMS\Layout\LayoutHelper;

$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));

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
                <a href="index.php?option=com_breezingformsng&amp;task=record.edit&amp;id=<?= $recId; ?>" title="<?= $headerTitle('JACTION_EDIT'); ?>">
                  <?= $this->escape((string) $rec->form_title); ?>
                </a>
              </th>
              <td><?= $this->escape((string) $rec->ip); ?></td>
              <td><?= $this->escape((string) ($rec->user_full_name ?: $rec->username)); ?></td>
              <td class="text-center">
                <a href="#" onclick="bfToggleFlag(<?= $recId; ?>, 'bfrecord_viewed', this); return false;" title="<?= Text::_('COM_BREEZINGFORMSNG_TOGGLE_VIEWED'); ?>">
                  <span class="<?= $rec->viewed ? 'icon-check text-success' : 'icon-times text-danger'; ?>"></span>
                </a>
              </td>
              <td class="text-center">
                <a href="#" onclick="bfToggleFlag(<?= $recId; ?>, 'bfrecord_exported', this); return false;" title="<?= Text::_('COM_BREEZINGFORMSNG_TOGGLE_EXPORTED'); ?>">
                  <span class="<?= $rec->exported ? 'icon-check text-success' : 'icon-times text-danger'; ?>"></span>
                </a>
              </td>
              <td class="text-center"><?= HTMLHelper::_('jgrid.published', (int) $rec->state, $i, 'records.', false); ?></td>
              <td><?= $this->escape((string) $rec->submitted); ?></td>
              <td><?= $rec->modified ? $this->escape((string) $rec->modified) : '—'; ?></td>
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
