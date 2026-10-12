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
?>
<div class="card mt-3">
  <div class="card-header">
    <strong><?= Text::_('COM_BREEZINGFORMSNG_IMPORT_CSV_TITLE'); ?></strong>
  </div>
  <div class="card-body">
    <p class="text-muted"><?= Text::_('COM_BREEZINGFORMSNG_IMPORT_CSV_DESC'); ?></p>

    <form action="index.php?option=com_breezingformsng" method="post" enctype="multipart/form-data" name="adminForm" id="adminForm">
      <div class="mb-3">
        <label class="form-label" for="csv_file"><?= Text::_('COM_BREEZINGFORMSNG_CSV_FILE'); ?></label>
        <input type="file" id="csv_file" name="csv_file" class="form-control" accept=".csv,text/csv">
      </div>
      <div class="mb-3">
        <label class="form-label" for="encoding"><?= Text::_('COM_BREEZINGFORMSNG_CSV_ENCODING'); ?></label>
        <select id="encoding" name="encoding" class="form-select">
          <option value="0"><?= Text::_('COM_BREEZINGFORMSNG_CSV_ENCODING_UTF8'); ?></option>
          <option value="ISO-8859-1">ISO-8859-1</option>
          <option value="Windows-1252">Windows-1252</option>
          <option value="UTF-16">UTF-16</option>
        </select>
      </div>
      <button type="submit" class="btn btn-primary"><?= Text::_('COM_BREEZINGFORMSNG_IMPORT'); ?></button>
      <a href="index.php?option=com_breezingformsng&amp;view=records" class="btn btn-secondary ms-2"><?= Text::_('JCANCEL'); ?></a>

      <input type="hidden" name="task" value="records.setCsvImport">
      <input type="hidden" name="form_id" value="<?= $this->formSelection; ?>">
      <?= HTMLHelper::_('form.token'); ?>
    </form>
  </div>
</div>
