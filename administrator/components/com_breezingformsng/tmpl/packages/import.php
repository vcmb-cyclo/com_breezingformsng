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

?>
<div class="row g-4">
    <div class="col-12 col-xl-6">
        <div class="card">
            <div class="card-body">
                <form action="index.php?option=com_breezingformsng" method="post" enctype="multipart/form-data" id="bf-package-import">
                    <label class="form-label" for="bf-package-file"><?= Text::_("COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_FILE"); ?></label>
                    <input class="form-control" id="bf-package-file" name="package_file" type="file" accept="application/json,.json" required>
                    <input type="hidden" name="task" value="packages.import">
                    <?= HTMLHelper::_("form.token"); ?>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
// The import asks for confirmation first, telling what the file holds and
// what it replaces. Without JavaScript, the form imports directly.
const bfImportForm = document.getElementById('bf-package-import');
let bfImportConfirmed = false;

const bfImportLines = (file, payload) => {
    const text = (key, ...values) => values.reduce(
        (message, value, index) => message.replace('%' + (index + 1) + '$s', String(value)).replace('%s', String(value)),
        Joomla.Text._(key)
    );
    const lines = [text('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_IMPORT_CONFIRM_FILE', file.name)];

    if (payload && typeof payload === 'object') {
        const count = (key) => (Array.isArray(payload[key]) ? payload[key].length : 0);
        lines.push(text('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_IMPORT_CONFIRM_CONTENT', payload.package ?? '', count('forms'), count('scripts'), count('pieces'), count('menus')));
        lines.push(Joomla.Text._('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_IMPORT_CONFIRM_REPLACE'));
        if (count('templates') > 0) {
            lines.push(text('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_IMPORT_CONFIRM_TEMPLATES', count('templates')));
        }
        if (count('integrator') > 0) {
            lines.push(text('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_IMPORT_CONFIRM_INTEGRATOR', count('integrator')));
        }
    }

    // Names come from the file: they are shown as text only.
    const content = document.createElement('div');
    for (const line of lines) {
        content.appendChild(document.createElement('p')).textContent = line;
    }

    return content;
};

bfImportForm.addEventListener('submit', (event) => {
    const file = document.getElementById('bf-package-file').files[0];

    if (bfImportConfirmed || !file) {
        return;
    }

    event.preventDefault();
    file.text()
        .then((source) => {
            try {
                return JSON.parse(source);
            } catch {
                // The server tells why the file is not a package.
                return null;
            }
        })
        .then((payload) => import('joomla.dialog').then((module) => module.default.confirm(bfImportLines(file, payload))))
        .then((confirmed) => {
            if (confirmed) {
                bfImportConfirmed = true;
                bfImportForm.submit();
            }
        });
});
</script>
