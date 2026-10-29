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

/** @var \Vcmb\Component\BreezingformsNG\Administrator\View\Pieces\HtmlView $this */

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Vcmb\Component\BreezingformsNG\Administrator\Model\PieceModel;

$listOrder = $this->escape((string) $this->state->get('list.ordering'));
$listDirn  = $this->escape((string) $this->state->get('list.direction'));
$user = Factory::getApplication()->getIdentity() ?? throw new \RuntimeException('The current user is not loaded.');
$userId = (int) $user->id;
$canManageCheckin = $user->authorise('core.manage', 'com_checkin');
?>
<form action="<?= Route::_('index.php?option=com_breezingformsng&view=pieces'); ?>" method="post" name="adminForm" id="adminForm">
  <div id="j-main-container" class="j-main-container">
    <?= LayoutHelper::render('joomla.searchtools.default', ['view' => $this, 'options' => ['selectorFieldName' => 'package']]); ?>

    <?php if ($this->items === []): ?>
      <div class="alert alert-info">
        <span class="icon-info-circle" aria-hidden="true"></span><span class="visually-hidden"><?= Text::_('INFO'); ?></span>
        <?= Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
      </div>
    <?php else: ?>
      <table class="table" id="pieceList">
        <caption class="visually-hidden"><?= Text::_('COM_BREEZINGFORMSNG_MANAGEPIECES'); ?></caption>
        <thead>
          <tr>
            <td class="w-1 text-center"><?= HTMLHelper::_('grid.checkall'); ?></td>
            <th scope="col" class="w-1 text-center"><?= HTMLHelper::_('searchtools.sort', 'JSTATUS', 'published', $listDirn, $listOrder); ?></th>
            <th scope="col"><?= HTMLHelper::_('searchtools.sort', 'COM_BREEZINGFORMSNG_PIECES_TITLE', 'title', $listDirn, $listOrder); ?></th>
            <th scope="col"><?= HTMLHelper::_('searchtools.sort', 'COM_BREEZINGFORMSNG_PIECES_NAME', 'name', $listDirn, $listOrder); ?></th>
            <th scope="col"><?= HTMLHelper::_('searchtools.sort', 'COM_BREEZINGFORMSNG_PIECES_PACKAGE', 'package', $listDirn, $listOrder); ?></th>
            <th scope="col"><?= HTMLHelper::_('searchtools.sort', 'COM_BREEZINGFORMSNG_PIECES_TYPE', 'type', $listDirn, $listOrder); ?></th>
            <th scope="col"><?= HTMLHelper::_('searchtools.sort', 'COM_BREEZINGFORMSNG_PIECES_DESCRIPTION', 'description', $listDirn, $listOrder); ?></th>
            <th scope="col" class="text-nowrap"><?= HTMLHelper::_('searchtools.sort', 'JGLOBAL_MODIFIED', 'modified', $listDirn, $listOrder); ?></th>
            <th scope="col" class="w-1"><?= HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'id', $listDirn, $listOrder); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($this->items as $i => $row): ?>
            <?php $editUrl = Route::_('index.php?option=com_breezingformsng&task=piece.edit&id=' . (int) $row->id); ?>
            <tr class="row<?= $i % 2; ?>">
              <td class="text-center"><?= HTMLHelper::_('grid.id', $i, (int) $row->id, false, 'cid', 'cb', (string) $row->title); ?></td>
              <td class="text-center">
                <?php if ($this->canEdit): ?>
                  <a href="#"
                     class="tbody-icon js-bf-form-state<?= $row->published ? ' active' : ''; ?>"
                     data-item-task="pieces.setPublished"
                     data-item-form-id="<?= (int) $row->id; ?>"
                     data-state-type="published"
                     title="<?= Text::_($row->published ? 'JPUBLISHED' : 'JUNPUBLISHED'); ?>">
                    <span class="<?= $row->published ? 'icon-publish' : 'icon-unpublish'; ?>" aria-hidden="true"></span>
                  </a>
                <?php else: ?>
                  <span class="tbody-icon<?= $row->published ? ' active' : ''; ?>" title="<?= Text::_($row->published ? 'JPUBLISHED' : 'JUNPUBLISHED'); ?>">
                    <span class="<?= $row->published ? 'icon-publish' : 'icon-unpublish'; ?>" aria-hidden="true"></span>
                  </span>
                <?php endif; ?>
              </td>
              <th scope="row">
                <?php if ((int) $row->checked_out > 0): ?>
                  <?= HTMLHelper::_('jgrid.checkedout', $i, (string) $row->editor, $row->checked_out_time, 'pieces.', $canManageCheckin || (int) $row->checked_out === $userId); ?>
                <?php endif; ?>
                <?php if ($this->canOpen): ?>
                  <a href="<?= $editUrl; ?>" title="<?= Text::_('JACTION_EDIT'); ?>"><?= $this->escape((string) $row->title); ?></a>
                <?php else: ?>
                  <?= $this->escape((string) $row->title); ?>
                <?php endif; ?>
              </th>
              <?php if ($this->canOpen): ?>
                <td><a href="<?= $editUrl; ?>"><?= $this->escape((string) $row->name); ?></a></td>
              <?php else: ?>
                <td><?= $this->escape((string) $row->name); ?></td>
              <?php endif; ?>
              <td><?= $this->escape((string) $row->package); ?></td>
              <td><?= isset(PieceModel::TYPES[$row->type]) ? Text::_(PieceModel::TYPES[$row->type]) : '???'; ?></td>
              <td><?= $this->escape(mb_strimwidth((string) $row->description, 0, 100, '...')); ?></td>
              <td class="text-nowrap">
                <?php $modified = $row->modified ?: $row->created; ?>
                <?= $modified ? HTMLHelper::_('date', $modified, 'Y-m-d H:i') : '-'; ?>
              </td>
              <td><?= (int) $row->id; ?></td>
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
