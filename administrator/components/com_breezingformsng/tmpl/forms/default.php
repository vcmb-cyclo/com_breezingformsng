<?php
/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

\defined('_JEXEC') or die;

/** @var \Vcmb\Component\BreezingformsNG\Administrator\View\Forms\HtmlView $this */
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;

$pkg = $this->pkg;
$listOrder = $this->escape((string) $this->state->get('list.ordering'));
$listDirn  = $this->escape((string) $this->state->get('list.direction'));
$pagination = $this->pagination;
$itemCount  = count($this->items);
$saveOrder  = $listOrder === 'ordering' && strtolower($listDirn) === 'asc';
$user = Factory::getApplication()->getIdentity() ?? throw new \RuntimeException('The current user is not loaded.');
$userId = (int) $user->id;
$canManageCheckin = $user->authorise('core.manage', 'com_checkin');
?>
<form action="index.php?option=com_breezingformsng&amp;view=forms" method="post" name="adminForm" id="adminForm">
  <div id="j-main-container" class="j-main-container">
  <?= LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>

  <table class="table table-striped table-hover">
    <thead>
      <tr>
        <th class="w-1 text-center">
          <?= HTMLHelper::_('grid.checkall'); ?>
        </th>
        <th class="text-center"><?= HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'id', $listDirn, $listOrder); ?></th>
        <th><?= HTMLHelper::_('searchtools.sort', 'COM_BREEZINGFORMSNG_FORMS_TITLE', 'title', $listDirn, $listOrder); ?></th>
        <th><?= HTMLHelper::_('searchtools.sort', 'COM_BREEZINGFORMSNG_FORMS_NAME', 'name', $listDirn, $listOrder); ?></th>
        <th class="text-center"><?= HTMLHelper::_('searchtools.sort', 'COM_BREEZINGFORMSNG_FORMS_PAGES', 'pages', $listDirn, $listOrder); ?></th>
        <th class="text-center w-10"><?= HTMLHelper::_('searchtools.sort', 'JPUBLISHED', 'published', $listDirn, $listOrder); ?></th>
        <th class="text-center w-10"><?= HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ORDERING', 'ordering', $listDirn, $listOrder); ?></th>
        <th class="text-nowrap"><?= HTMLHelper::_('searchtools.sort', 'COM_BREEZINGFORMSNG_FORMS_MODIFIED', 'modified', $listDirn, $listOrder); ?></th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($this->items)): ?>
        <tr><td colspan="8" class="text-center"><?= Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?></td></tr>
      <?php else: ?>
        <?php foreach ($this->items as $i => $form): ?>
          <tr>
            <td class="text-center"><?= HTMLHelper::_('grid.id', $i, $form->id); ?></td>
            <td class="text-center"><?= (int) $form->id; ?></td>
            <td>
              <?php if ((int) $form->checked_out > 0): ?>
                <?= HTMLHelper::_('jgrid.checkedout', $i, (string) $form->editor, $form->checked_out_time, 'forms.', $canManageCheckin || (int) $form->checked_out === $userId); ?>
              <?php endif; ?>
              <a href="index.php?option=com_breezingformsng&task=quickmode.display&form=<?= (int) $form->id; ?>&pkg=<?= rawurlencode($pkg); ?>"
                 title="<?= Text::_('COM_BREEZINGFORMSNG_FORMS_OPEN_EDITOR'); ?>">
                <?= htmlspecialchars($form->title); ?>
              </a>
            </td>
            <td><?= htmlspecialchars($form->name); ?></td>
            <td class="text-center"><?= (int) $form->pages; ?></td>
            <td class="text-center">
              <a href="#"
                 class="tbody-icon<?= $form->published ? ' active' : ''; ?>"
                 data-item-id="cb<?= (int) $i; ?>"
                 data-item-task="forms.setPublished"
                 data-item-form-id="<?= (int) $form->id; ?>"
                 onclick="bfTogglePublished(<?= (int) $form->id; ?>, 'forms', this); return false;"
                 title="<?= $form->published ? Text::_('JPUBLISHED') : Text::_('JUNPUBLISHED'); ?>">
                <span class="<?= $form->published ? 'icon-publish' : 'icon-unpublish'; ?>" aria-hidden="true"></span>
              </a>
            </td>
            <td class="order text-center text-nowrap">
              <?= $pagination->orderUpIcon(
                  $i,
                  $saveOrder,
                  'forms.orderup',
                  Text::_('JLIB_HTML_MOVE_UP'),
                  $saveOrder
              ); ?>
              <?= $pagination->orderDownIcon(
                  $i,
                  $itemCount,
                  $saveOrder,
                  'forms.orderdown',
                  Text::_('JLIB_HTML_MOVE_DOWN'),
                  $saveOrder
              ); ?>
            </td>
            <td class="text-nowrap">
              <?php
              $modified = (string) ($form->modified ?? '');
              echo $modified !== '' && $modified !== '0000-00-00 00:00:00'
                  ? HTMLHelper::_('date', $modified, Text::_('DATE_FORMAT_LC5'))
                  : '-';
              ?>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>

  <?= $pagination->getListFooter(); ?>

  <input type="hidden" name="task" value="">
  <input type="hidden" name="boxchecked" value="0">
  <?= HTMLHelper::_('form.token'); ?>
  </div>
</form>

<?php
// Web assets for this view are registered in Forms\HtmlView::display() —
// useScript() calls placed in the template body do not take effect here.
?>
