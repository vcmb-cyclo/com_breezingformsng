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
                        <th scope="col"><?php echo Text::_('COM_BREEZINGFORMSNG_PLUGIN_STATUS'); ?></th>
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
                            <td>
                                <span class="badge <?php echo !empty($plugin['enabled']) ? 'bg-success' : 'bg-secondary'; ?>">
                                    <?php echo Text::_(!empty($plugin['enabled']) ? 'COM_BREEZINGFORMSNG_PUBLISHED' : 'COM_BREEZINGFORMSNG_UNPUBLISHED'); ?>
                                </span>
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
