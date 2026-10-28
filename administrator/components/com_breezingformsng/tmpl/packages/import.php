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
