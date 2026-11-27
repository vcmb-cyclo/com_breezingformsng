<?php
/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

\defined('_JEXEC') or die;

/** @var \Vcmb\Component\BreezingformsNG\Administrator\View\Integrator\HtmlView $this */
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;

$listOrder = $this->escape((string) $this->state->get('list.ordering'));
$listDirn  = $this->escape((string) $this->state->get('list.direction'));
$date = static function (mixed $value): string {
    $value = (string) ($value ?? '');

    return $value !== '' && $value !== '0000-00-00 00:00:00'
        ? HTMLHelper::_('date', $value, Text::_('DATE_FORMAT_LC5'))
        : '-';
};
$authorNames = $this->authorNames;
$author = function (mixed $value) use ($authorNames): string {
    $value = (string) ($value ?? '');

    return $value !== '' ? $this->escape($authorNames[$value] ?? $value) : '-';
};
?>
<form action="index.php?option=com_breezingformsng&amp;view=integrator" method="post" name="adminForm" id="adminForm" class="bf-list-filters">
  <div id="j-main-container" class="j-main-container">
  <?= LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>

  <table class="table table-striped table-hover" data-name="bfng-integrator">
    <thead>
      <tr>
        <td class="w-1 text-center">
          <?= HTMLHelper::_('grid.checkall'); ?>
        </td>
        <th class="w-1"><?= HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'rules.id', $listDirn, $listOrder); ?></th>
        <th><?= HTMLHelper::_('searchtools.sort', 'COM_BREEZINGFORMSNG_INTEGRATOR_RULENAME', 'rules.name', $listDirn, $listOrder); ?></th>
        <th><?= HTMLHelper::_('searchtools.sort', 'COM_BREEZINGFORMSNG_TYPE', 'rules.type', $listDirn, $listOrder); ?></th>
        <th><?= HTMLHelper::_('searchtools.sort', 'COM_BREEZINGFORMSNG_FORM', 'forms.name', $listDirn, $listOrder); ?></th>
        <th><?= HTMLHelper::_('searchtools.sort', 'COM_BREEZINGFORMSNG_INTEGRATOR_TABLE', 'rules.reference_table', $listDirn, $listOrder); ?></th>
        <th class="text-center w-10"><?= HTMLHelper::_('searchtools.sort', 'JPUBLISHED', 'rules.published', $listDirn, $listOrder); ?></th>
        <th class="d-none text-nowrap"><?= HTMLHelper::_('searchtools.sort', 'COM_BREEZINGFORMSNG_FORMS_CREATED', 'rules.created', $listDirn, $listOrder); ?></th>
        <th class="d-none"><?= HTMLHelper::_('searchtools.sort', 'COM_BREEZINGFORMSNG_FORMS_CREATED_BY', 'rules.created_by', $listDirn, $listOrder); ?></th>
        <th class="text-nowrap"><?= HTMLHelper::_('searchtools.sort', 'COM_BREEZINGFORMSNG_FORMS_MODIFIED', 'rules.modified', $listDirn, $listOrder); ?></th>
        <th class="d-none"><?= HTMLHelper::_('searchtools.sort', 'COM_BREEZINGFORMSNG_FORMS_MODIFIED_BY', 'rules.modified_by', $listDirn, $listOrder); ?></th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($this->rules)): ?>
        <tr><td colspan="11" class="text-center"><?= Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?></td></tr>
      <?php else: ?>
        <?php foreach ($this->rules as $i => $rule): ?>
          <tr>
            <td class="text-center"><?= HTMLHelper::_('grid.id', $i, $rule->id); ?></td>
            <td><?= (int) $rule->id; ?></td>
            <td>
              <a href="index.php?option=com_breezingformsng&view=integrator&layout=edit&id=<?= (int) $rule->id; ?>">
                <?= htmlspecialchars($rule->name); ?>
              </a>
            </td>
            <td><?= htmlspecialchars($rule->type); ?></td>
            <td><?= htmlspecialchars($rule->form_name); ?></td>
            <td><?= htmlspecialchars($rule->reference_table); ?></td>
            <td class="text-center">
                <?php $isPublished = (int) $rule->published === 1; ?>
                <?php if ((int) $rule->published === -2) : ?>
                  <span class="icon-trash text-danger" title="<?= $this->escape(Text::_('JTRASHED')); ?>" role="img" aria-label="<?= $this->escape(Text::_('JTRASHED')); ?>"></span>
                <?php else : ?>
                  <button type="button" class="tbody-icon" onclick="bfTogglePublished(<?= (int) $rule->id; ?>, 'integrator', this); return false;"
                     title="<?= $isPublished ? Text::_('JPUBLISHED') : Text::_('JUNPUBLISHED'); ?>"
                     aria-pressed="<?= $isPublished ? 'true' : 'false'; ?>"
                     aria-label="<?= $isPublished ? Text::_('JPUBLISHED') : Text::_('JUNPUBLISHED'); ?>">
                    <span class="<?= $isPublished ? 'icon-publish' : 'icon-unpublish'; ?>" aria-hidden="true"></span>
                  </button>
                <?php endif; ?>
            </td>
            <td class="d-none text-nowrap"><?= $date($rule->created); ?></td>
            <td class="d-none"><?= $author($rule->created_by); ?></td>
            <td class="text-nowrap"><?= $date($rule->modified); ?></td>
            <td class="d-none"><?= $author($rule->modified_by); ?></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>

  <?= $this->pagination->getListFooter(); ?>

  <input type="hidden" name="task" value="">
  <input type="hidden" name="publish_id" value="0">
  <input type="hidden" name="boxchecked" value="0">
  <?= HTMLHelper::_('form.token'); ?>
  </div>
</form>

<?php
// Web assets for this view are registered in Integrator\HtmlView::display() —
// useScript() calls placed in the template body do not take effect here.
?>
