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
use Joomla\CMS\Layout\LayoutHelper;


// Each field explains itself in the tooltip of its label.
$labelTip = static fn (string $key): string => ' title="' . htmlspecialchars(Text::_($key), ENT_QUOTES, 'UTF-8') . '"';
$mayTransferTemplates = $this->mayTransferTemplates;

?>
<div class="row g-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <form action="index.php?option=com_breezingformsng" method="post" id="bf-package-export">
                    <p class="alert alert-info"><?= Text::_('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_EXPORT_HINT'); ?></p>
                    <div class="row g-3 mb-4">
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="bf-package-choice"<?= $labelTip('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_PACKAGE_CHOICE_TIP'); ?>><?= Text::_('COM_BREEZINGFORMSNG_INSTALLER_PACKAGE'); ?></label>
                            <select class="form-select" id="bf-package-choice" name="selected_package">
                                <option value=""><?= Text::_('JGLOBAL_SELECT_AN_OPTION'); ?></option>
                                <option value="" data-new><?= Text::_('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_NEW_PACKAGE_EXPORT'); ?></option>
                                <?php foreach ($this->profiles as $id => $profile) : ?>
                                    <option value="<?= htmlspecialchars((string) $id, ENT_QUOTES, 'UTF-8'); ?>"<?= (string) $id === $this->selectedPackage ? ' selected' : ''; ?>><?= htmlspecialchars((string) $id, ENT_QUOTES, 'UTF-8'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-6 d-none" id="bf-package-new">
                            <label class="form-label" for="bf-package"<?= $labelTip('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_PACKAGE_ID_TIP'); ?>><?= Text::_('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_NEW_PACKAGE_ID'); ?><span class="star" aria-hidden="true">&#160;*</span></label>
                            <input class="form-control" id="bf-package" name="package" maxlength="30" required value="<?= htmlspecialchars($this->selectedPackage, ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                    </div>
                    <div<?= $this->selectedPackage === '' ? ' class="d-none"' : ''; ?> id="bf-package-details">
                        <?= LayoutHelper::render('packages.metadata', ['values' => [], 'tips' => true]); ?>
                        <?= LayoutHelper::render('packages.item-lists', ['id' => 'bf-selections', 'fieldName' => '%s[]', 'rows' => $this->listRows, 'tracked' => false, 'showPackage' => true, 'tips' => true]); ?>
                        <fieldset class="mt-4" id="bf-package-options">
                            <legend class="form-label fs-6"><?= Text::_('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_EXPORT_OPTIONS'); ?></legend>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="bf-include-templates" name="include_templates" value="1"<?= $mayTransferTemplates ? '' : ' disabled'; ?>>
                                <label class="form-check-label" for="bf-include-templates"<?= $mayTransferTemplates
                                    ? $labelTip('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_INCLUDE_TEMPLATES_TIP')
                                    : ' title="' . htmlspecialchars(Text::sprintf('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_INCLUDE_TEMPLATES_DENIED', Text::_('JACTION_ADMIN')), ENT_QUOTES, 'UTF-8') . '"'; ?>><?= Text::_('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_INCLUDE_TEMPLATES'); ?></label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="bf-include-integrator" name="include_integrator" value="1"<?= $this->mayTransferIntegrator ? '' : ' disabled'; ?>>
                                <label class="form-check-label" for="bf-include-integrator"<?= $labelTip($this->mayTransferIntegrator ? 'COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_INCLUDE_INTEGRATOR_TIP' : 'COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_INCLUDE_INTEGRATOR_DENIED'); ?>><?= Text::_('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_INCLUDE_INTEGRATOR'); ?></label>
                            </div>
                        </fieldset>
                        <?= LayoutHelper::render('packages.audit', ['profiles' => $this->profiles, 'selected' => $this->selectedPackage, 'timezone' => $this->timezone]); ?>
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
    bfSelections.classList.remove('d-none');

    if (isNew) {
        const openPackageInfo = () => {
            if ('new' in bfPackageChoice.selectedOptions[0].dataset) {
                window.bootstrap.Collapse.getOrCreateInstance(
                    document.getElementById('bf-package-info-content'), { toggle: false }
                ).show();
            }
        };
        // Joomla loads Bootstrap as a module, after this inline script.
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', openPackageInfo, { once: true });
        } else {
            openPackageInfo();
        }
    }

    // The audit trail of an existing package, as on the record screen.
    let hasAudit = false;
    for (const row of document.querySelectorAll('#bf-package-audit [data-bf-audit]')) {
        const shown = !isNew && row.dataset.bfAudit === id;
        row.classList.toggle('d-none', !shown);
        hasAudit = hasAudit || shown;
    }
    document.getElementById('bf-package-audit').classList.toggle('d-none', !hasAudit);

    if (assigned) {
        for (const box of bfSelections.querySelectorAll('input[type="checkbox"]')) {
            box.checked = box.dataset.bfPackage === id;
        }
        // The lists (package-update.js) refresh their counters on this event.
        for (const section of bfSelections.querySelectorAll('[data-bf-section]')) {
            section.dispatchEvent(new Event('change', { bubbles: true }));
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
    // The package list already holds the new choice when its change event runs.
    params.delete('selected_package');
    // The options stay as they are when another package is chosen.
    params.delete('include_templates');
    params.delete('include_integrator');
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
</script>
