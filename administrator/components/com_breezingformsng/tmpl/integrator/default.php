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
?>
<form action="index.php?option=com_breezingformsng&amp;view=integrator" method="post" name="adminForm" id="adminForm">
  <div id="j-main-container" class="j-main-container">
  <?= LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>

  <table class="table table-striped table-hover">
    <thead>
      <tr>
        <th class="w-1 text-center">
          <?= HTMLHelper::_('grid.checkall'); ?>
        </th>
        <th class="w-1"><?= HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'rules.id', $listDirn, $listOrder); ?></th>
        <th><?= HTMLHelper::_('searchtools.sort', 'COM_BREEZINGFORMSNG_INTEGRATOR_RULENAME', 'rules.name', $listDirn, $listOrder); ?></th>
        <th><?= HTMLHelper::_('searchtools.sort', 'COM_BREEZINGFORMSNG_TYPE', 'rules.type', $listDirn, $listOrder); ?></th>
        <th><?= HTMLHelper::_('searchtools.sort', 'COM_BREEZINGFORMSNG_FORM', 'forms.name', $listDirn, $listOrder); ?></th>
        <th><?= HTMLHelper::_('searchtools.sort', 'COM_BREEZINGFORMSNG_INTEGRATOR_TABLE', 'rules.reference_table', $listDirn, $listOrder); ?></th>
        <th class="text-center w-10"><?= HTMLHelper::_('searchtools.sort', 'JPUBLISHED', 'rules.published', $listDirn, $listOrder); ?></th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($this->rules)): ?>
        <tr><td colspan="7" class="text-center"><?= Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?></td></tr>
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
              <a href="#" onclick="bfTogglePublished(<?= (int) $rule->id; ?>, 'integrator', this); return false;"
                 title="<?= $rule->published ? Text::_('JPUBLISHED') : Text::_('JUNPUBLISHED'); ?>">
                <span class="<?= $rule->published ? 'icon-publish' : 'icon-unpublish'; ?>" aria-hidden="true"></span>
              </a>
            </td>
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
