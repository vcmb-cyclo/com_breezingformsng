<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

\defined('_JEXEC') or die;

/** @var \Vcmb\Component\BreezingformsNG\Administrator\View\About\HtmlView $this */
use Joomla\CMS\HTML\HTMLHelper;
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
            <form id="bf-extensions-form" method="post"
                action="<?php echo Route::_('index.php?option=com_breezingformsng&task=about.uninstallExtension'); ?>">
            <div class="table-responsive">
                <table id="bf-extensions-table" class="table table-sm table-striped align-middle mb-0">
                    <thead>
                    <tr>
                        <th scope="col" class="w-1 text-center"><?php echo Text::_('JGRID_HEADING_ID'); ?></th>
                        <th scope="col"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_EXTENSION_NAME'); ?></th>
                        <th scope="col"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_EXTENSION_TYPE'); ?></th>
                        <th scope="col"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_EXTENSION_CATEGORY'); ?></th>
                        <th scope="col" class="text-center"><?php echo Text::_('COM_BREEZINGFORMSNG_PLUGIN_STATUS'); ?></th>
                        <th scope="col"><?php echo Text::_('JGLOBAL_DESCRIPTION'); ?></th>
                        <th scope="col"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_EXTENSION_CHANGELOG'); ?></th>
                        <?php if ($this->canUninstallExtension) : ?>
                            <th scope="col"><span class="visually-hidden"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_EXTENSION_UNINSTALL'); ?></span></th>
                        <?php endif; ?>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($plugins as $plugin) : ?>
                        <?php
                        $pluginId = (int) ($plugin['id'] ?? 0);
                        $isPlugin = ($plugin['type'] ?? 'plugin') === 'plugin';
                        $pluginEditUrl = $isPlugin && $pluginId > 0
                            ? Route::_('index.php?option=com_plugins&task=plugin.edit&extension_id=' . $pluginId, false)
                            : '';
                        ?>
                        <tr>
                            <td class="text-center"><?php echo $pluginId; ?></td>
                            <th scope="row">
                                <?php if ($pluginEditUrl !== '') : ?>
                                    <a href="<?php echo htmlspecialchars($pluginEditUrl, ENT_QUOTES, 'UTF-8'); ?>">
                                        <strong><?php echo htmlspecialchars((string) ($plugin['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
                                    </a>
                                <?php else : ?>
                                    <strong><?php echo htmlspecialchars((string) ($plugin['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
                                <?php endif; ?>
                                <span class="text-muted small d-block">
                                    <?php echo $isPlugin ? htmlspecialchars((string) ($plugin['group'] ?? ''), ENT_QUOTES, 'UTF-8') . ' /' : ''; ?>
                                    <?php echo htmlspecialchars((string) ($plugin['element'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                                    <?php if ((string) ($plugin['version'] ?? '') !== '') : ?>
                                        &middot; <?php echo htmlspecialchars((string) $plugin['version'], ENT_QUOTES, 'UTF-8'); ?>
                                    <?php endif; ?>
                                </span>
                            </th>
                            <td><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_EXTENSION_TYPE_' . strtoupper((string) ($plugin['type'] ?? 'plugin'))); ?></td>
                            <td><?php echo htmlspecialchars((string) ($plugin['category'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="text-center">
                                <?php $isEnabled = !empty($plugin['enabled']); ?>
                                <?php if ($this->canEditPluginState && $isPlugin && $pluginId > 0) : ?>
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
                            <td><?php echo htmlspecialchars((string) ($plugin['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <?php if (($plugin['changelogUrl'] ?? '') !== '') : ?>
                                    <a href="<?php echo htmlspecialchars((string) $plugin['changelogUrl'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">
                                        <span class="icon-out-2" aria-hidden="true"></span>
                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_EXTENSION_CHANGELOG'); ?>
                                    </a>
                                <?php endif; ?>
                            </td>
                            <?php if ($this->canUninstallExtension) : ?>
                                <td class="text-end">
                                    <?php if (!empty($plugin['uninstallable'])) : ?>
                                        <button type="submit"
                                            class="btn btn-sm btn-outline-danger"
                                            name="extension_id"
                                            value="<?php echo $pluginId; ?>"
                                            title="<?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_EXTENSION_UNINSTALL'); ?>"
                                            data-bf-submit-confirm="<?php echo htmlspecialchars(Text::sprintf('COM_BREEZINGFORMSNG_ABOUT_EXTENSION_UNINSTALL_CONFIRM', (string) ($plugin['name'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>">
                                            <span class="icon-trash" aria-hidden="true"></span>
                                            <span class="visually-hidden"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_EXTENSION_UNINSTALL'); ?></span>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php echo HTMLHelper::_('form.token'); ?>
            </form>
        <?php endif; ?>
    </div>
</div>
