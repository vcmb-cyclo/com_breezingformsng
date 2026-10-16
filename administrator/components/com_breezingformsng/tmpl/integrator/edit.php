<?php
/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

\defined('_JEXEC') or die;

/** @var \Vcmb\Component\BreezingformsNG\Administrator\View\Integrator\HtmlView $this */
use Joomla\CMS\Editor\Editor;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

$rule        = $this->rule;
$ruleId      = $rule ? (int) $rule->id : 0;
$isNew       = ($ruleId === 0);
$baseUrl     = 'index.php?option=com_breezingformsng&view=integrator';
$editUrl     = $baseUrl . '&layout=edit&id=' . $ruleId;

$operators = [
    '='     => 'equals',
    '<>'    => 'not equal',
    '>'     => 'greater than',
    '<'     => 'less than',
    '>='    => 'equals or greater than',
    '<='    => 'equals or less than',
    '%...%' => 'in value',
    '%...'  => 'starts with',
    '...%'  => 'ends with',
];

$fakeName = static fn(string $n): bool => \in_array($n, ['bfFakeName', 'bfFakeName2', 'bfFakeName3', 'bfFakeName4'], true);
?>

<form action="<?= $baseUrl; ?>" method="post" id="integratorActionForm">
  <input type="hidden" name="task" value="">
  <input type="hidden" name="id" value="<?= $ruleId; ?>">
  <input type="hidden" name="itemId" value="0">
  <input type="hidden" name="criteriaId" value="0">
  <input type="hidden" name="publish_id" value="0">
  <?= HTMLHelper::_('form.token'); ?>
</form>

<?php /* ── Base data card ── */ ?>
<div class="card mb-3">
  <div class="card-header"><strong><?= Text::_('COM_BREEZINGFORMSNG_INTEGRATOR_BASE_DATA'); ?></strong></div>
  <div class="card-body">
    <form action="<?= $baseUrl; ?>" method="post" name="adminForm" id="adminForm">
      <div class="row g-3 align-items-end">
        <div class="col-md-3">
          <label class="form-label"><?= Text::_('COM_BREEZINGFORMSNG_INTEGRATOR_RULENAME'); ?></label>
          <input type="text" name="rule_name" class="form-control"
                 value="<?= $rule ? htmlspecialchars($rule->name) : ''; ?>"
                 <?= $rule ? 'disabled' : ''; ?>>
        </div>

        <?php if ($isNew): ?>
        <div class="col-md-2">
          <label class="form-label"><?= Text::_('COM_BREEZINGFORMSNG_INTEGRATOR_SHOWING'); ?></label>
          <select name="formfilter" class="form-select" onchange="this.form.task.value='integrator.edit';this.form.submit();">
            <option value="all" <?= $this->formFilter === 'all' ? 'selected' : ''; ?>>All</option>
            <option value="published" <?= $this->formFilter === 'published' ? 'selected' : ''; ?>>Published</option>
            <option value="unpublished" <?= $this->formFilter === 'unpublished' ? 'selected' : ''; ?>>Unpublished</option>
          </select>
        </div>
        <?php endif; ?>

        <div class="col-md-3">
          <label class="form-label"><?= Text::_('COM_BREEZINGFORMSNG_FORM'); ?></label>
          <select name="form_id" class="form-select" <?= $rule ? 'disabled' : ''; ?>>
            <?php foreach ($this->forms as $form): ?>
              <option value="<?= (int) $form->id; ?>" <?= ($rule && $rule->form_id == $form->id) ? 'selected' : ''; ?>>
                <?= htmlspecialchars($form->name); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-3">
          <label class="form-label"><?= Text::_('COM_BREEZINGFORMSNG_INTEGRATOR_TABLE'); ?></label>
          <select name="reference_table" class="form-select" <?= $rule ? 'disabled' : ''; ?>>
            <?php foreach ($this->tableNames as $tblName): ?>
              <option value="<?= htmlspecialchars($tblName); ?>"
                <?= ($rule && $tblName === $rule->reference_table) ? 'selected' : ''; ?>>
                <?= htmlspecialchars($tblName); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <?php if ($isNew): ?>
        <div class="col-md-2">
          <label class="form-label"><?= Text::_('COM_BREEZINGFORMSNG_TYPE'); ?></label>
          <div>
            <?= Text::_('COM_BREEZINGFORMSNG_INTEGRATOR_INSERT'); ?>
            <input type="radio" name="type" value="insert" checked class="form-check-input ms-1">
            &nbsp;
            <?= Text::_('COM_BREEZINGFORMSNG_INTEGRATOR_UPDATE'); ?>
            <input type="radio" name="type" value="update" class="form-check-input ms-1">
          </div>
        </div>
        <?php else: ?>
        <div class="col-md-2">
          <label class="form-label"><?= Text::_('COM_BREEZINGFORMSNG_TYPE'); ?></label>
          <div class="form-control-plaintext"><?= htmlspecialchars($rule->type); ?></div>
        </div>
        <?php endif; ?>
      </div>

      <input type="hidden" name="task" value="<?= $isNew ? 'integrator.save' : 'integrator.edit'; ?>">
      <input type="hidden" name="id" value="<?= $ruleId; ?>">
      <?= HTMLHelper::_('form.token'); ?>
    </form>
  </div>
