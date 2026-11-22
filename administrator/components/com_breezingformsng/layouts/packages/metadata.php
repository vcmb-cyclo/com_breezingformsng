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

/**
 * The details of a package (name, version, title, author, e-mail, URL, description, copyright)
 * in a collapsed accordion, as the fields metadata[...] of the form that holds it.
 *
 * - values: the value of each field (an empty field when missing).
 * - tips:   a tooltip on each label, from COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_META_<FIELD>_TIP.
 *
 * @var array{values: array<string, mixed>, tips: bool} $displayData
 */

$package = Text::_('COM_BREEZINGFORMSNG_INSTALLER_PACKAGE');
$author = Text::_('COM_BREEZINGFORMSNG_INSTALLER_AUTHOR');
$fields = [
    'name' => $package . ' ' . Text::_('COM_BREEZINGFORMSNG_INSTALLER_NAME'),
    'version' => $package . ' ' . Text::_('COM_BREEZINGFORMSNG_INSTALLER_VERS'),
    'title' => $package . ' ' . Text::_('COM_BREEZINGFORMSNG_INSTALLER_TITLE'),
    'author' => $author . ' ' . Text::_('COM_BREEZINGFORMSNG_INSTALLER_NAME'),
    'email' => $author . ' ' . Text::_('COM_BREEZINGFORMSNG_INSTALLER_EMAIL'),
    'url' => $author . ' ' . Text::_('COM_BREEZINGFORMSNG_INSTALLER_URL'),
    'description' => Text::_('COM_BREEZINGFORMSNG_INSTALLER_DESC'),
    'copyright' => Text::_('COM_BREEZINGFORMSNG_INSTALLER_CPYRT'),
];
$escape = static fn (mixed $text): string => htmlspecialchars((string) ($text ?? ''), ENT_QUOTES, 'UTF-8');
?>
<table class="table table-striped mb-4" id="bf-package-info">
    <tbody><tr class="table-primary"><th>
        <a href="#" id="bf-package-info-toggle" class="bf-collapse-toggle collapsed d-block text-decoration-none text-reset"
            data-bs-toggle="collapse" data-bs-target="#bf-package-info-content"
            aria-expanded="false" aria-controls="bf-package-info-content">
            <span class="icon-chevron-down bf-collapse-icon" aria-hidden="true"></span> <?= Text::_('COM_BREEZINGFORMSNG_INSTALLER_REPORT_PACKAGEINFO'); ?>
        </a>
    </th></tr></tbody>
    <tbody id="bf-package-info-content" class="collapse"><tr><td>
        <div class="row g-3">
            <?php foreach ($fields as $field => $label) : ?>
                <div class="col-12 col-md-6 d-flex flex-column">
                    <label class="form-label" for="bf-meta-<?= $field; ?>"<?= $displayData['tips'] ? ' title="' . $escape(Text::_('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_META_' . strtoupper($field) . '_TIP')) . '"' : ''; ?>><?= $escape($label); ?></label>
                    <input class="form-control mt-auto" id="bf-meta-<?= $field; ?>" name="metadata[<?= $field; ?>]" value="<?= $escape($displayData['values'][$field] ?? ''); ?>">
                </div>
            <?php endforeach; ?>
        </div>
    </td></tr></tbody>
</table>
