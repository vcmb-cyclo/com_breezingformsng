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
use Joomla\CMS\Session\Session;

$listOrder  = $this->listOrder;
$listDirn   = $this->listDirn;
$total      = $this->total;
$limitStart = $this->limitStart;
$limit      = $this->limit;
$formSelection = $this->formSelection;
$searchTerm = $this->searchTerm;

$totalPages  = ($limit > 0) ? (int) ceil($total / $limit) : 1;
$currentPage = ($limit > 0) ? (int) floor($limitStart / $limit) : 0;

$pageUrl = function (int $start) use ($listOrder, $listDirn, $formSelection, $searchTerm): string {
    return 'index.php?option=com_breezingformsng&view=records'
        . '&filter_order=' . rawurlencode($listOrder)
        . '&filter_order_Dir=' . $listDirn
        . '&form_selection=' . $formSelection
        . ($searchTerm !== '' ? '&searchterm=' . rawurlencode($searchTerm) : '')
        . '&limitstart=' . $start;
};

$editUrl = static function (int $recordId) use ($listOrder, $listDirn, $formSelection, $searchTerm, $limit, $limitStart): string {
    $query = [
        'option'            => 'com_breezingformsng',
        'view'              => 'records',
        'layout'            => 'edit',
        'record_id'         => $recordId,
        'form_selection'    => $formSelection,
        'filter_order'      => $listOrder,
        'filter_order_Dir'  => $listDirn,
        'limit'             => $limit,
        'limitstart'        => $limitStart,
    ];
    if ($searchTerm !== '') {
        $query['searchterm'] = $searchTerm;
    }

    return 'index.php?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
};