</div>

<?php if ($rule !== null): ?>

<?php /* ── Items card ── */ ?>
<div class="card mb-3">
  <div class="card-header"><strong><?= Text::_('COM_BREEZINGFORMSNG_INTEGRATOR_DATA_INTEGRATION'); ?></strong></div>
  <div class="card-body p-0">
    <form action="<?= $baseUrl; ?>" method="post" name="addItemForm" id="addItemForm">
      <table class="table table-striped mb-0">
        <thead>
          <tr>
            <th><?= Text::_('COM_BREEZINGFORMSNG_INTEGRATOR_FORM_ELEMENT_INCOMING'); ?></th>
            <th class="text-center w-5"><?= Text::_('COM_BREEZINGFORMSNG_INTEGRATOR_COPY_TO'); ?></th>
            <th><?= Text::_('COM_BREEZINGFORMSNG_INTEGRATOR_DB_FIELD_OUTGOING'); ?></th>
            <th></th>
            <th class="text-center w-5"><?= Text::_('JPUBLISHED'); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php /* New item row */ ?>
          <tr class="table-secondary">
            <td>
              <select name="element_id" class="form-select form-select-sm">
                <?php foreach ($this->formElements as $el): if ($fakeName($el->name)) continue; ?>
                  <option value="<?= (int) $el->id; ?>"><?= htmlspecialchars($el->name); ?> (<?= htmlspecialchars($el->type); ?>)</option>
                <?php endforeach; ?>
              </select>
            </td>
            <td class="text-center">=&gt;</td>
            <td>
              <select name="reference_column" class="form-select form-select-sm">
                <?php foreach ($this->tableColumns as $colName => $colType): ?>
                  <option value="<?= htmlspecialchars($colName); ?>"><?= htmlspecialchars($colName); ?> (<?= htmlspecialchars($colType); ?>)</option>
                <?php endforeach; ?>
              </select>
            </td>
            <td colspan="2">
              <button type="submit" class="btn btn-sm btn-primary"><?= Text::_('COM_BREEZINGFORMSNG_INTEGRATOR_ADD'); ?></button>
            </td>
          </tr>

          <?php foreach ($this->items as $item): ?>
            <tr>
              <td><?= htmlspecialchars($item->element_name); ?> (<?= htmlspecialchars($item->element_type); ?>)</td>
              <td class="text-center">=&gt;</td>
              <td>
                <?= htmlspecialchars($item->reference_column); ?>
                (<?= htmlspecialchars($this->tableColumns[$item->reference_column] ?? ''); ?>)
              </td>
              <td>
                <div id="codeBlock<?= (int) $item->id; ?>" class="mb-2" style="display:none">
                  <?php
                  $editor = Editor::getInstance('codemirror');
                  echo $editor->display('code' . $item->id, $item->code ?? '', '100%', '300', 40, 20, false, 'code' . $item->id, null, null, ['syntax' => 'php']);
                  ?>
                  <a class="btn btn-sm btn-secondary mt-1" href="#"
                     onclick="document.saveCodeForm.itemId.value=<?= (int) $item->id; ?>;document.saveCodeForm.code.value=Joomla.editors.instances['code<?= (int) $item->id; ?>'].getValue();document.saveCodeForm.submit();return false;">
                    <?= Text::_('JSAVE'); ?>
                  </a>
                </div>
                <a href="#" onclick="var b=document.getElementById('codeBlock<?= (int) $item->id; ?>');b.style.display=b.style.display==='none'?'':'none';return false;">
                  <?= Text::_('COM_BREEZINGFORMSNG_CODE'); ?>
                </a>
                &nbsp;|&nbsp;
                <button type="button" class="btn btn-link text-danger p-0 border-0 align-baseline"
                        onclick="var f=document.getElementById('integratorActionForm');f.task.value='integrator.removeItem';f.itemId.value='<?= (int) $item->id; ?>';f.submit();">
                  <?= Text::_('COM_BREEZINGFORMSNG_INTEGRATOR_REMOVE'); ?>
                </button>
              </td>
              <td class="text-center">
                <?php if ($item->published): ?>
                  <button type="button" class="tbody-icon active border-0 bg-transparent"
                          onclick="var f=document.getElementById('integratorActionForm');f.task.value='integrator.unpublishItem';f.publish_id.value='<?= (int) $item->id; ?>';f.submit();"><span class="icon-publish" aria-hidden="true"></span></button>
                <?php else: ?>
                  <button type="button" class="tbody-icon border-0 bg-transparent"
                          onclick="var f=document.getElementById('integratorActionForm');f.task.value='integrator.publishItem';f.publish_id.value='<?= (int) $item->id; ?>';f.submit();"><span class="icon-unpublish" aria-hidden="true"></span></button>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <input type="hidden" name="task" value="integrator.addItem">
      <input type="hidden" name="id" value="<?= $ruleId; ?>">
      <?= HTMLHelper::_('form.token'); ?>
    </form>
  </div>
