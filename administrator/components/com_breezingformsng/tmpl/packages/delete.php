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
                                <option value=""><?= Text::_('JGLOBAL_SELECT_AN_OPTION'); ?></option>
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
                    <?php foreach ($sections as $section => $label) : ?>
                        <?php if (($this->contents[$section] ?? []) !== []) : ?>
                            <h3 class="h5 mt-4"><?= Text::_($label); ?> <span class="badge bg-secondary"><?= count($this->contents[$section]); ?></span></h3>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th scope="col"><?= Text::_('JGLOBAL_TITLE'); ?></th>
                                            <th scope="col"><?= Text::_('COM_BREEZINGFORMSNG_FORMS_NAME'); ?></th>
                                            <th scope="col"><?= Text::_('JPUBLISHED'); ?></th>
                                            <th scope="col"><?= Text::_('JGRID_HEADING_ID'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($this->contents[$section] as $item) : ?>
                                            <tr>
                                                <td><?= htmlspecialchars((string) $item['title'], ENT_QUOTES, 'UTF-8'); ?></td>
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
                    <form action="index.php?option=com_breezingformsng" method="post" id="bf-package-delete">
                        <input type="hidden" name="package"
                            value="<?= htmlspecialchars($this->selectedPackage, ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="task" value="packages.delete">
                        <?= HTMLHelper::_('form.token'); ?>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
