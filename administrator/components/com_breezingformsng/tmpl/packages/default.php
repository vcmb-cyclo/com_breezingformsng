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

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

?>
<p class="alert alert-info"><?= Text::_("COM_BREEZINGFORMSNG_CONFIGURATION_INTRO"); ?></p>

<section class="card mt-3" id="bf-mcp-status" aria-labelledby="bf-mcp-status-title">
    <div class="card-body">
        <div class="d-flex flex-wrap align-items-center gap-3 mb-2">
            <h2 class="h5 mb-0" id="bf-mcp-status-title"><?= Text::_('COM_BREEZINGFORMSNG_CONFIG_MCP_FIELDSET'); ?></h2>
            <?php $logo = Uri::root(true) . '/media/com_breezingformsng/images/mcp/'; ?>
            <img class="bf-mcp-logo bf-mcp-logo--light" src="<?= $logo; ?>light.svg" width="1338" height="195"
                alt="<?= htmlspecialchars(Text::_('COM_BREEZINGFORMSNG_MCP_STATUS_LOGO_ALT'), ENT_QUOTES, 'UTF-8'); ?>">
            <img class="bf-mcp-logo bf-mcp-logo--dark" src="<?= $logo; ?>dark.svg" width="1338" height="195"
                alt="<?= htmlspecialchars(Text::_('COM_BREEZINGFORMSNG_MCP_STATUS_LOGO_ALT'), ENT_QUOTES, 'UTF-8'); ?>">
            <?php if ($this->mcpEnabled && $this->mcpRouted) : ?>
                <span class="badge bg-success" data-mcp-state="enabled">
                    <span class="icon-check" aria-hidden="true"></span>
                    <?= Text::_('COM_BREEZINGFORMSNG_MCP_STATUS_ENABLED'); ?>
                </span>
            <?php else : ?>
                <span class="badge bg-secondary" data-mcp-state="disabled">
                    <span class="icon-times" aria-hidden="true"></span>
                    <?= Text::_('COM_BREEZINGFORMSNG_MCP_STATUS_DISABLED'); ?>
                </span>
            <?php endif; ?>
        </div>
        <p class="mb-2">
            <?php if ($this->mcpEnabled && $this->mcpRouted) : ?>
                <?= Text::sprintf('COM_BREEZINGFORMSNG_MCP_STATUS_ENABLED_DESC', '<code>' . htmlspecialchars(Uri::root() . 'api/index.php/v1/breezingformsng/mcp', ENT_QUOTES, 'UTF-8') . '</code>'); ?>
            <?php elseif ($this->mcpEnabled) : ?>
                <?= Text::_('COM_BREEZINGFORMSNG_MCP_STATUS_PLUGIN_DISABLED'); ?>
            <?php else : ?>
                <?= Text::_('COM_BREEZINGFORMSNG_MCP_STATUS_DISABLED_DESC'); ?>
            <?php endif; ?>
        </p>
        <?php if ($this->canConfigure) : ?>
            <a class="btn btn-sm btn-outline-secondary" href="<?= Route::_('index.php?option=com_config&view=component&component=com_breezingformsng'); ?>">
                <span class="icon-options" aria-hidden="true"></span>
                <?= Text::_('JOPTIONS'); ?>
            </a>
        <?php endif; ?>
        <p class="small text-body-secondary mt-2 mb-0"><?= Text::_('COM_BREEZINGFORMSNG_MCP_STATUS_NOT_ENDORSED'); ?></p>
    </div>
</section>
