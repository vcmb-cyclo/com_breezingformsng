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

defined("_JEXEC") or die;

/** @var \Vcmb\Component\BreezingformsNG\Administrator\View\Packages\HtmlView $this */

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

$sections = [
    "forms" => "COM_BREEZINGFORMSNG_INSTALLER_FORMSEL",
    "scripts" => "COM_BREEZINGFORMSNG_INSTALLER_SCRIPTSEL",
    "pieces" => "COM_BREEZINGFORMSNG_INSTALLER_PIECESEL",
    "menus" => "COM_BREEZINGFORMSNG_INSTALLER_MENUSEL",
];

$metadataFields = [
    'name' => Text::_('COM_BREEZINGFORMSNG_INSTALLER_PACKAGE') . ' ' . Text::_('COM_BREEZINGFORMSNG_INSTALLER_NAME'),
    'version' => Text::_('COM_BREEZINGFORMSNG_INSTALLER_PACKAGE') . ' ' . Text::_('COM_BREEZINGFORMSNG_INSTALLER_VERS'),
    'title' => Text::_('COM_BREEZINGFORMSNG_INSTALLER_PACKAGE') . ' ' . Text::_('COM_BREEZINGFORMSNG_INSTALLER_TITLE'),
    'author' => Text::_('COM_BREEZINGFORMSNG_INSTALLER_AUTHOR') . ' ' . Text::_('COM_BREEZINGFORMSNG_INSTALLER_NAME'),
    'email' => Text::_('COM_BREEZINGFORMSNG_INSTALLER_AUTHOR') . ' ' . Text::_('COM_BREEZINGFORMSNG_INSTALLER_EMAIL'),
    'url' => Text::_('COM_BREEZINGFORMSNG_INSTALLER_AUTHOR') . ' ' . Text::_('COM_BREEZINGFORMSNG_INSTALLER_URL'),
    'description' => Text::_('COM_BREEZINGFORMSNG_INSTALLER_DESC'),
    'copyright' => Text::_('COM_BREEZINGFORMSNG_INSTALLER_CPYRT'),
];
$timezone = $this->timezone;
// Same date display as the record screen: local time, time zone in the tooltip.
$auditDate = static fn (mixed $utc): string => $utc === null || $utc === '' ? '' : sprintf(
    '<span title="%s">%s</span>',
    htmlspecialchars(
        Text::sprintf('COM_BREEZINGFORMSNG_TIMEZONE_WITH_OFFSET', $timezone->getName(), HTMLHelper::_('date', (string) $utc, 'P')),
        ENT_QUOTES,
        'UTF-8'
    ),
    HTMLHelper::_('date', (string) $utc, Text::_('DATE_FORMAT_LC6')),
);
$auditText = static fn (mixed $text): string => htmlspecialchars((string) ($text ?? ''), ENT_QUOTES, 'UTF-8');
?>
<div class="row g-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <form action="index.php?option=com_breezingformsng" method="post" id="bf-package-export">
                    <p class="alert alert-info"><?= Text::_('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_EXPORT_HINT'); ?></p>
                    <div class="row g-3 mb-4">
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="bf-package-choice"><?= Text::_('COM_BREEZINGFORMSNG_INSTALLER_PACKAGE'); ?></label>
                            <select class="form-select" id="bf-package-choice" name="selected_package">
                                <option value=""><?= Text::_('JGLOBAL_SELECT_AN_OPTION'); ?></option>
                                <option value="" data-new><?= Text::_('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_NEW_PACKAGE'); ?></option>
                                <?php foreach ($this->profiles as $id => $profile) : ?>
                                    <option value="<?= htmlspecialchars((string) $id, ENT_QUOTES, 'UTF-8'); ?>"<?= (string) $id === $this->selectedPackage ? ' selected' : ''; ?>><?= htmlspecialchars((string) $id, ENT_QUOTES, 'UTF-8'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-6 d-none" id="bf-package-new">
                            <label class="form-label" for="bf-package"><?= Text::_('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_NEW_PACKAGE_ID'); ?><span class="star" aria-hidden="true">&#160;*</span></label>
                            <input class="form-control" id="bf-package" name="package" maxlength="30" required value="<?= htmlspecialchars($this->selectedPackage, ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                    </div>
                    <div<?= $this->selectedPackage === '' ? ' class="d-none"' : ''; ?> id="bf-package-details">
                        <div class="row g-3 mb-4">
                            <?php foreach ($metadataFields as $field => $label) : ?>
                                <div class="col-12 col-md-6">
                                    <label class="form-label" for="bf-meta-<?= $field; ?>"><?= $label; ?></label>
                                    <input class="form-control" id="bf-meta-<?= $field; ?>" name="metadata[<?= $field; ?>]" value="">
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="row g-4" id="bf-selections">
                            <?php foreach ($sections as $section => $label) : ?>
                                <div class="col-12 col-lg-6">
                                    <label class="form-label" for="bf-<?= $section; ?>"><?= Text::_($label); ?></label>
                                    <select class="form-select" id="bf-<?= $section; ?>" name="<?= $section; ?>[]" multiple size="14">
                                        <?php foreach ($this->choices[$section] as $item) : ?>
                                            <?php $name = (string) ($item["title"] ?: $item["name"]); ?>
                                            <option value="<?= (int) $item["id"]; ?>"><?= htmlspecialchars((string) $item["package"] . " :: " . $name, ENT_QUOTES, "UTF-8"); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="mt-2">
                                        <button class="btn btn-sm btn-secondary" data-select-all="bf-<?= $section; ?>" type="button"><?= Text::_("COM_BREEZINGFORMSNG_INSTALLER_SELECTALL"); ?></button>
                                        <button class="btn btn-sm btn-secondary" data-clear-selection="bf-<?= $section; ?>" type="button"><?= Text::_("COM_BREEZINGFORMSNG_INSTALLER_CLRSELECTION"); ?></button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="card mt-4 d-none" id="bf-package-audit">
                            <table class="table mb-0">
                                <thead>
                                    <tr class="table-primary"><th colspan="2"><?= Text::_('COM_BREEZINGFORMSNG_RECORDS_AUDITINFO'); ?></th></tr>
                                </thead>
                                <?php foreach ($this->profiles as $id => $profile) : ?>
                                    <tbody class="d-none" data-bf-audit="<?= htmlspecialchars((string) $id, ENT_QUOTES, 'UTF-8'); ?>">
                                        <tr><th class="w-25"><?= Text::_('JGLOBAL_FIELD_CREATED_LABEL'); ?></th><td><?= $auditDate($profile['created'] ?? null); ?></td></tr>
                                        <tr><th><?= Text::_('JGLOBAL_FIELD_CREATED_BY_LABEL'); ?></th><td><?= $auditText($profile['created_by'] ?? ''); ?></td></tr>
                                        <tr><th><?= Text::_('JGLOBAL_FIELD_MODIFIED_LABEL'); ?></th><td><?= $auditDate($profile['modified'] ?? null); ?></td></tr>
                                        <tr><th><?= Text::_('JGLOBAL_FIELD_MODIFIED_BY_LABEL'); ?></th><td><?= $auditText($profile['modified_by'] ?? ''); ?></td></tr>
                                    </tbody>
                                <?php endforeach; ?>
                            </table>
                        </div>
                    </div>
                    <input type="hidden" name="task" value="packages.export">
                    <?= HTMLHelper::_("form.token"); ?>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
const bfPackageProfiles = <?= json_encode($this->profiles, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_THROW_ON_ERROR); ?>;
const bfPackage = document.getElementById('bf-package');
const bfSelections = document.getElementById('bf-selections');

const bfPackageChoice = document.getElementById('bf-package-choice');
const bfPackageNew = document.getElementById('bf-package-new');

const bfPackageDetails = document.getElementById('bf-package-details');

// Nothing shows until the list picks a package. An existing one fills in its
// details, and one with assigned items exports them, so its selection is
// hidden and cleared. "New package" shows the ID field.
const bfShowProfile = () => {
    const isNew = 'new' in bfPackageChoice.selectedOptions[0].dataset;
    const id = bfPackageChoice.value;
    const profile = bfPackageProfiles[id] || {};
    const assigned = Boolean(profile.assigned);
    bfPackageDetails.classList.toggle('d-none', id === '' && !isNew);
    bfPackageNew.classList.toggle('d-none', !isNew);
    bfPackage.value = isNew && !bfPackageProfiles[bfPackage.value] ? bfPackage.value : id;
    bfSelections.classList.toggle('d-none', assigned);

    // The audit trail of an existing package, as on the record screen.
    let hasAudit = false;
    for (const body of document.querySelectorAll('#bf-package-audit tbody')) {
        const shown = !isNew && body.dataset.bfAudit === id;
        body.classList.toggle('d-none', !shown);
        hasAudit = hasAudit || shown;
    }
    document.getElementById('bf-package-audit').classList.toggle('d-none', !hasAudit);

    if (assigned) {
        for (const option of bfSelections.querySelectorAll('option')) {
            option.selected = false;
        }
    }

    for (const field of ['name', 'version', 'title', 'author', 'email', 'url', 'description', 'copyright']) {
        document.getElementById('bf-meta-' + field).value = profile[field] || '';
    }
};

// Unsaved changes guard, as on the record screen (admin-form-dirty.js): the
// form is compared with its last loaded or submitted state, without `task`.
const bfExportForm = document.getElementById('bf-package-export');
const bfFormState = () => {
    const params = new URLSearchParams(new FormData(bfExportForm));
    params.delete('task');
    return params.toString();
};
const bfConfirmDiscard = () => import('joomla.dialog')
    .then((module) => module.default.confirm(Joomla.Text._('COM_BREEZINGFORMSNG_CONFIRM_DISCARD_CHANGES')));
let bfSavedState = '';
let bfChoice = 0;
let bfSubmitting = false;

const bfLoadProfile = () => {
    bfShowProfile();
    bfChoice = bfPackageChoice.selectedIndex;
    bfSavedState = bfFormState();
};

bfPackageChoice.addEventListener('change', () => {
    if (bfFormState() === bfSavedState) {
        bfLoadProfile();
        return;
    }

    const wanted = bfPackageChoice.selectedIndex;
    bfPackageChoice.selectedIndex = bfChoice;
    bfConfirmDiscard().then((confirmed) => {
        if (confirmed) {
            bfPackageChoice.selectedIndex = wanted;
            bfLoadProfile();
        }
    });
});

document.getElementById('toolbar-close')?.addEventListener('click', (event) => {
    if (bfFormState() === bfSavedState) {
        return;
    }

    event.preventDefault();
    const href = event.currentTarget.href;
    bfConfirmDiscard().then((confirmed) => {
        if (confirmed) {
            bfSubmitting = true;
            window.location.href = href;
        }
    });
});

// Save and Export package both store the form. Save reloads the screen;
// Export package downloads the file and leaves the page open, so the stored
// details are copied into the in-memory profile (a new package joins the
// list), and the guard comes back after a while.
bfExportForm.addEventListener('submit', () => {
    bfSubmitting = true;

    if (bfExportForm.elements.task.value === 'packages.export') {
        const id = bfPackage.value.trim();
        const profile = bfPackageProfiles[id] || { id };

        for (const field of ['name', 'version', 'title', 'author', 'email', 'url', 'description', 'copyright']) {
            profile[field] = document.getElementById('bf-meta-' + field).value;
        }
        bfPackageProfiles[id] = profile;

        // After the browser has built the submitted data.
        window.setTimeout(() => {
            if (![...bfPackageChoice.options].some((option) => !('new' in option.dataset) && option.value === id)) {
                bfPackageChoice.add(new Option(id, id));
            }
            bfPackageChoice.value = id;
            bfLoadProfile();
        });
    }

    bfSavedState = bfFormState();
    window.setTimeout(() => { bfSubmitting = false; }, 10000);
});

window.addEventListener('beforeunload', (event) => {
    if (!bfSubmitting && bfFormState() !== bfSavedState) {
        event.preventDefault();
        event.returnValue = '';
    }
});

bfLoadProfile();

document.addEventListener('click', (event) => {
    const button = event.target.closest('button[data-select-all], button[data-clear-selection]');
    if (!button) {
        return;
    }

    const select = document.getElementById(button.dataset.selectAll || button.dataset.clearSelection);
    for (const option of select.options) {
        option.selected = Boolean(button.dataset.selectAll);
    }
});
</script>
