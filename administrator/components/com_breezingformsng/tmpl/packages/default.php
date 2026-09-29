<?php
/** @package BreezingFormsNG */

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
?>
<div class="row g-4">
    <div class="col-12 col-xl-8">
        <div class="card">
            <div class="card-body">
                <h2 class="h4"><?= Text::_("COM_BREEZINGFORMSNG_INSTALLER_CREATEPKG"); ?></h2>
                <form action="index.php?option=com_breezingformsng" method="post">
                    <div class="row g-3 mb-4">
                        <div class="col-12 col-md-4">
                            <label class="form-label" for="bf-existing-id"><?= Text::_('COM_BREEZINGFORMSNG_INSTALLER_ID'); ?></label>
                            <select class="form-select" id="bf-existing-id" name="existing_id">
                                <option value=""></option>
                                <?php foreach ($this->profiles as $id => $profile) : ?>
                                    <option value="<?= htmlspecialchars((string) $id, ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars((string) $id, ENT_QUOTES, 'UTF-8'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-8">
                            <label class="form-label" for="bf-package"><?= Text::_('COM_BREEZINGFORMSNG_INSTALLER_PACKAGE'); ?></label>
                            <input class="form-control" id="bf-package" name="package" maxlength="30" required>
                        </div>
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
                    <div class="mt-4">
                        <button class="btn btn-primary" type="submit"><?= Text::_("COM_BREEZINGFORMSNG_INSTALLER_CREATEPKG"); ?></button>
                    </div>
                    <input type="hidden" name="task" value="packages.export">
                    <?= HTMLHelper::_("form.token"); ?>
                </form>
            </div>
        </div>
    </div>
    <div class="col-12 col-xl-4">
        <div class="card">
            <div class="card-body">
                <h2 class="h4"><?= Text::_("COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_IMPORT_TITLE"); ?></h2>
                <form action="index.php?option=com_breezingformsng" method="post" enctype="multipart/form-data">
                    <label class="form-label" for="bf-package-file"><?= Text::_("COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_FILE"); ?></label>
                    <input class="form-control mb-3" id="bf-package-file" name="package_file" type="file" accept="application/json,.json" required>
                    <button class="btn btn-primary" type="submit"><?= Text::_("COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_IMPORT"); ?></button>
                    <input type="hidden" name="task" value="packages.import">
                    <?= HTMLHelper::_("form.token"); ?>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
const bfPackageProfiles = <?= json_encode($this->profiles, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_THROW_ON_ERROR); ?>;
const bfExistingId = document.getElementById('bf-existing-id');
const bfPackage = document.getElementById('bf-package');
const bfSelections = document.getElementById('bf-selections');

bfExistingId.addEventListener('change', () => {
    const id = bfExistingId.value;
    const profile = bfPackageProfiles[id] || {};
    bfPackage.value = id;
    bfPackage.readOnly = id !== '';
    bfSelections.classList.toggle('d-none', id !== '');

    for (const field of ['name', 'version', 'title', 'author', 'email', 'url', 'description', 'copyright']) {
        document.getElementById('bf-meta-' + field).value = profile[field] || '';
    }
});

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