$headerTitle = static fn (string $key): string => htmlspecialchars(Text::_($key), ENT_QUOTES, 'UTF-8');
?>
<form action="index.php?option=com_breezingformsng&amp;view=records" method="post" name="adminForm" id="adminForm">

  <div class="row mb-3">
    <div class="col-md-4">
      <select name="form_selection" id="form_selection" class="form-select" onchange="this.form.limitstart.value=0;this.form.submit();">
        <option value="0"><?= Text::_('COM_BREEZINGFORMSNG_ALL_FORMS'); ?></option>
        <?php foreach ($this->forms as $form): ?>
          <option value="<?= (int) $form['id']; ?>" <?= ($this->formSelection == $form['id']) ? 'selected' : ''; ?>>
            <?= htmlspecialchars($form['title']); ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-4">
      <div class="input-group">
        <label class="visually-hidden" for="filter_search"><?= Text::_('JSEARCH_FILTER'); ?></label>
        <input type="text" name="searchterm" id="filter_search" class="form-control" placeholder="<?= Text::_('JSEARCH_FILTER'); ?>" value="<?= htmlspecialchars($this->searchTerm); ?>">
        <button type="submit" id="filter_search_submit" class="btn btn-primary" onclick="this.form.limitstart.value=0;" title="<?= $headerTitle('JSEARCH_FILTER_SUBMIT'); ?>" aria-label="<?= $headerTitle('JSEARCH_FILTER_SUBMIT'); ?>">
          <span class="icon-search" aria-hidden="true"></span>
        </button>
        <?php if ($this->searchTerm !== ''): ?>
          <a href="index.php?option=com_breezingformsng&view=records&form_selection=<?= $this->formSelection; ?>" id="filter_search_clear" class="btn btn-secondary" title="<?= $headerTitle('JSEARCH_FILTER_CLEAR'); ?>"><?= Text::_('JSEARCH_FILTER_CLEAR'); ?></a>
        <?php endif; ?>
      </div>
    </div>
    <div class="col-md-4 text-end">
      <span class="badge bg-secondary"><?= (int) $total; ?> <?= Text::_('COM_BREEZINGFORMSNG_RECORDS'); ?></span>
    </div>
  </div>

  <table class="table table-striped table-hover" id="recordList">
    <thead>
      <tr>
        <th class="w-1 text-center"><input type="checkbox" class="form-check-input" onclick="Joomla.checkAll(this)" title="<?= Text::_('JGLOBAL_CHECK_ALL'); ?>"></th>
        <th title="<?= $headerTitle('COM_BREEZINGFORMSNG_ID_DESC'); ?>"><?= HTMLHelper::_('searchtools.sort', 'COM_BREEZINGFORMSNG_ID', 'records.id', $listDirn, $listOrder); ?></th>
        <th title="<?= $headerTitle('COM_BREEZINGFORMSNG_RECORDS_FORM_DESC'); ?>"><?= HTMLHelper::_('searchtools.sort', 'COM_BREEZINGFORMSNG_FORM', 'forms.title', $listDirn, $listOrder); ?></th>
        <th title="<?= $headerTitle('COM_BREEZINGFORMSNG_IP_DESC'); ?>"><?= HTMLHelper::_('searchtools.sort', 'COM_BREEZINGFORMSNG_IP', 'records.ip', $listDirn, $listOrder); ?></th>
        <th title="<?= $headerTitle('COM_BREEZINGFORMSNG_USER_DESC'); ?>"><?= HTMLHelper::_('searchtools.sort', 'COM_BREEZINGFORMSNG_USER', 'records.username', $listDirn, $listOrder); ?></th>
        <th class="text-center" title="<?= $headerTitle('COM_BREEZINGFORMSNG_VIEWED_DESC'); ?>"><?= HTMLHelper::_('searchtools.sort', 'COM_BREEZINGFORMSNG_VIEWED', 'records.viewed', $listDirn, $listOrder); ?></th>
        <th class="text-center" title="<?= $headerTitle('COM_BREEZINGFORMSNG_EXPORTED_DESC'); ?>"><?= HTMLHelper::_('searchtools.sort', 'COM_BREEZINGFORMSNG_EXPORTED', 'records.exported', $listDirn, $listOrder); ?></th>
        <th class="text-center" title="<?= $headerTitle('COM_BREEZINGFORMSNG_ARCHIVED_DESC'); ?>"><?= HTMLHelper::_('searchtools.sort', 'COM_BREEZINGFORMSNG_ARCHIVED', 'records.archived', $listDirn, $listOrder); ?></th>
        <th title="<?= $headerTitle('COM_BREEZINGFORMSNG_SUBMITTED_DESC'); ?>"><?= HTMLHelper::_('searchtools.sort', 'COM_BREEZINGFORMSNG_SUBMITTED', 'records.submitted', $listDirn, $listOrder); ?></th>
        <th title="<?= $headerTitle('COM_BREEZINGFORMSNG_MODIFIED_DESC'); ?>"><?= HTMLHelper::_('searchtools.sort', 'COM_BREEZINGFORMSNG_FORMS_MODIFIED', 'records.modified', $listDirn, $listOrder); ?></th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($this->records)): ?>
        <tr><td colspan="10" class="text-center"><?= Text::_('COM_BREEZINGFORMSNG_NO_RECORDS_FOUND'); ?></td></tr>
      <?php else: ?>
        <?php foreach ($this->records as $i => $rec): ?>
          <?php $recId = (int) $rec['id']; ?>
          <tr>
            <td class="text-center"><?= HTMLHelper::_('grid.id', $i, $recId); ?></td>
            <td><?= $recId; ?></td>
            <td>
              <a href="<?= htmlspecialchars($editUrl($recId), ENT_QUOTES, 'UTF-8'); ?>" title="<?= $headerTitle('JACTION_EDIT'); ?>">
                <?= htmlspecialchars((string) $rec['form_title']); ?>
              </a>
            </td>
            <td><?= htmlspecialchars((string) $rec['ip']); ?></td>
            <td><?= htmlspecialchars((string) ($rec['user_full_name'] ?: $rec['username'])); ?></td>
            <td class="text-center">
              <a href="#" onclick="bfToggleFlag(<?= $recId; ?>, 'bfrecord_viewed', this); return false;" title="<?= Text::_('COM_BREEZINGFORMSNG_TOGGLE_VIEWED'); ?>">
                <span class="<?= $rec['viewed'] ? 'icon-check text-success' : 'icon-times text-danger'; ?>"></span>
              </a>
            </td>
            <td class="text-center">
              <a href="#" onclick="bfToggleFlag(<?= $recId; ?>, 'bfrecord_exported', this); return false;" title="<?= Text::_('COM_BREEZINGFORMSNG_TOGGLE_EXPORTED'); ?>">
                <span class="<?= $rec['exported'] ? 'icon-check text-success' : 'icon-times text-danger'; ?>"></span>
              </a>
            </td>
            <td class="text-center">
              <a href="#" onclick="bfToggleFlag(<?= $recId; ?>, 'bfrecord_archived', this); return false;" title="<?= Text::_('COM_BREEZINGFORMSNG_TOGGLE_ARCHIVED'); ?>">
                <span class="<?= $rec['archived'] ? 'icon-check text-success' : 'icon-times text-danger'; ?>"></span>
              </a>
            </td>
            <td><?= htmlspecialchars((string) $rec['submitted']); ?></td>
            <td><?= $rec['modified'] ? htmlspecialchars((string) $rec['modified']) : '—'; ?></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>

  <?php if ($totalPages > 1): ?>
  <nav aria-label="<?= Text::_('JLIB_HTML_PAGINATION'); ?>">
    <ul class="pagination justify-content-center">
      <?php if ($currentPage > 0): ?>
        <li class="page-item"><a class="page-link" href="<?= $pageUrl(0); ?>">&laquo;</a></li>
        <li class="page-item"><a class="page-link" href="<?= $pageUrl(($currentPage - 1) * $limit); ?>">&lsaquo;</a></li>
      <?php endif; ?>
      <?php
      $startPage = max(0, $currentPage - 4);
      $endPage   = min($totalPages - 1, $currentPage + 4);
      for ($p = $startPage; $p <= $endPage; $p++):
      ?>
        <li class="page-item <?= ($p === $currentPage) ? 'active' : ''; ?>">
          <a class="page-link" href="<?= $pageUrl($p * $limit); ?>"><?= $p + 1; ?></a>
        </li>
      <?php endfor; ?>
      <?php if ($currentPage < $totalPages - 1): ?>
        <li class="page-item"><a class="page-link" href="<?= $pageUrl(($currentPage + 1) * $limit); ?>">&rsaquo;</a></li>
        <li class="page-item"><a class="page-link" href="<?= $pageUrl(($totalPages - 1) * $limit); ?>">&raquo;</a></li>
      <?php endif; ?>
    </ul>
  </nav>
  <div class="text-center text-muted small mb-3">
    <?= Text::plural('COM_BREEZINGFORMSNG_PAGINATION_INFO', $total, $limitStart + 1, min($limitStart + $limit, $total)); ?>
  </div>
  <?php endif; ?>

  <input type="hidden" name="task" value="">
  <input type="hidden" name="view" value="records">
  <input type="hidden" name="boxchecked" value="0">
  <input type="hidden" name="filter_order" value="<?= htmlspecialchars($listOrder); ?>">
  <input type="hidden" name="filter_order_Dir" value="<?= htmlspecialchars($listDirn); ?>">
  <input type="hidden" name="limitstart" value="<?= $limitStart; ?>">
  <?= HTMLHelper::_('form.token'); ?>
</form>

<?php
// Web assets for this view are registered in Records\HtmlView::display() —
// useScript() calls placed in the template body do not take effect here.
?>
