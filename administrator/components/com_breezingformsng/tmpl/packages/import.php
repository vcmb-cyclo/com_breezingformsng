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
use Joomla\CMS\Router\Route;

$escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$reportPackage = (string) ($this->importReport['package'] ?? '');
$reportItems = (array) ($this->importReport['items'] ?? []);
// Each kind of item: its heading and the page that edits one of them.
$reportKinds = [
    'forms' => ['COM_BREEZINGFORMSNG_MANAGEFORMS', static fn (int $id): string => 'index.php?option=com_breezingformsng&task=quickmode.display&form=' . $id . '&pkg=' . rawurlencode($reportPackage)],
    'scripts' => ['COM_BREEZINGFORMSNG_MANAGESCRIPTS', static fn (int $id): string => 'index.php?option=com_breezingformsng&task=script.edit&id=' . $id],
    'pieces' => ['COM_BREEZINGFORMSNG_MANAGEPIECES', static fn (int $id): string => 'index.php?option=com_breezingformsng&task=piece.edit&id=' . $id],
    'menus' => ['COM_BREEZINGFORMSNG_MANAGEMENUS', static fn (int $id): string => 'index.php?option=com_breezingformsng&task=menu.edit&id=' . $id],
];
$hasReport = array_filter($reportItems) !== [];
?>
<div class="row g-4">
    <?php if ($hasReport) : ?>
        <div class="col-12">
            <div class="card" id="bf-package-import-report">
                <div class="card-body">
                    <h2 class="h4"><?= $escape(Text::sprintf('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_IMPORT_REPORT', $reportPackage)); ?></h2>
                    <?php foreach ($reportKinds as $kind => [$label, $editUrl]) : ?>
                        <?php if (($reportItems[$kind] ?? []) !== []) : ?>
                            <h3 class="h5 mt-4"><?= Text::_($label); ?> <span class="badge bg-secondary"><?= count($reportItems[$kind]); ?></span></h3>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th scope="col"><?= Text::_('JGLOBAL_TITLE'); ?></th>
                                            <th scope="col"><?= Text::_('COM_BREEZINGFORMSNG_FORMS_NAME'); ?></th>
                                            <th scope="col"><?= Text::_('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_IMPORT_REPORT_ACTION'); ?></th>
                                            <?php if ($kind === 'forms') : ?>
                                                <th scope="col"><?= Text::_('COM_BREEZINGFORMSNG_ELEMENTS'); ?></th>
                                            <?php endif; ?>
                                            <th scope="col"><?= Text::_('JGRID_HEADING_ID'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($reportItems[$kind] as $item) : ?>
                                            <?php $label = (string) ($item['title'] ?? '') !== '' ? (string) $item['title'] : (string) ($item['name'] ?? ''); ?>
                                            <tr>
                                                <td><a href="<?= $escape(Route::_($editUrl((int) ($item['id'] ?? 0)), false)); ?>"><?= $escape($label); ?></a></td>
                                                <td><?= $escape($item['name'] ?? ''); ?></td>
                                                <td>
                                                    <?php if (($item['action'] ?? '') === 'replaced') : ?>
                                                        <span class="badge bg-warning text-dark"><?= Text::_('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_IMPORT_REPORT_REPLACED'); ?></span>
                                                    <?php elseif (($item['action'] ?? '') === 'identical') : ?>
                                                        <span class="badge bg-secondary"><?= Text::_('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_IMPORT_REPORT_IDENTICAL'); ?></span>
                                                    <?php else : ?>
                                                        <span class="badge bg-success"><?= Text::_('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_IMPORT_REPORT_CREATED'); ?></span>
                                                    <?php endif; ?>
                                                    <?php if (!empty($item['required'])) : ?>
                                                        <span class="badge bg-info text-dark"><?= Text::_('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_IMPORT_REPORT_REQUIRED'); ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <?php if ($kind === 'forms') : ?>
                                                    <td><?= (int) ($item['elements'] ?? 0); ?></td>
                                                <?php endif; ?>
                                                <td><?= (int) ($item['id'] ?? 0); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>

                    <?php if (($reportItems['rules'] ?? []) !== []) : ?>
                        <h3 class="h5 mt-4"><?= Text::_('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_IMPORT_REPORT_RULES'); ?> <span class="badge bg-secondary"><?= count($reportItems['rules']); ?></span></h3>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th scope="col"><?= Text::_('COM_BREEZINGFORMSNG_INTEGRATOR_RULENAME'); ?></th>
                                        <th scope="col"><?= Text::_('COM_BREEZINGFORMSNG_FORM'); ?></th>
                                        <th scope="col"><?= Text::_('JGRID_HEADING_ID'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($reportItems['rules'] as $rule) : ?>
                                        <tr>
                                            <td><a href="<?= $escape(Route::_('index.php?option=com_breezingformsng&view=integrator&layout=edit&id=' . (int) ($rule['id'] ?? 0), false)); ?>"><?= $escape(($rule['name'] ?? '') !== '' ? $rule['name'] : '#' . (int) ($rule['id'] ?? 0)); ?></a></td>
                                            <td><?= $escape($rule['form'] ?? ''); ?></td>
                                            <td><?= (int) ($rule['id'] ?? 0); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>

                    <?php if (($reportItems['templates'] ?? []) !== []) : ?>
                        <h3 class="h5 mt-4"><?= Text::_('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_IMPORT_REPORT_TEMPLATES'); ?> <span class="badge bg-secondary"><?= count($reportItems['templates']); ?></span></h3>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th scope="col"><?= Text::_('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_IMPORT_REPORT_FILE'); ?></th>
                                        <th scope="col"><?= Text::_('COM_BREEZINGFORMSNG_FORM'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($reportItems['templates'] as $template) : ?>
                                        <tr>
                                            <td><code><?= $escape($template['path'] ?? ''); ?></code></td>
                                            <td><?= $escape($template['form'] ?? ''); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <form action="index.php?option=com_breezingformsng" method="post" enctype="multipart/form-data" id="bf-package-import" data-may-templates="<?= $this->mayTransferTemplates ? '1' : '0'; ?>" data-may-integrator="<?= $this->mayTransferIntegrator ? '1' : '0'; ?>">
                    <div class="col-12 col-xl-6">
                        <label class="form-label" for="bf-package-file"><?= Text::_("COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_FILE"); ?></label>
                        <input class="form-control" id="bf-package-file" name="package_file" type="file" accept="application/json,.json,application/xml,text/xml,.xml" required aria-describedby="bf-package-file-formats">
                        <div class="form-text" id="bf-package-file-formats"><?= Text::_('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_IMPORT_FORMATS'); ?></div>
                    </div>
                    <!-- Filled from the chosen file: one table per kind of item, to choose what to import. -->
                    <div id="bf-package-selection" class="mt-4" hidden>
                        <h2 class="h4"><?= Text::_('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_IMPORT_SELECT'); ?></h2>
                        <p class="text-body-secondary"><?= Text::_('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_IMPORT_SELECT_HINT'); ?></p>
                        <div data-tables></div>
                    </div>
                    <input type="hidden" name="task" value="packages.import">
                    <?= HTMLHelper::_("form.token"); ?>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
// The import asks for confirmation first, telling what the file holds and
// what it replaces. Once a file is chosen, its items are listed by kind, with
// what the import would do with each, to leave some out. Without JavaScript,
// the form imports the whole file directly.
const bfImportForm = document.getElementById('bf-package-import');
const bfImportSelection = document.getElementById('bf-package-selection');
const bfImportParts = {
    forms: 'COM_BREEZINGFORMSNG_MANAGEFORMS',
    scripts: 'COM_BREEZINGFORMSNG_MANAGESCRIPTS',
    pieces: 'COM_BREEZINGFORMSNG_MANAGEPIECES',
    menus: 'COM_BREEZINGFORMSNG_MANAGEMENUS',
};
const bfImportStatusBadges = {
    new: ['bg-success', 'COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_IMPORT_STATUS_NEW'],
    replace: ['bg-warning text-dark', 'COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_IMPORT_STATUS_REPLACE'],
    identical: ['bg-secondary', 'COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_IMPORT_STATUS_IDENTICAL'],
};
// The page that edits an item of the site, by part.
const bfImportEditUrls = {
    forms: (id, payload) => `index.php?option=com_breezingformsng&task=quickmode.display&form=${id}&pkg=${encodeURIComponent(String(payload.package ?? ''))}`,
    scripts: (id) => `index.php?option=com_breezingformsng&task=script.edit&id=${id}`,
    pieces: (id) => `index.php?option=com_breezingformsng&task=piece.edit&id=${id}`,
    menus: (id) => `index.php?option=com_breezingformsng&task=menu.edit&id=${id}`,
};
let bfImportConfirmed = false;
// What the server says the import would do with each item, by part; null when unknown.
let bfImportStatuses = null;
// The id of the item of the site each item would replace, by part; 0 for a new one.
let bfImportIds = null;
// The items of the file the tables list, as the server read them; null when unknown.
let bfImportPayload = null;
let bfImportReading = 0;

// One pass, with a function: names from the file are never read as
// placeholders or as replacement patterns such as $&.
const bfImportText = (key, ...values) => {
    let next = 0;
    return Joomla.Text._(key).replace(/%(?:(\d+)\$)?[sd]/g, (match, position) => {
        const index = position === undefined ? next++ : Number(position) - 1;
        return index < values.length ? String(values[index]) : match;
    });
};

// A count, in the singular or plural form of the page language: the key ending
// in _1 is the singular, the key itself the plural.
const bfImportPluralRules = new Intl.PluralRules(document.documentElement.lang || undefined);
const bfImportCount = (key, count) => {
    const singular = bfImportPluralRules.select(count) === 'one' && Joomla.Text._(`${key}_1`, '') !== '';
    return bfImportText(singular ? `${key}_1` : key, count);
};

const bfImportElementCount = (row) => {
    if (row && Number.isInteger(row.element_count)) {
        return row.element_count;
    }
    return Array.isArray(row && row.elements) ? row.elements.length : 0;
};

const bfImportRows = (payload, part) => (payload && Array.isArray(payload[part]) ? payload[part] : []);
const bfImportStatus = (part, position) => (bfImportStatuses && Array.isArray(bfImportStatuses[part]) ? bfImportStatuses[part][position] : undefined);

// The positions chosen in the tables, by part; null when no table is shown.
const bfImportChosen = () => {
    if (!bfImportSelection || bfImportSelection.hidden) {
        return null;
    }
    const chosen = {};
    for (const part of Object.keys(bfImportParts)) {
        chosen[part] = new Set([...bfImportForm.querySelectorAll(`input[name="import_select[${part}][]"]:checked`)].map((box) => Number(box.value)));
    }
    return chosen;
};

const bfImportLines = (file, payload, chosen = null) => {
    const lines = [bfImportText('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_IMPORT_CONFIRM_FILE', file.name)];

    if (payload && typeof payload === 'object') {
        const positions = (part) => bfImportRows(payload, part).map((row, position) => position).filter((position) => !chosen || chosen[part].has(position));
        const kept = (part) => positions(part).map((position) => bfImportRows(payload, part)[position]);
        const formSources = new Set(kept('forms').map((form) => form && form.source_id));
        // Templates and rules come with the forms chosen.
        const ofChosenForms = (part) => bfImportRows(payload, part).filter((entry) => !chosen || formSources.has(entry && entry.form_source_id)).length;
        const count = (part) => bfImportCount(`COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_IMPORT_CONFIRM_${part.toUpperCase()}`, kept(part).length);
        lines.push(bfImportText('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_IMPORT_CONFIRM_CONTENT', payload.package ?? '', count('forms'), count('scripts'), count('pieces'), count('menus')));
        if (bfImportStatuses) {
            let created = 0;
            let replaced = 0;
            for (const part of Object.keys(bfImportParts)) {
                for (const position of positions(part)) {
                    const status = bfImportStatus(part, position);
                    created += status === 'new' ? 1 : 0;
                    replaced += status === 'replace' ? 1 : 0;
                }
            }
            lines.push(bfImportText(
                'COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_IMPORT_CONFIRM_STATUS',
                bfImportCount('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_IMPORT_CONFIRM_CREATED', created),
                bfImportCount('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_IMPORT_CONFIRM_REPLACED', replaced)
            ));
        } else {
            lines.push(Joomla.Text._('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_IMPORT_CONFIRM_REPLACE'));
        }
        // What the user may not write is left out by the server: say it beforehand.
        const may = (part) => bfImportForm.dataset[part] === '1';
        if (ofChosenForms('templates') > 0) {
            lines.push(bfImportCount(may('mayTemplates') ? 'COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_IMPORT_CONFIRM_TEMPLATES' : 'COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_IMPORT_CONFIRM_TEMPLATES_SKIPPED', ofChosenForms('templates')));
        }
        if (ofChosenForms('integrator') > 0) {
            lines.push(bfImportCount(may('mayIntegrator') ? 'COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_IMPORT_CONFIRM_INTEGRATOR' : 'COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_IMPORT_CONFIRM_INTEGRATOR_SKIPPED', ofChosenForms('integrator')));
        }
    }

    // Names come from the file: they are shown as text only.
    const content = document.createElement('div');
    for (const line of lines) {
        content.appendChild(document.createElement('p')).textContent = line;
    }

    // JoomlaDialog restores HTMLElement content to its original parent on
    // close. Return escaped markup so a detached element needs no restoration.
    return content.innerHTML;
};

const bfImportRead = (file) => file.text().then((source) => {
    try {
        return JSON.parse(source);
    } catch {
        // The server tells why the file is not a package.
        return null;
    }
});

// Asks the server what the import would do with each item; nothing is written.
// Without an answer, the tables are shown without it.
const bfImportPreview = () => {
    const data = new FormData(bfImportForm);
    data.set('task', 'packages.preview');
    return fetch(bfImportForm.action, { method: 'POST', body: data, credentials: 'same-origin' })
        .then((response) => response.json())
        .then((json) => (json && json.success && json.data && typeof json.data.statuses === 'object' ? json.data : null))
        .catch(() => null);
};

// One table per kind of item of the file. Every item is checked, but those the
// site already has as they are: there is nothing to import for them. Names from
// the file are written as text only.
const bfImportShowSelection = (payload) => {
    if (!bfImportSelection) {
        return;
    }
    const tables = bfImportSelection.querySelector('[data-tables]');
    tables.replaceChildren();
    bfImportForm.querySelector('input[name="import_selected"]')?.remove();
    const parts = Object.keys(bfImportParts).filter((part) => bfImportRows(payload, part).length > 0);
    bfImportSelection.hidden = parts.length === 0;
    if (parts.length === 0) {
        return;
    }

    const flag = document.createElement('input');
    flag.type = 'hidden';
    flag.name = 'import_selected';
    flag.value = '1';
    bfImportForm.appendChild(flag);

    let selectable = 0;
    for (const part of parts) {
        const rows = bfImportRows(payload, part);
        const heading = tables.appendChild(document.createElement('h3'));
        heading.className = 'h5 mt-3';
        heading.textContent = `${Joomla.Text._(bfImportParts[part])} `;
        const badge = heading.appendChild(document.createElement('span'));
        badge.className = 'badge bg-secondary';
        badge.textContent = String(rows.length);

        const wrapper = tables.appendChild(document.createElement('div'));
        wrapper.className = 'table-responsive';
        const table = wrapper.appendChild(document.createElement('table'));
        table.className = 'table table-sm table-striped';
        const head = table.createTHead().insertRow();
        const allCell = head.appendChild(document.createElement('th'));
        allCell.scope = 'col';
        allCell.className = 'w-1';
        const all = allCell.appendChild(document.createElement('input'));
        all.type = 'checkbox';
        all.className = 'form-check-input';
        all.setAttribute('aria-label', Joomla.Text._('COM_BREEZINGFORMSNG_INSTALLER_SELECTALL'));
        const columns = ['JGLOBAL_TITLE', 'COM_BREEZINGFORMSNG_FORMS_NAME'];
        if (part === 'forms') {
            columns.push('COM_BREEZINGFORMSNG_ELEMENTS');
        }
        if (bfImportStatuses) {
            columns.push('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_IMPORT_STATUS');
        }
        for (const key of columns) {
            const cell = head.appendChild(document.createElement('th'));
            cell.scope = 'col';
            cell.textContent = Joomla.Text._(key);
        }

        const body = table.createTBody();
        const boxes = [];
        rows.forEach((row, position) => {
            const data = row && typeof row.data === 'object' && row.data !== null ? row.data : {};
            const status = bfImportStatus(part, position);
            const identical = status === 'identical';
            const line = body.insertRow();
            const box = line.insertCell().appendChild(document.createElement('input'));
            box.type = 'checkbox';
            box.className = 'form-check-input';
            box.name = `import_select[${part}][]`;
            box.value = String(position);
            box.id = `bf-import-${part}-${position}`;
            box.checked = !identical;
            box.disabled = identical;
            if (identical) {
                line.classList.add('text-body-secondary');
            } else {
                boxes.push(box);
            }
            const titleCell = line.insertCell();
            const title = titleCell.appendChild(document.createElement('label'));
            title.htmlFor = box.id;
            title.textContent = String(data.title ?? '') || String(data.name ?? '');
            // The item of the site it would replace opens in a new tab: the chosen file stays here.
            const id = bfImportIds && Array.isArray(bfImportIds[part]) ? Number(bfImportIds[part][position]) : 0;
            if (Number.isInteger(id) && id > 0) {
                const link = titleCell.appendChild(document.createElement('a'));
                link.href = bfImportEditUrls[part](id, payload);
                link.target = '_blank';
                link.rel = 'noopener';
                link.className = 'ms-2 small';
                link.textContent = Joomla.Text._('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_IMPORT_OPEN_EXISTING');
                link.setAttribute('aria-label', bfImportText('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_IMPORT_OPEN_EXISTING_LABEL', title.textContent));
            }
            line.insertCell().textContent = String(data.name ?? '');
            if (part === 'forms') {
                line.insertCell().textContent = String(bfImportElementCount(row));
            }
            if (bfImportStatuses) {
                const cell = line.insertCell();
                const [classes, key] = bfImportStatusBadges[status] ?? bfImportStatusBadges.new;
                const label = cell.appendChild(document.createElement('span'));
                label.className = `badge ${classes}`;
                label.textContent = Joomla.Text._(key);
                if (identical) {
                    const hint = cell.appendChild(document.createElement('div'));
                    hint.className = 'small';
                    hint.id = `${box.id}-hint`;
                    hint.textContent = Joomla.Text._('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_IMPORT_STATUS_IDENTICAL_HINT');
                    box.setAttribute('aria-describedby', hint.id);
                }
            }
        });
        selectable += boxes.length;

        const sync = () => {
            const checked = boxes.filter((box) => box.checked).length;
            all.checked = boxes.length > 0 && checked === boxes.length;
            all.indeterminate = checked > 0 && checked < boxes.length;
        };
        all.disabled = boxes.length === 0;
        all.addEventListener('change', () => {
            boxes.forEach((box) => { box.checked = all.checked; });
            sync();
        });
        boxes.forEach((box) => box.addEventListener('change', sync));
        sync();
    }

    if (selectable === 0) {
        const notice = tables.appendChild(document.createElement('div'));
        notice.className = 'alert alert-info mt-3';
        notice.dataset.allIdentical = '1';
        notice.textContent = Joomla.Text._('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_IMPORT_ALL_IDENTICAL');
    }
};

// The Import toolbar button stays greyed out until a file is chosen.
const bfImportButton = document.querySelector('joomla-toolbar-button[task="packages.import"] button');
const bfImportSyncButton = () => {
    if (bfImportButton) {
        bfImportButton.disabled = document.getElementById('bf-package-file').files.length === 0;
    }
};
bfImportSyncButton();
window.addEventListener('pageshow', bfImportSyncButton);

document.getElementById('bf-package-file').addEventListener('change', (event) => {
    bfImportSyncButton();
    const file = event.target.files[0];
    const reading = ++bfImportReading;
    bfImportStatuses = null;
    bfImportIds = null;
    bfImportPayload = null;
    if (!file) {
        bfImportShowSelection(null);
        return;
    }
    bfImportRead(file)
        // An XML file of BreezingForms is read by the server only: its answer lists the items.
        .then((payload) => bfImportPreview().then((preview) => [preview && preview.payload ? preview.payload : payload, preview]))
        .then(([payload, preview]) => {
            // Only the answer for the file still chosen is shown.
            if (reading === bfImportReading) {
                bfImportStatuses = preview ? preview.statuses : null;
                bfImportIds = preview && preview.ids && typeof preview.ids === 'object' ? preview.ids : null;
                bfImportPayload = preview && preview.payload ? preview.payload : null;
                bfImportShowSelection(payload);
            }
        }, () => bfImportShowSelection(null));
});

bfImportForm.addEventListener('submit', (event) => {
    const file = document.getElementById('bf-package-file').files[0];

    if (bfImportConfirmed || !file) {
        return;
    }

    event.preventDefault();
    const chosen = bfImportChosen();
    if (chosen && Object.values(chosen).every((positions) => positions.size === 0)) {
        const allIdentical = bfImportSelection.querySelector('[data-all-identical]') !== null;
        Joomla.renderMessages({
            [allIdentical ? 'info' : 'error']: [Joomla.Text._(allIdentical ? 'COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_IMPORT_ALL_IDENTICAL' : 'COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_IMPORT_NOTHING_SELECTED')],
        });
        return;
    }

    bfImportRead(file)
        .then((payload) => import('joomla.dialog').then((module) => module.default.confirm(bfImportLines(file, bfImportPayload ?? payload, chosen))))
        .then((confirmed) => {
            if (confirmed) {
                bfImportConfirmed = true;
                bfImportForm.submit();
            }
        }, () => {
            // The file could not be read or the dialog not opened: the import
            // goes on as without JavaScript, and the server reports any problem.
            bfImportConfirmed = true;
            bfImportForm.submit();
        });
});
</script>