</div>

<form action="<?= $baseUrl; ?>" method="post" name="saveCodeForm" id="saveCodeForm">
  <input type="hidden" name="task" value="integrator.saveCode">
  <input type="hidden" name="code" value="">
  <input type="hidden" name="itemId" value="-1">
  <input type="hidden" name="id" value="<?= $ruleId; ?>">
  <?= HTMLHelper::_('form.token'); ?>
</form>

<?php if ($rule->type === 'update'): ?>

<?php /* ── Criteria — Form ── */ ?>
<div class="card mb-3">
  <div class="card-header"><strong><?= Text::_('COM_BREEZINGFORMSNG_INTEGRATOR_UPDATE_CRITERIA_FORM'); ?></strong></div>
  <div class="card-body p-0">
    <form action="<?= $baseUrl; ?>" method="post" name="addCriteriaForm">
      <table class="table table-striped mb-0">
        <thead>
          <tr>
            <th><?= Text::_('COM_BREEZINGFORMSNG_INTEGRATOR_DB_FIELD_VALUE'); ?></th>
            <th><?= Text::_('COM_BREEZINGFORMSNG_INTEGRATOR_OPERATION'); ?></th>
            <th><?= Text::_('COM_BREEZINGFORMSNG_INTEGRATOR_FORM_ELEMENT_VALUE'); ?></th>
            <th><?= Text::_('COM_BREEZINGFORMSNG_INTEGRATOR_AND_OR'); ?></th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr class="table-secondary">
            <td>
              <select name="reference_column" class="form-select form-select-sm">
                <?php foreach ($this->tableColumns as $colName => $colType): ?>
                  <option value="<?= htmlspecialchars($colName); ?>"><?= htmlspecialchars($colName); ?> (<?= htmlspecialchars($colType); ?>)</option>
                <?php endforeach; ?>
              </select>
            </td>
            <td><?= HTMLHelper::_('select.genericlist', array_combine(array_keys($operators), array_values($operators)), 'operator', 'class="form-select form-select-sm"'); ?></td>
            <td>
              <select name="element_id" class="form-select form-select-sm">
                <?php foreach ($this->formElements as $el): if ($fakeName($el->name)) continue; ?>
                  <option value="<?= (int) $el->id; ?>"><?= htmlspecialchars($el->name); ?> (<?= htmlspecialchars($el->type); ?>)</option>
                <?php endforeach; ?>
              </select>
            </td>
            <td>
              <?= Text::_('COM_BREEZINGFORMSNG_INTEGRATOR_AND'); ?> <input type="radio" name="andor" value="AND" checked class="form-check-input">
              <?= Text::_('COM_BREEZINGFORMSNG_INTEGRATOR_OR'); ?> <input type="radio" name="andor" value="OR" class="form-check-input">
            </td>
            <td><button type="submit" class="btn btn-sm btn-primary"><?= Text::_('COM_BREEZINGFORMSNG_INTEGRATOR_ADD'); ?></button></td>
          </tr>
          <?php foreach ($this->criteria as $crit): ?>
            <tr>
              <td><?= htmlspecialchars($crit->reference_column); ?> (<?= htmlspecialchars($this->tableColumns[$crit->reference_column] ?? ''); ?>)</td>
              <td><?= htmlspecialchars($crit->operator); ?></td>
              <td><?= htmlspecialchars($crit->element_name); ?> (<?= htmlspecialchars($crit->element_type); ?>)</td>
              <td><?= htmlspecialchars($crit->andor); ?></td>
              <td>
                <button type="button" class="btn btn-link text-danger p-0 border-0 align-baseline"
                        onclick="var f=document.getElementById('integratorActionForm');f.task.value='integrator.removeCriteria';f.criteriaId.value='<?= (int) $crit->id; ?>';f.submit();">
                  <?= Text::_('COM_BREEZINGFORMSNG_INTEGRATOR_REMOVE'); ?>
                </button>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <input type="hidden" name="task" value="integrator.addCriteria">
      <input type="hidden" name="id" value="<?= $ruleId; ?>">
      <?= HTMLHelper::_('form.token'); ?>
    </form>
  </div>
