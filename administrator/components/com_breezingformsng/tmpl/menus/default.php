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

/** @var \Vcmb\Component\BreezingformsNG\Administrator\View\Menus\HtmlView $this */

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
?>
<form action="<?= Route::_('index.php?option=com_breezingformsng&view=menus'); ?>" method="post" name="adminForm" id="adminForm">
  <div id="j-main-container" class="j-main-container">
    <?= LayoutHelper::render('joomla.searchtools.default', ['view' => $this, 'options' => ['selectorFieldName' => 'package']]); ?>

    <?php if ($this->items === []): ?>
      <div class="alert alert-info">
        <span class="icon-info-circle" aria-hidden="true"></span><span class="visually-hidden"><?= Text::_('INFO'); ?></span>
        <?= Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
      </div>
    <?php else: ?>
      <table class="table" id="menuList">
        <caption class="visually-hidden"><?= Text::_('COM_BREEZINGFORMSNG_MANAGEMENUS'); ?></caption>
        <thead>
          <tr>
            <td class="w-1 text-center"><?= HTMLHelper::_('grid.checkall'); ?></td>
            <th scope="col" class="w-1 text-center"><?= Text::_('JSTATUS'); ?></th>
            <th scope="col"><?= Text::_('COM_BREEZINGFORMSNG_MENUS_TITLE'); ?></th>
            <th scope="col"><?= Text::_('COM_BREEZINGFORMSNG_MENUS_NAME'); ?></th>
            <th scope="col"><?= Text::_('COM_BREEZINGFORMSNG_MENUS_PARENT'); ?></th>
            <th scope="col"><?= Text::_('COM_BREEZINGFORMSNG_MENUS_PACKAGE'); ?></th>
            <th scope="col" class="w-10 text-center"><?= Text::_('JORDER'); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($this->items as $i => $row): ?>
            <tr class="row<?= $i % 2; ?>">
              <td class="text-center"><?= HTMLHelper::_('grid.id', $i, (int) $row->id, false, 'cid', 'cb', (string) $row->title); ?></td>
              <td class="text-center"><?= HTMLHelper::_('jgrid.published', (int) $row->published, $i, 'menus.', $this->canEditState); ?></td>
              <th scope="row">
                <?= (int) $row->parent > 0 ? '&mdash; ' : ''; ?>
                <a href="<?= Route::_('index.php?option=com_breezingformsng&task=menu.edit&id=' . (int) $row->id); ?>" title="<?= Text::_('JACTION_EDIT'); ?>">
                  <?= $this->escape((string) $row->title); ?>
                </a>
              </th>
              <td><?= $this->escape((string) $row->name); ?></td>
              <td><?= (int) $row->parent > 0 ? $this->escape((string) $row->parent_title) : '—'; ?></td>
              <td><?= $this->escape((string) $row->package); ?></td>
              <td class="text-center">
                <?php if ($this->canEditState): ?>
                  <button type="button" class="btn btn-sm btn-link p-0"
                    onclick="return Joomla.listItemTask('cb<?= $i; ?>', 'menus.orderup');"
                    title="<?= Text::_('JLIB_HTML_MOVE_UP'); ?>">
                    <span class="icon-arrow-up-2" aria-hidden="true"></span>
                  </button>
                  <button type="button" class="btn btn-sm btn-link p-0"
                    onclick="return Joomla.listItemTask('cb<?= $i; ?>', 'menus.orderdown');"
                    title="<?= Text::_('JLIB_HTML_MOVE_DOWN'); ?>">
                    <span class="icon-arrow-down-2" aria-hidden="true"></span>
                  </button>
                <?php endif; ?>
              </td>
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
