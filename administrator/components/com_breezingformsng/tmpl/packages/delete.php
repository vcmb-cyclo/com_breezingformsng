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

$sections = [
    'forms' => 'COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_CONTENT_FORMS',
    'scripts' => 'COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_CONTENT_SCRIPTS',
    'pieces' => 'COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_CONTENT_PIECES',
    'menus' => 'COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_CONTENT_MENUS',
];
$hasContents = array_filter($this->contents) !== [];
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
                <p class="alert alert-info"><?= Text::_('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_DELETE_HINT'); ?></p>
                <form action="index.php" method="get" id="bf-package-delete-choice">
                    <input type="hidden" name="option" value="com_breezingformsng">
                    <input type="hidden" name="view" value="packages">
                    <input type="hidden" name="layout" value="delete">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="bf-package-delete-select"><?= Text::_('COM_BREEZINGFORMSNG_INSTALLER_PACKAGE'); ?></label>
                            <select class="form-select" id="bf-package-delete-select" name="package" onchange="this.form.submit()">
                                <option value=""><?= Text::_('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_SELECT_PACKAGE'); ?></option>
                                <?php foreach ($this->packages as $package) : ?>
                                    <option value="<?= htmlspecialchars($package, ENT_QUOTES, 'UTF-8'); ?>"<?= $package === $this->selectedPackage ? ' selected' : ''; ?>><?= htmlspecialchars($package, ENT_QUOTES, 'UTF-8'); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <noscript><button class="btn btn-secondary mt-2" type="submit"><?= Text::_('JSUBMIT'); ?></button></noscript>
                        </div>
                    </div>
                </form>

                <?php if ($this->selectedPackage !== '') : ?>
                    <p class="alert alert-warning mt-4">
                        <?= htmlspecialchars(
                            Text::sprintf('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_DELETE_CONFIRM', $this->selectedPackage),
                            ENT_QUOTES,
                            'UTF-8'
                        ); ?>
                    </p>
                    <?php if ($hasContents) : ?>
                        <p class="alert alert-warning"><?= Text::_('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_DELETE_REIMPORT'); ?></p>
                    <?php else : ?>
                        <p><?= Text::_('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_DELETE_EMPTY'); ?></p>
                    <?php endif; ?>
                    <form action="index.php?option=com_breezingformsng" method="post" id="bf-package-delete">
                    <?php foreach ($sections as $section => $label) : ?>
                        <?php if (($this->contents[$section] ?? []) !== []) : ?>
                            <h3 class="h5 mt-4"><?= Text::_($label); ?> <span class="badge bg-secondary"><?= count($this->contents[$section]); ?></span></h3>
                            <div class="table-responsive">
                                <table class="table table-sm table-striped" data-delete-section>
                                    <thead>
                                        <tr>
                                            <th scope="col" class="w-1">
                                                <input type="checkbox" class="form-check-input" data-select-all hidden
                                                    aria-label="<?= Text::_('COM_BREEZINGFORMSNG_INSTALLER_SELECTALL'); ?>">
                                            </th>
                                            <th scope="col"><?= Text::_('JGLOBAL_TITLE'); ?></th>
                                            <th scope="col"><?= Text::_('COM_BREEZINGFORMSNG_FORMS_NAME'); ?></th>
                                            <th scope="col"><?= Text::_('JPUBLISHED'); ?></th>
                                            <th scope="col"><?= Text::_('JGRID_HEADING_ID'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($this->contents[$section] as $item) : ?>
                                            <?php $title = (string) ($item['title'] ?: $item['name']); ?>
                                            <?php $editUrl = match ($section) {
                                                'forms' => 'index.php?option=com_breezingformsng&task=quickmode.display&form=' . (int) $item['id'] . '&pkg=' . rawurlencode($this->selectedPackage),
                                                'scripts' => 'index.php?option=com_breezingformsng&task=script.edit&id=' . (int) $item['id'],
                                                'pieces' => 'index.php?option=com_breezingformsng&task=piece.edit&id=' . (int) $item['id'],
                                                'menus' => 'index.php?option=com_breezingformsng&task=menu.edit&id=' . (int) $item['id'],
                                            }; ?>
                                            <tr>
                                                <td>
                                                    <input class="form-check-input" type="checkbox"
                                                        name="delete_select[<?= $section; ?>][]" value="<?= (int) $item['id']; ?>"
                                                        id="bf-delete-<?= $section; ?>-<?= (int) $item['id']; ?>" checked>
                                                </td>
                                                <td><label class="visually-hidden" for="bf-delete-<?= $section; ?>-<?= (int) $item['id']; ?>">
                                                    <?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>
                                                </label>
                                                    <a target="_blank" rel="noopener"
                                                        href="<?= htmlspecialchars($editUrl, ENT_QUOTES, 'UTF-8'); ?>"
                                                        aria-label="<?= htmlspecialchars(Text::sprintf('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_IMPORT_OPEN_EXISTING_LABEL', $title), ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></a>
                                                </td>
                                                <td><?= htmlspecialchars((string) $item['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?= Text::_((int) $item['published'] === 1 ? 'JYES' : 'JNO'); ?></td>
                                                <td><?= (int) $item['id']; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                        <input type="hidden" name="package"
                            value="<?= htmlspecialchars($this->selectedPackage, ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="task" value="packages.delete">
                        <?= HTMLHelper::_('form.token'); ?>
                    </form>
                    <table class="table table-striped mt-4" id="bf-package-info">
                        <tbody><tr class="table-primary"><th colspan="2">
                            <a href="#" id="bf-package-info-toggle" class="bf-collapse-toggle collapsed d-block text-decoration-none text-reset"
                                data-bs-toggle="collapse" data-bs-target="#bf-package-info-content"
                                aria-expanded="false" aria-controls="bf-package-info-content">
                                <span class="icon-chevron-down bf-collapse-icon" aria-hidden="true"></span> <?= Text::_('COM_BREEZINGFORMSNG_INSTALLER_REPORT_PACKAGEINFO'); ?>
                            </a>
                        </th></tr></tbody>
                        <tbody id="bf-package-info-content" class="collapse">
                            <?php foreach ($metadataFields as $field => $label) : ?>
                                <tr><th class="w-25"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></th><td><?= $auditText($profile[$field] ?? ''); ?></td></tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
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
            </div>
        </div>
    </div>
</div>
<script>
document.querySelectorAll('[data-delete-section]').forEach((table) => {
    const all = table.querySelector('[data-select-all]');
    const boxes = [...table.querySelectorAll('tbody input[type="checkbox"]')];
    const sync = () => {
        const checked = boxes.filter((box) => box.checked).length;
        all.checked = checked === boxes.length;
        all.indeterminate = checked > 0 && checked < boxes.length;
    };
    all.hidden = false;
    all.addEventListener('change', () => {
        boxes.forEach((box) => { box.checked = all.checked; });
        sync();
    });
    boxes.forEach((box) => box.addEventListener('change', sync));
    sync();
});
</script>
