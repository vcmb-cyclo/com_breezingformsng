<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/** @var \Vcmb\Component\BreezingformsNG\Administrator\View\Packages\HtmlView $this */

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Vcmb\Component\BreezingformsNG\Administrator\Model\PackageTransferModel;

$sections = [
    'forms' => 'COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_CONTENT_FORMS',
    'scripts' => 'COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_CONTENT_SCRIPTS',
    'pieces' => 'COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_CONTENT_PIECES',
    'menus' => 'COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_CONTENT_MENUS',
];
$profile = $this->profiles[$this->selectedPackage] ?? [];
$timezone = $this->timezone;
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
$auditModified = static function (mixed $date, mixed $author) use ($auditDate, $auditText): string {
    $dateText = $auditDate($date);
    $authorText = $auditText($author);

    return $dateText !== '' && $authorText !== ''
        ? Text::sprintf('COM_BREEZINGFORMSNG_AUDIT_DATE_BY', $dateText, $authorText)
        : $dateText . $authorText;
};
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
?>
<div class="row g-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <p class="alert alert-info"><?= Text::_('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_MANAGE_HINT'); ?></p>
                <form action="index.php" method="get" id="bf-package-update-choice">
                    <input type="hidden" name="option" value="com_breezingformsng">
                    <input type="hidden" name="view" value="packages">
                    <input type="hidden" name="layout" value="update">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="bf-package-update-select"><?= Text::_('COM_BREEZINGFORMSNG_INSTALLER_PACKAGE'); ?></label>
                            <select class="form-select" id="bf-package-update-select" name="package" onchange="this.form.submit()">
                                <option value=""><?= Text::_('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_SELECT_PACKAGE'); ?></option>
                                <option value="<?= PackageTransferModel::NEW_PACKAGE; ?>"<?= $this->isNewPackage ? ' selected' : ''; ?>><?= Text::_('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_NEW_PACKAGE'); ?></option>
                                <?php foreach ($this->packages as $package) : ?>
                                    <option value="<?= htmlspecialchars($package, ENT_QUOTES, 'UTF-8'); ?>"<?= $package === $this->selectedPackage ? ' selected' : ''; ?>><?= htmlspecialchars($package, ENT_QUOTES, 'UTF-8'); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <noscript><button class="btn btn-secondary mt-2" type="submit"><?= Text::_('JSUBMIT'); ?></button></noscript>
                        </div>
                    </div>
                </form>

                <?php if ($this->selectedPackage !== '' || $this->isNewPackage) : ?>
                    <?php $existing = $this->selectedPackage !== ''; ?>
                    <form action="index.php?option=com_breezingformsng" method="post" id="bf-package-update" class="mt-4">
                        <?php if ($this->isNewPackage) : ?>
                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-6">
                                    <label class="form-label" for="bf-package"><?= Text::_('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_NEW_PACKAGE_ID'); ?><span class="star" aria-hidden="true">&#160;*</span></label>
                                    <input class="form-control" id="bf-package" name="package" maxlength="30" value="<?= htmlspecialchars((string) ($this->draft['package'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" required>
                                    <input type="hidden" name="package_new" value="1">
                                </div>
                            </div>
                        <?php else : ?>
                            <input type="hidden" name="package" value="<?= htmlspecialchars($this->selectedPackage, ENT_QUOTES, 'UTF-8'); ?>">
                        <?php endif; ?>
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
                                    <?php foreach ($metadataFields as $field => $label) : ?>
                                        <div class="col-12 col-md-6 d-flex flex-column">
                                            <label class="form-label" for="bf-meta-<?= $field; ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></label>
                                            <input class="form-control mt-auto" id="bf-meta-<?= $field; ?>" name="metadata[<?= $field; ?>]" value="<?= $auditText($this->draft['metadata'][$field] ?? $profile[$field] ?? ''); ?>">
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </td></tr></tbody>
                        </table>
                        <p class="alert alert-info"><?= Text::_('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_UPDATE_HINT'); ?></p>
                        <div class="row g-4" id="bf-package-sections">
                            <?php foreach ($sections as $section => $label) : ?>
                                <?php
                                $members = [];
                                $free = [];
                                foreach ($this->choices[$section] ?? [] as $item) {
                                    if ($existing && (string) $item['package'] === $this->selectedPackage) {
                                        $members[] = $item;
                                    } elseif ((string) $item['package'] === '') {
                                        $free[] = $item;
                                    }
                                }
                                $rows = array_merge($members, $free);
                                $memberIds = array_map(static fn (array $item): int => (int) $item['id'], $members);
                                ?>
                                <div class="col-12 col-lg-6">
                                    <fieldset class="card h-100<?= $rows === [] ? ' opacity-50' : ''; ?>" data-bf-section="<?= $section; ?>">
                                        <legend class="card-header d-flex flex-wrap align-items-center gap-2 float-none w-100 mb-0 fs-5">
                                            <?= Text::_($label); ?>
                                            <span class="badge bg-secondary" data-bf-count></span>
                                            <span class="small text-body-secondary" data-bf-delta></span>
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
                                                        aria-label="<?= Text::_('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_UPDATE_FILTER'); ?>"
                                                        placeholder="<?= Text::_('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_UPDATE_FILTER'); ?>">
                                                </div>
                                            <?php endif; ?>
                                            <div class="list-group list-group-flush overflow-auto bf-package-list">
                                                <?php foreach ($rows as $item) : ?>
                                                    <?php
                                                    $id = (int) $item['id'];
                                                    $title = (string) ($item['title'] ?: $item['name']);
                                                    $rules = (int) ($item['rules'] ?? 0);
                                                    $saved = in_array($id, $memberIds, true);
                                                    $checked = isset($this->draft['select'][$section]) ? in_array($id, $this->draft['select'][$section], true) : $saved;
                                                    ?>
                                                    <label class="list-group-item d-flex align-items-center gap-2 py-2 bf-package-row<?= $checked ? ' row-selected' : ''; ?>" data-bf-row>
                                                        <input class="form-check-input m-0 flex-shrink-0" type="checkbox"
                                                            name="update_select[<?= $section; ?>][]" value="<?= $id; ?>"
                                                            data-bf-saved="<?= $saved ? '1' : '0'; ?>"<?= $checked ? ' checked' : ''; ?>>
                                                        <span class="flex-grow-1 text-break" data-bf-title><?= htmlspecialchars($title . ($rules > 0 ? ' (' . Text::sprintf('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_INTEGRATOR_RULES_COUNT', $rules) . ')' : ''), ENT_QUOTES, 'UTF-8'); ?></span>
                                                        <span class="badge bg-success d-none" data-bf-flag="attach"><?= Text::_('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_UPDATE_TO_ATTACH'); ?></span>
                                                        <span class="badge bg-warning text-dark d-none" data-bf-flag="detach"><?= Text::_('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_UPDATE_TO_DETACH'); ?></span>
                                                    </label>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </fieldset>
                                    <input type="hidden" name="update_parts[]" value="<?= $section; ?>">
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="position-sticky bottom-0 bg-body border-top d-flex align-items-center flex-wrap gap-3 py-3 mt-4">
                            <span class="small text-body-secondary" id="bf-package-summary" aria-live="polite"><?= Text::_('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_UPDATE_NO_CHANGES'); ?></span>
                            <button class="btn btn-sm btn-secondary" type="button" id="bf-package-reset" hidden><?= Text::_('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_UPDATE_RESET'); ?></button>
                        </div>
                        <input type="hidden" name="task" value="packages.update">
                        <?= HTMLHelper::_('form.token'); ?>
                    </form>
                    <?php if ($existing) : ?>
                        <form action="index.php?option=com_breezingformsng" method="post" id="bf-package-remove" class="mt-4">
                            <input type="hidden" name="package" value="<?= htmlspecialchars($this->selectedPackage, ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="task" value="packages.remove">
                            <?= HTMLHelper::_('form.token'); ?>
                        </form>

                        <table class="table table-striped mt-4" id="bf-package-audit">
                            <tbody><tr class="table-primary"><th colspan="2">
                                <a href="#" id="bf-package-audit-toggle" class="bf-collapse-toggle collapsed d-block text-decoration-none text-reset"
                                    data-bs-toggle="collapse" data-bs-target="#bf-package-audit-content"
                                    aria-expanded="false" aria-controls="bf-package-audit-content">
                                    <span class="icon-chevron-down bf-collapse-icon" aria-hidden="true"></span> <?= Text::_('COM_BREEZINGFORMSNG_RECORDS_AUDITINFO'); ?>
                                </a>
                            </th></tr></tbody>
                                <tbody id="bf-package-audit-content" class="collapse">
                                    <tr><th class="w-25"><?= Text::_('COM_BREEZINGFORMSNG_TEST_CREATED'); ?></th><td><?= $auditModified($profile['created'] ?? null, $profile['created_by'] ?? ''); ?></td></tr>
                                    <tr><th><?= Text::_('COM_BREEZINGFORMSNG_FORMS_MODIFIED'); ?></th><td><?= $auditModified($profile['modified'] ?? null, $profile['modified_by'] ?? ''); ?></td></tr>
                                </tbody>
                        </table>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
