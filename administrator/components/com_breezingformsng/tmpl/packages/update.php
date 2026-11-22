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
use Joomla\CMS\Layout\LayoutHelper;
use Vcmb\Component\BreezingformsNG\Administrator\Service\PackageListRows;

$profile = $this->profiles[$this->selectedPackage] ?? [];
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
                                <?php foreach ($this->packages as $package) : ?>
                                    <option value="<?= htmlspecialchars($package, ENT_QUOTES, 'UTF-8'); ?>"<?= $package === $this->selectedPackage ? ' selected' : ''; ?>><?= htmlspecialchars($package, ENT_QUOTES, 'UTF-8'); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <noscript><button class="btn btn-secondary mt-2" type="submit"><?= Text::_('JSUBMIT'); ?></button></noscript>
                            <a class="btn btn-secondary mt-2" href="index.php?option=com_breezingformsng&amp;view=packages&amp;layout=update&amp;package_new=1"><?= Text::_('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_NEW_PACKAGE'); ?></a>
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
                        <?= LayoutHelper::render('packages.metadata', ['values' => $this->draft['metadata'] ?? $profile, 'tips' => false]); ?>
                        <p class="alert alert-info"><?= Text::_('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_UPDATE_HINT'); ?></p>
                        <?= LayoutHelper::render('packages.item-lists', ['id' => 'bf-package-sections', 'fieldName' => 'update_select[%s][]', 'rows' => $this->listRows, 'tracked' => true, 'showPackage' => false, 'tips' => false]); ?>
                        <?php foreach (PackageListRows::SECTIONS as $section) : ?>
                            <input type="hidden" name="update_parts[]" value="<?= $section; ?>">
                        <?php endforeach; ?>
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

                        <?= LayoutHelper::render('packages.audit', ['profiles' => [$this->selectedPackage => $profile], 'selected' => $this->selectedPackage, 'timezone' => $this->timezone]); ?>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
