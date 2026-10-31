<?php
/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/** @var int $debugMode */
/** @var int $formId */
/** @var int $published */
use Joomla\CMS\Language\Text;
?>
<div class="d-flex align-items-center gap-3 ms-auto">
    <div class="d-inline-flex align-items-center gap-2">
        <span class="fw-semibold" title="<?php echo Text::_('COM_BREEZINGFORMSNG_PUBLISH_TIP'); ?>">
            <?php echo Text::_('COM_BREEZINGFORMSNG_PUBLISHED'); ?>
        </span>
        <a href="#"
            id="bfFormPublishedToggle"
            class="js-grid-item-action tbody-icon js-bf-form-state<?php echo $published ? ' active' : ''; ?>"
            data-item-id="bfformstate<?php echo (int) $formId; ?>"
            data-item-task="forms.setPublished"
            data-item-form-id="<?php echo (int) $formId; ?>"
            data-state-type="published"
            title="<?php echo $published ? Text::_('JPUBLISHED') : Text::_('JUNPUBLISHED'); ?>">
            <span class="<?php echo $published ? 'icon-publish' : 'icon-unpublish'; ?>" aria-hidden="true"></span>
        </a>
        <input type="checkbox" name="cid[]" id="bfformstate<?php echo (int) $formId; ?>" value="<?php echo (int) $formId; ?>" hidden />
    </div>
    <div class="d-inline-flex align-items-center gap-2">
        <span class="fw-semibold" title="<?php echo Text::_('COM_BREEZINGFORMSNG_DEBUG_MODE_TIP'); ?>">
            <?php echo Text::_('COM_BREEZINGFORMSNG_DEBUG_MODE'); ?>
        </span>
        <a href="#"
            id="bfFormDebugToggle"
            class="js-grid-item-action tbody-icon js-bf-form-state<?php echo $debugMode ? ' active' : ''; ?>"
            data-item-id="bfdebugstate<?php echo (int) $formId; ?>"
            data-item-task="forms.setDebug"
            data-item-form-id="<?php echo (int) $formId; ?>"
            data-state-type="debug"
            title="<?php echo $debugMode ? Text::_('COM_BREEZINGFORMSNG_DEBUG_MODE_ENABLED') : Text::_('COM_BREEZINGFORMSNG_DEBUG_MODE_DISABLED'); ?>">
            <span class="<?php echo $debugMode ? 'fa-solid fa-bug text-success' : 'fa-solid fa-bug text-muted'; ?>" aria-hidden="true"></span>
        </a>
        <input type="checkbox" name="cid[]" id="bfdebugstate<?php echo (int) $formId; ?>" value="<?php echo (int) $formId; ?>" hidden />
    </div>
</div>