</div>

<?php /* ── Criteria — Joomla ── */ ?>
<div class="card mb-3">
  <div class="card-header"><strong><?= Text::_('COM_BREEZINGFORMSNG_INTEGRATOR_UPDATE_CRITERIA_JOOMLA'); ?></strong></div>
  <div class="card-body p-0">
    <form action="<?= $baseUrl; ?>" method="post" name="addCriteriaJoomlaForm">
      <table class="table table-striped mb-0">
        <thead>
          <tr>
            <th><?= Text::_('COM_BREEZINGFORMSNG_INTEGRATOR_DB_FIELD_VALUE'); ?></th>
            <th><?= Text::_('COM_BREEZINGFORMSNG_INTEGRATOR_OPERATION'); ?></th>
            <th><?= Text::_('COM_BREEZINGFORMSNG_INTEGRATOR_JOOMLA_OBJECT_VALUE'); ?></th>
            <th><?= Text::_('COM_BREEZINGFORMSNG_INTEGRATOR_AND_OR'); ?></th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr class="table-secondary">
            <td>
              <select name="reference_column" class="form-select form-select-sm">
                <?php foreach ($this->tableColumns as $colName => $colType): ?>
                  <option value="<?= htmlspecialchars($colName); ?>"><?= htmlspecialchars($colName); ?> (<?= htmlspecialchars($colType); ?>)</option>
                <?php endforeach; ?>
              </select>
            </td>
            <td><?= HTMLHelper::_('select.genericlist', array_combine(array_keys($operators), array_values($operators)), 'operator', 'class="form-select form-select-sm"'); ?></td>
            <td>
              <select name="joomla_object" class="form-select form-select-sm">
                <option value="Userid"><?= Text::_('COM_BREEZINGFORMSNG_INTEGRATOR_USERID'); ?></option>
                <option value="Username"><?= Text::_('COM_BREEZINGFORMSNG_INTEGRATOR_USERNAME'); ?></option>
                <option value="Language"><?= Text::_('COM_BREEZINGFORMSNG_INTEGRATOR_LANGUAGE'); ?></option>
                <option value="Date"><?= Text::_('COM_BREEZINGFORMSNG_INTEGRATOR_DATE'); ?></option>
              </select>
            </td>
            <td>
              <?= Text::_('COM_BREEZINGFORMSNG_INTEGRATOR_AND'); ?> <input type="radio" name="andor" value="AND" checked class="form-check-input">
              <?= Text::_('COM_BREEZINGFORMSNG_INTEGRATOR_OR'); ?> <input type="radio" name="andor" value="OR" class="form-check-input">
            </td>
            <td><button type="submit" class="btn btn-sm btn-primary"><?= Text::_('COM_BREEZINGFORMSNG_INTEGRATOR_ADD'); ?></button></td>
          </tr>
          <?php foreach ($this->criteriaJoomla as $crit): ?>
            <tr>
              <td><?= htmlspecialchars($crit->reference_column); ?> (<?= htmlspecialchars($this->tableColumns[$crit->reference_column] ?? ''); ?>)</td>
              <td><?= htmlspecialchars($crit->operator); ?></td>
              <td><?= htmlspecialchars($crit->joomla_object); ?></td>
              <td><?= htmlspecialchars($crit->andor); ?></td>
              <td>
                <button type="button" class="btn btn-link text-danger p-0 border-0 align-baseline"
                        onclick="var f=document.getElementById('integratorActionForm');f.task.value='integrator.removeCriteriaJoomla';f.criteriaId.value='<?= (int) $crit->id; ?>';f.submit();">
                  <?= Text::_('COM_BREEZINGFORMSNG_INTEGRATOR_REMOVE'); ?>
                </button>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <input type="hidden" name="task" value="integrator.addCriteriaJoomla">
      <input type="hidden" name="id" value="<?= $ruleId; ?>">
      <?= HTMLHelper::_('form.token'); ?>
    </form>
  </div>
