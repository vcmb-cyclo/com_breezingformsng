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

?>
<div class="row g-4">
    <div class="col-12 col-xl-6">
        <div class="card">
            <div class="card-body">
                <p class="alert alert-warning">
                    <?= htmlspecialchars(
                        Text::sprintf('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_DELETE_CONFIRM', $this->selectedPackage),
                        ENT_QUOTES,
                        'UTF-8'
                    ); ?>
                </p>
                <form action="index.php?option=com_breezingformsng" method="post" id="bf-package-delete">
                    <input type="hidden" name="package"
                        value="<?= htmlspecialchars($this->selectedPackage, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="task" value="packages.delete">
                    <?= HTMLHelper::_('form.token'); ?>
                </form>
            </div>
        </div>
    </div>
</div>
