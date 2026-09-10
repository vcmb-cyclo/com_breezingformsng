<?php
/** @package BreezingFormsNG */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
?>
<div class="row g-4">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-body">
                <h2 class="h4"><?= Text::_('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_EXPORT_TITLE'); ?></h2>
                <p><?= Text::_('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_EXPORT_DESC'); ?></p>
                <form action="index.php?option=com_breezingformsng" method="post">
                    <label class="form-label" for="bf-package"><?= Text::_('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_PACKAGE'); ?></label>
                    <select class="form-select mb-3" id="bf-package" name="package" required>
                        <option value=""><?= Text::_('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_SELECT_PACKAGE'); ?></option>
                        <?php foreach ($this->packages as $package) : ?>
                            <option value="<?= htmlspecialchars($package, ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($package, ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="btn btn-primary" type="submit"><?= Text::_('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_EXPORT'); ?></button>
                    <input type="hidden" name="task" value="packages.export">
                    <?= HTMLHelper::_('form.token'); ?>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-body">
                <h2 class="h4"><?= Text::_('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_IMPORT_TITLE'); ?></h2>
                <p><?= Text::_('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_IMPORT_DESC'); ?></p>
                <form action="index.php?option=com_breezingformsng" method="post" enctype="multipart/form-data">
                    <label class="form-label" for="bf-package-file"><?= Text::_('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_FILE'); ?></label>
                    <input class="form-control mb-3" id="bf-package-file" name="package_file" type="file" accept="application/json,.json" required>
                    <button class="btn btn-primary" type="submit"><?= Text::_('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_IMPORT'); ?></button>
                    <input type="hidden" name="task" value="packages.import">
                    <?= HTMLHelper::_('form.token'); ?>
                </form>
            </div>
        </div>
    </div>
</div>
