<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

\defined('_JEXEC') or die;

/** @var \Vcmb\Component\BreezingformsNG\Administrator\View\About\HtmlView $this */
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$plugins = $this->plugins;
?>
<div class="card">
    <div class="card-header">
        <div>
            <h2 class="h5 mb-1"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_EXTENSIONS_TITLE'); ?></h2>
            <p class="text-muted mb-0"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_EXTENSIONS_DESC'); ?></p>
        </div>
    </div>
    <div class="card-body">
        <?php if ($plugins === []) : ?>
            <div class="alert alert-info mb-0">
                <?php echo Text::_('COM_BREEZINGFORMSNG_PLUGINS_NOT_AVAILABLE'); ?>
            </div>
        <?php else : ?>
            <div class="table-responsive">
                <table id="bf-extensions-table" class="table table-sm table-striped align-middle mb-0">
                    <thead>
                    <tr>
                        <th scope="col"><?php echo Text::_('COM_BREEZINGFORMSNG_PLUGIN'); ?></th>
                        <th scope="col"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_EXTENSION_CATEGORY'); ?></th>
                        <th scope="col" class="text-center"><?php echo Text::_('COM_BREEZINGFORMSNG_PLUGIN_STATUS'); ?></th>
                        <th scope="col"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_EXTENSION_PURPOSE'); ?></th>
                        <th scope="col"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_EXTENSION_USAGE'); ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($plugins as $plugin) : ?>
                        <?php
                        $pluginId = (int) ($plugin['id'] ?? 0);
                        $pluginEditUrl = $pluginId > 0
                            ? Route::_('index.php?option=com_plugins&task=plugin.edit&extension_id=' . $pluginId, false)
                            : '';
                        ?>
                        <tr>
                            <th scope="row">
                                <?php if ($pluginEditUrl !== '') : ?>
                                    <a href="<?php echo htmlspecialchars($pluginEditUrl, ENT_QUOTES, 'UTF-8'); ?>">
                                        <strong><?php echo htmlspecialchars((string) ($plugin['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
                                    </a>
                                <?php else : ?>
                                    <strong><?php echo htmlspecialchars((string) ($plugin['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
                                <?php endif; ?>
                                <span class="text-muted small d-block">
                                    <?php echo htmlspecialchars((string) ($plugin['group'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                                    /
                                    <?php echo htmlspecialchars((string) ($plugin['element'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                                    <?php if ((string) ($plugin['version'] ?? '') !== '') : ?>
                                        &middot; <?php echo htmlspecialchars((string) $plugin['version'], ENT_QUOTES, 'UTF-8'); ?>
                                    <?php endif; ?>
                                </span>
                            </th>
                            <td><?php echo htmlspecialchars((string) ($plugin['category'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="text-center">
                                <?php $isEnabled = !empty($plugin['enabled']); ?>
                                <?php if ($this->canEditPluginState && $pluginId > 0) : ?>
                                    <button type="button"
                                        class="tbody-icon js-bf-form-state<?php echo $isEnabled ? ' active' : ''; ?>"
                                        data-item-task="about.setPluginState"
                                        data-item-form-id="<?php echo $pluginId; ?>"
                                        data-state-type="published"
                                        title="<?php echo Text::_($isEnabled ? 'JPUBLISHED' : 'JUNPUBLISHED'); ?>"
                                        aria-pressed="<?php echo $isEnabled ? 'true' : 'false'; ?>"
                                        aria-label="<?php echo Text::_($isEnabled ? 'JPUBLISHED' : 'JUNPUBLISHED'); ?>">
                                        <span class="<?php echo $isEnabled ? 'icon-publish' : 'icon-unpublish'; ?>" aria-hidden="true"></span>
                                    </button>
                                <?php else : ?>
                                    <span class="tbody-icon<?php echo $isEnabled ? ' active' : ''; ?>"
                                        title="<?php echo Text::_($isEnabled ? 'JPUBLISHED' : 'JUNPUBLISHED'); ?>">
                                        <span class="<?php echo $isEnabled ? 'icon-publish' : 'icon-unpublish'; ?>" aria-hidden="true"></span>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars((string) ($plugin['purpose'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string) ($plugin['usage'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