</div>

<?php /* ── Criteria — Fixed ── */ ?>
<div class="card mb-3">
  <div class="card-header"><strong><?= Text::_('COM_BREEZINGFORMSNG_INTEGRATOR_UPDATE_CRITERIA_FIXED'); ?></strong></div>
  <div class="card-body p-0">
    <form action="<?= $baseUrl; ?>" method="post" name="addCriteriaFixedForm">
      <table class="table table-striped mb-0">
        <thead>
          <tr>
            <th><?= Text::_('COM_BREEZINGFORMSNG_INTEGRATOR_DB_FIELD_VALUE'); ?></th>
            <th><?= Text::_('COM_BREEZINGFORMSNG_INTEGRATOR_OPERATION'); ?></th>
            <th><?= Text::_('COM_BREEZINGFORMSNG_INTEGRATOR_FIXED_VALUE'); ?></th>
            <th><?= Text::_('COM_BREEZINGFORMSNG_INTEGRATOR_AND_OR'); ?></th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr class="table-secondary">
            <td>
              <select name="reference_column" class="form-select form-select-sm">
                <?php foreach ($this->tableColumns as $colName => $colType): ?>
                  <option value="<?= htmlspecialchars($colName); ?>"><?= htmlspecialchars($colName); ?> (<?= htmlspecialchars($colType); ?>)</option>
                <?php endforeach; ?>
              </select>
            </td>
            <td><?= HTMLHelper::_('select.genericlist', array_combine(array_keys($operators), array_values($operators)), 'operator', 'class="form-select form-select-sm"'); ?></td>
            <td><input type="text" name="fixed_value" class="form-control form-control-sm"></td>
            <td>
              <?= Text::_('COM_BREEZINGFORMSNG_INTEGRATOR_AND'); ?> <input type="radio" name="andor" value="AND" checked class="form-check-input">
              <?= Text::_('COM_BREEZINGFORMSNG_INTEGRATOR_OR'); ?> <input type="radio" name="andor" value="OR" class="form-check-input">
            </td>
            <td><button type="submit" class="btn btn-sm btn-primary"><?= Text::_('COM_BREEZINGFORMSNG_INTEGRATOR_ADD'); ?></button></td>
          </tr>
          <?php foreach ($this->criteriaFixed as $crit): ?>
            <tr>
              <td><?= htmlspecialchars($crit->reference_column); ?> (<?= htmlspecialchars($this->tableColumns[$crit->reference_column] ?? ''); ?>)</td>
              <td><?= htmlspecialchars($crit->operator); ?></td>
              <td><?= htmlspecialchars($crit->fixed_value); ?></td>
              <td><?= htmlspecialchars($crit->andor); ?></td>
              <td>
                <button type="button" class="btn btn-link text-danger p-0 border-0 align-baseline"
                        onclick="var f=document.getElementById('integratorActionForm');f.task.value='integrator.removeCriteriaFixed';f.criteriaId.value='<?= (int) $crit->id; ?>';f.submit();">
                  <?= Text::_('COM_BREEZINGFORMSNG_INTEGRATOR_REMOVE'); ?>
                </button>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <input type="hidden" name="task" value="integrator.addCriteriaFixed">
      <input type="hidden" name="id" value="<?= $ruleId; ?>">
      <?= HTMLHelper::_('form.token'); ?>
    </form>
  </div>
</div>

<?php endif; /* update criteria */ ?>

<?php /* ── Finalize code card ── */ ?>
<div class="card mb-3">
  <div class="card-header"><strong><?= Text::_('COM_BREEZINGFORMSNG_INTEGRATOR_FINALIZE_CODE'); ?></strong></div>
  <div class="card-body">
    <form action="<?= $baseUrl; ?>" method="post" name="saveFinalizeCodeForm">
      <?php
      $editor = Editor::getInstance('codemirror');
      echo $editor->display('finalizeCode', $rule->finalize_code ?? '', '100%', '350', 40, 20, false, 'finalizeCode', null, null, ['syntax' => 'php']);
      ?>
      <button type="submit" class="btn btn-primary mt-2"><?= Text::_('JSAVE'); ?></button>
      <input type="hidden" name="task" value="integrator.saveFinalizeCode">
      <input type="hidden" name="id" value="<?= $ruleId; ?>">
      <?= HTMLHelper::_('form.token'); ?>
    </form>
  </div>
</div>

<?php endif; /* rule !== null */ ?>
