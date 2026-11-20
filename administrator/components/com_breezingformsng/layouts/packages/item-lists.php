<?php

/**
 * BreezingForms NG - A Joomla Forms Application
 *
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 *
 * SPDX-License-Identifier: GPL-2.0-or-later
 **/

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Vcmb\Component\BreezingformsNG\Administrator\Service\PackageListRows;

/**
 * The checkbox lists of the package screens, one per kind of item, driven by
 * media/com_breezingformsng/js/admin/package-update.js: a click changes one row only, "All" and
 * "None" act on the visible rows, a long list has a filter, an empty one is dimmed.
 *
 * - id:        id of the row holding the lists (the script finds it by data-bf-lists).
 * - fieldName: name of the checkboxes, with the kind in place of %s (update_select[%s][], %s[]).
 * - rows:      the rows by kind, from PackageListRows.
 * - tracked:   the screen compares with the saved state: rows say "to attach" or "to detach".
 * - showPackage: each row names the package of its item (export, where every item is listed).
 * - tips:      a tooltip on each list, from COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_<KIND>_TIP.
 *
 * @var array{id: string, fieldName: string, rows: array<string, list<array<string, mixed>>>, tracked: bool, showPackage: bool, tips: bool} $displayData
 */

$labels = [
    'forms' => 'COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_CONTENT_FORMS',
    'scripts' => 'COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_CONTENT_SCRIPTS',
    'pieces' => 'COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_CONTENT_PIECES',
    'menus' => 'COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_CONTENT_MENUS',
];
$escape = static fn (string $text): string => htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
$filterLabel = Text::_('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_UPDATE_FILTER');
?>
<div class="row g-4" id="<?= $escape($displayData['id']); ?>" data-bf-lists>
    <?php foreach (PackageListRows::SECTIONS as $section) : ?>
        <?php $rows = $displayData['rows'][$section] ?? []; ?>
        <div class="col-12 col-lg-6">
            <fieldset class="card h-100<?= $rows === [] ? ' opacity-50' : ''; ?>" data-bf-section="<?= $section; ?>"<?= $displayData['tips'] ? ' title="' . $escape(Text::_('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_' . strtoupper($section) . '_TIP')) . '"' : ''; ?>>
                <legend class="card-header d-flex flex-wrap align-items-center gap-2 float-none w-100 mb-0 fs-5">
                    <?= Text::_($labels[$section]); ?>
                    <span class="badge bg-secondary" data-bf-count></span>
                    <?php if ($displayData['tracked']) : ?>
                        <span class="small text-body-secondary" data-bf-delta></span>
                    <?php endif; ?>
                    <?php if ($rows !== []) : ?>
                        <span class="ms-auto">
                            <button class="btn btn-sm btn-link" type="button" data-bf-all><?= Text::_('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_UPDATE_ALL'); ?></button>
                            <button class="btn btn-sm btn-link" type="button" data-bf-none><?= Text::_('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_UPDATE_NONE'); ?></button>
                        </span>
                    <?php endif; ?>
                </legend>
                <?php if ($rows === []) : ?>
                    <p class="card-body fst-italic text-body-secondary mb-0"><?= Text::_('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_UPDATE_NO_ITEMS'); ?></p>
                <?php else : ?>
                    <?php if (count($rows) > 15) : ?>
                        <div class="card-body border-bottom py-2">
                            <input type="search" class="form-control form-control-sm" data-bf-filter
                                aria-label="<?= $escape($filterLabel); ?>" placeholder="<?= $escape($filterLabel); ?>">
                        </div>
                    <?php endif; ?>
                    <div class="list-group list-group-flush overflow-auto bf-package-list">
                        <?php foreach ($rows as $row) : ?>
                            <?php
                            $shown = $row['title'] . ($row['rules'] > 0 ? ' (' . Text::sprintf('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_INTEGRATOR_RULES_COUNT', $row['rules']) . ')' : '');
                            ?>
                            <label class="list-group-item d-flex align-items-center gap-2 py-2 bf-package-row<?= $row['checked'] ? ' row-selected' : ''; ?>" data-bf-row
                                data-bf-search="<?= $escape($row['package'] . ' ' . $shown); ?>">
                                <input class="form-check-input m-0 flex-shrink-0" type="checkbox"
                                    name="<?= $escape(sprintf($displayData['fieldName'], $section)); ?>" value="<?= $row['id']; ?>"
                                    data-bf-package="<?= $escape($row['package']); ?>"<?= $displayData['tracked'] ? ' data-bf-saved="' . ($row['saved'] ? '1' : '0') . '"' : ''; ?><?= $row['checked'] ? ' checked' : ''; ?>>
                                <span class="flex-grow-1 text-break" data-bf-title><?= $escape($shown); ?></span>
                                <?php if ($displayData['tracked']) : ?>
                                    <span class="badge bg-success d-none" data-bf-flag="attach"><?= Text::_('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_UPDATE_TO_ATTACH'); ?></span>
                                    <span class="badge bg-warning text-dark d-none" data-bf-flag="detach"><?= Text::_('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_UPDATE_TO_DETACH'); ?></span>
                                <?php endif; ?>
                                <?php if ($displayData['showPackage']) : ?>
                                    <span class="badge bg-light text-dark border"><?= $escape($row['package'] !== '' ? $row['package'] : Text::_('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_UPDATE_GROUP_FREE')); ?></span>
                                <?php endif; ?>
                                <a class="btn btn-sm btn-link p-0 flex-shrink-0" href="<?= $escape($row['url']); ?>" target="_blank" rel="noopener"
                                    aria-label="<?= $escape(Text::sprintf('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_IMPORT_OPEN_EXISTING_LABEL', $row['title'])); ?>"><span class="icon-external-link" aria-hidden="true"></span></a>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </fieldset>
        </div>
    <?php endforeach; ?>
</div>
