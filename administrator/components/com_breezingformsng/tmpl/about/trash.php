<?php

/**
 * @psalm-suppress InvalidScope
 *
 * BreezingForms NG - A Joomla Forms Application
 *
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 *
 * Tools / Trash: the trashed records per form, and the action that empties the trash.
 */

defined('_JEXEC') or die('Direct Access to this location is not allowed.');

/** @var \Vcmb\Component\BreezingformsNG\Administrator\View\About\HtmlView $this */

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$trashed = array_values(array_filter(
    $this->recordInventory,
    static fn (array $entry): bool => $entry['trashed'] > 0
));
$trashedTotal = array_sum(array_column($trashed, 'trashed'));
?>

<form action="<?php echo Route::_('index.php?option=com_breezingformsng&task=about.display&view=about&layout=trash'); ?>" method="post" name="adminForm" id="adminForm">
    <div class="card mt-3" id="bf-trash-section">
        <div class="card-body p-3 p-lg-4">
            <?php if (!$this->canRepair) : ?>
                <div class="alert alert-info"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_READ_ONLY_NOTICE'); ?></div>
            <?php endif; ?>
            <h3 class="h5"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_TRASHED_RECORDS'); ?></h3>
            <?php if ($trashed === [] && $this->trashedItems === []) : ?>
                <div class="alert alert-info mb-0"><?php echo Text::_('COM_BREEZINGFORMSNG_TOOLS_TRASH_EMPTY'); ?></div>
            <?php elseif ($trashed !== []) : ?>
                <p class="text-muted small"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_TRASHED_RECORDS_DESC'); ?></p>
                <div class="table-responsive">
                    <table class="table table-striped align-middle mb-3">
                        <thead>
                            <tr>
                                <th scope="col"><?php echo Text::_('COM_BREEZINGFORMSNG_TOOLS_RECORDS_FORM'); ?></th>
                                <th scope="col" class="text-center w-10"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_TRASHED_RECORDS'); ?></th>
                                <th scope="col" class="w-1"><span class="visually-hidden"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_TRASH_VIEW'); ?></span></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($trashed as $entry) : ?>
                                <?php
                                $formId = (int) $entry['form'];
                                $formName = $entry['name'] !== ''
                                    ? $entry['name']
                                    : Text::sprintf('COM_BREEZINGFORMSNG_TOOLS_RECORDS_DELETED_FORM', $formId);
                                ?>
                                <tr>
                                    <td>
                                                                                <strong><?php echo htmlspecialchars($formName, ENT_QUOTES, 'UTF-8'); ?></strong>
                                        <span class="text-muted small">(#<?php echo $formId; ?>)</span>
                                    </td>
                                    <td class="text-center">
                                        <a class="badge bg-danger text-decoration-none"
                                            href="<?php echo Route::_('index.php?option=com_breezingformsng&view=records&filter[form]=' . $formId . '&filter[state]=-2&filter[search]='); ?>"
                                            title="<?php echo htmlspecialchars(Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_TRASH_VIEW'), ENT_QUOTES, 'UTF-8'); ?>"><?php echo number_format((int) $entry['trashed'], 0, '.', ' '); ?></a>
                                    </td>
                                    <td class="text-nowrap">
                                        <a class="btn btn-sm btn-outline-secondary"
                                            href="<?php echo Route::_('index.php?option=com_breezingformsng&view=records&filter[form]=' . $formId . '&filter[state]=-2&filter[search]='); ?>">
                                            <span class="icon-eye" aria-hidden="true"></span>
                                            <?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_TRASH_VIEW'); ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="fw-bold">
                                <td><?php echo Text::_('COM_BREEZINGFORMSNG_TOOLS_RECORDS_TOTAL'); ?></td>
                                <td class="text-center"><?php echo number_format($trashedTotal, 0, '.', ' '); ?></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <button
                    type="button"
                    class="btn btn-danger"
                    <?php echo $this->canRepair ? '' : ' disabled'; ?>
                    onclick="if (window.confirm('<?php echo htmlspecialchars(Text::sprintf('COM_BREEZINGFORMSNG_ABOUT_AUDIT_TRASH_EMPTY_CONFIRM', $trashedTotal), ENT_QUOTES, 'UTF-8'); ?>')) { Joomla.submitform('about.emptyRecordsTrash'); }"
                ><span class="icon-trash" aria-hidden="true"></span> <?php echo Text::_('JTOOLBAR_EMPTY_TRASH'); ?></button>
            <?php endif; ?>
        </div>
    </div>

    <?php foreach ($this->trashedItems as $type) : ?>
        <?php $isRule = $type['view'] === 'integrator'; ?>
        <div class="card mt-3" id="bf-trash-<?php echo $type['view']; ?>">
            <div class="card-body p-3 p-lg-4">
                <h3 class="h5">
                    <?php echo Text::_($type['label']); ?>
                    <span class="badge bg-danger ms-1"><?php echo number_format($type['count'], 0, '.', ' '); ?></span>
                </h3>
                <div class="table-responsive">
                    <table class="table table-striped align-middle mb-3">
                        <thead>
                            <tr>
                                <th scope="col" class="w-10"><?php echo Text::_('COM_BREEZINGFORMSNG_ID'); ?></th>
                                <th scope="col"><?php echo Text::_('JGLOBAL_TITLE'); ?></th>
                                <th scope="col"><?php echo Text::_($isRule ? 'COM_BREEZINGFORMSNG_TOOLS_RECORDS_FORM' : 'COM_BREEZINGFORMSNG_FORMS_NAME'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($type['items'] as $item) : ?>
                                <tr>
                                    <td><?php echo (int) $item['id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($item['title'] !== '' ? $item['title'] : $item['name'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
                                    <td><?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <button
                        type="button"
                        class="btn btn-danger"
                        <?php echo !empty($this->canEmpty[$type['view']]) ? '' : ' disabled'; ?>
                        onclick="if (window.confirm(<?php echo htmlspecialchars(json_encode(Text::_('JGLOBAL_CONFIRM_DELETE'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR), ENT_QUOTES, 'UTF-8'); ?>)) { document.getElementById('bf-trash-type').value = '<?php echo $type['view']; ?>'; Joomla.submitform('about.emptyItemsTrash'); }"
                    ><span class="icon-trash" aria-hidden="true"></span> <?php echo Text::_('JTOOLBAR_EMPTY_TRASH'); ?></button>
                    <a class="btn btn-outline-secondary"
                        href="<?php echo Route::_('index.php?option=com_breezingformsng&view=' . $type['view'] . '&filter[published]=-2'); ?>">
                        <span class="icon-eye" aria-hidden="true"></span>
                        <?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_TRASH_VIEW'); ?>
                    </a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <input type="hidden" name="option" value="com_breezingformsng" />
    <input type="hidden" name="task" value="about.display" />
    <input type="hidden" name="view" value="about" />
    <input type="hidden" name="layout" value="trash" />
    <input type="hidden" name="trash_type" id="bf-trash-type" value="" />
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
