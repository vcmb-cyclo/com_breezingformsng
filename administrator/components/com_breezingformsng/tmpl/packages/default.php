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

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

/** @var \Vcmb\Component\BreezingformsNG\Administrator\View\Packages\HtmlView $this */
/** @var \Vcmb\Component\BreezingformsNG\Administrator\View\Packages\HtmlView $view */

/** @psalm-suppress InvalidScope */
$view = $this;
$mcpActive = $view->mcpEnabled && $view->mcpRouted;
$mcpEndpoint = Uri::root() . 'api/index.php/v1/breezingformsng/mcp';
$logo = Uri::root(true) . '/media/com_breezingformsng/images/mcp/';
$logoAlt = htmlspecialchars(Text::_('COM_BREEZINGFORMSNG_MCP_STATUS_LOGO_ALT'), ENT_QUOTES, 'UTF-8');
$layoutUrl = static fn (string $layout): string => Route::_(
    'index.php?option=com_breezingformsng&view=packages&layout=' . $layout
);
$aboutUrl = static fn (string $layout): string => Route::_(
    'index.php?option=com_breezingformsng&task=about.display&view=about&layout=' . $layout
);

// Quick-access tiles: id, icon, title key, description key, target (a link, or the audit form).
$maintenanceTiles = [
    ['id' => 'bf-tools-audit', 'icon' => 'fa-wave-square', 'title' => 'COM_BREEZINGFORMSNG_ABOUT_AUDIT',
        'desc' => 'COM_BREEZINGFORMSNG_TOOLS_TILE_AUDIT_DESC', 'form' => 'bf-tools-audit-form', 'async' => true],
    ['id' => 'bf-tools-statistics', 'icon' => 'fa-chart-column', 'title' => 'COM_BREEZINGFORMSNG_TOOLS_STATISTICS_TITLE',
        'desc' => 'COM_BREEZINGFORMSNG_TOOLS_TILE_STATISTICS_DESC', 'url' => $aboutUrl('statistics')],
    ['id' => 'bf-tools-trash', 'icon' => 'fa-trash-can', 'title' => 'COM_BREEZINGFORMSNG_TOOLS_TRASH_TITLE',
        'desc' => 'COM_BREEZINGFORMSNG_TOOLS_TILE_TRASH_DESC', 'url' => $aboutUrl('trash'), 'badge' => $view->trashedTotal],
    ['id' => 'bf-tools-log', 'icon' => 'fa-file-lines', 'title' => 'COM_BREEZINGFORMSNG_TOOLS_LOG_TITLE',
        'desc' => 'COM_BREEZINGFORMSNG_TOOLS_TILE_LOG_DESC', 'url' => $aboutUrl('log')],
];
$packageTiles = [
    ['id' => 'bf-tools-install', 'layout' => 'import', 'label' => 'COM_BREEZINGFORMSNG_TOOLS_TILE_INSTALL',
        'icon' => 'fa-arrow-up-from-bracket', 'desc' => 'COM_BREEZINGFORMSNG_TOOLS_TILE_INSTALL_DESC'],
    ['id' => 'bf-tools-export', 'layout' => 'export', 'label' => 'COM_BREEZINGFORMSNG_TOOLS_TILE_EXPORT',
        'icon' => 'fa-file-export', 'desc' => 'COM_BREEZINGFORMSNG_TOOLS_TILE_EXPORT_DESC'],
    ['id' => 'bf-tools-manage', 'layout' => 'update', 'label' => 'COM_BREEZINGFORMSNG_TOOLS_TILE_MANAGE',
        'icon' => 'fa-cubes', 'desc' => 'COM_BREEZINGFORMSNG_TOOLS_TILE_MANAGE_DESC'],
];

/** One tile: a link, or a submit button of a form when the action needs a POST. */
$renderTile = static function (array $tile) use ($layoutUrl): string {
    $classes = 'bf-tile' . (!empty($tile['danger']) ? ' bf-tile--danger' : '');
    $title = htmlspecialchars(Text::_($tile['title'] ?? $tile['label']), ENT_QUOTES, 'UTF-8');
    $desc = htmlspecialchars(Text::_($tile['desc']), ENT_QUOTES, 'UTF-8');
    $badge = '';

    // Counted in the background, so that the page is not slowed down: the script fills it in.
    if (!empty($tile['async'])) {
        $badge = '<span class="bf-tile__badge badge bg-secondary" data-bf-audit-badge>'
            . '<span class="spinner-border spinner-border-sm" role="status">'
            . '<span class="visually-hidden">' . htmlspecialchars(Text::_('COM_BREEZINGFORMSNG_TOOLS_AUDIT_LOADING'), ENT_QUOTES, 'UTF-8')
            . '</span></span></span>';
    }

    if (!empty($tile['badge'])) {
        $badge = '<span class="bf-tile__badge badge bg-danger" data-trash-count="' . (int) $tile['badge'] . '">'
            . number_format((int) $tile['badge'], 0, '.', ' ') . '<span class="visually-hidden"> – '
            . htmlspecialchars(Text::plural('COM_BREEZINGFORMSNG_TOOLS_TRASH_TOTAL', (int) $tile['badge']), ENT_QUOTES, 'UTF-8')
            . '</span></span>';
    }

    $inner = $badge . '<span class="bf-tile__icon fa-solid ' . $tile['icon'] . '" aria-hidden="true"></span>'
        . '<span class="bf-tile__title">' . $title . '</span>'
        . '<span class="bf-tile__desc">' . $desc . '</span>';

    if (isset($tile['form'])) {
        return '<button type="submit" form="' . $tile['form'] . '" id="' . $tile['id'] . '" class="' . $classes . '">' . $inner . '</button>';
    }

    $url = $tile['url'] ?? $layoutUrl($tile['layout']);

    return '<a id="' . $tile['id'] . '" class="' . $classes . '" href="' . $url . '">' . $inner . '</a>';
};
?>
<div class="row g-3">
    <div class="col-lg-6 d-flex flex-column gap-3">
        <section class="card" id="bf-tools-maintenance" aria-labelledby="bf-tools-maintenance-title">
            <div class="card-body">
                <h2 class="h5" id="bf-tools-maintenance-title"><?= Text::_('COM_BREEZINGFORMSNG_TOOLS_MAINTENANCE_TITLE'); ?></h2>
                <p class="text-muted"><?= Text::_('COM_BREEZINGFORMSNG_TOOLS_MAINTENANCE_DESC'); ?></p>
                <form action="<?= Route::_('index.php?option=com_breezingformsng&task=about.runAudit'); ?>" method="post" id="bf-tools-audit-form" class="d-none"
                    data-bf-summary-url="<?= Route::_('index.php?option=com_breezingformsng&task=about.auditSummary&format=json', false); ?>">
                    <?= HTMLHelper::_('form.token'); ?>
                </form>
                <div class="bf-tiles">
                    <?php foreach ($maintenanceTiles as $tile) : ?>
                        <?= $renderTile($tile); ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="card" id="bf-tools-packages" aria-labelledby="bf-tools-packages-title">
            <div class="card-body">
                <h2 class="h5" id="bf-tools-packages-title"><?= Text::_('COM_BREEZINGFORMSNG_TOOLS_PACKAGES_TITLE'); ?></h2>
                <p class="text-muted"><?= Text::_('COM_BREEZINGFORMSNG_TOOLS_PACKAGES_DESC'); ?></p>
                <div class="bf-tiles">
                    <?php foreach ($packageTiles as $tile) : ?>
                        <?= $renderTile($tile); ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    </div>

    <div class="col-lg-6 d-flex flex-column gap-3">
        <section class="card" id="bf-mcp-status" aria-labelledby="bf-mcp-status-title">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                <h2 class="h5 mb-0" id="bf-mcp-status-title"><?= Text::_('COM_BREEZINGFORMSNG_CONFIG_MCP_FIELDSET'); ?></h2>
                <span class="d-flex align-items-center gap-2">
                    <?php if ($mcpActive) : ?>
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
                    <?php if ($view->canConfigure) : ?>
                        <a class="text-body" href="<?= Route::_('index.php?option=com_config&view=component&component=com_breezingformsng'); ?>"
                            title="<?= htmlspecialchars(Text::_('JOPTIONS'), ENT_QUOTES, 'UTF-8'); ?>">
                            <span class="icon-cogs" aria-hidden="true"></span>
                            <span class="visually-hidden"><?= Text::_('JOPTIONS'); ?></span>
                        </a>
                    <?php endif; ?>
                </span>
            </div>
            <div class="card-body">
                <img class="bf-mcp-logo bf-mcp-logo--light mb-2" src="<?= $logo; ?>light.svg" width="1338" height="195" alt="<?= $logoAlt; ?>">
                <img class="bf-mcp-logo bf-mcp-logo--dark mb-2" src="<?= $logo; ?>dark.svg" width="1338" height="195" alt="<?= $logoAlt; ?>">
                <p>
                    <?php if ($mcpActive) : ?>
                        <?= Text::_('COM_BREEZINGFORMSNG_TOOLS_MCP_ENABLED_DESC'); ?>
                    <?php elseif ($view->mcpEnabled) : ?>
                        <?= Text::_('COM_BREEZINGFORMSNG_MCP_STATUS_PLUGIN_DISABLED'); ?>
                    <?php else : ?>
                        <?= Text::_('COM_BREEZINGFORMSNG_MCP_STATUS_DISABLED_DESC'); ?>
                    <?php endif; ?>
                </p>
                <?php if ($mcpActive) : ?>
                    <p class="small fw-bold mb-1" id="bf-mcp-endpoint-label"><?= Text::_('COM_BREEZINGFORMSNG_TOOLS_MCP_ENDPOINT'); ?></p>
                    <code class="d-block p-3 mb-3 bg-body-tertiary border rounded" aria-labelledby="bf-mcp-endpoint-label"><?= htmlspecialchars($mcpEndpoint, ENT_QUOTES, 'UTF-8'); ?></code>
                <?php endif; ?>
                <p class="small text-body-secondary mb-0"><?= Text::_('COM_BREEZINGFORMSNG_MCP_STATUS_NOT_ENDORSED'); ?></p>
            </div>
        </section>

        <section class="accordion" id="bf-tools-sample" aria-labelledby="bf-tools-sample-title">
            <?php
            $selectableSamples = 0;
            foreach ($view->samples as $candidate) {
                $selectableSamples += ($candidate['installedId'] <= 0 && $view->canInstallSample && !$candidate['nameTaken']) ? 1 : 0;
            }
            ?>
            <div class="accordion-item">
                <h2 class="accordion-header" id="bf-tools-sample-title">
                    <button class="accordion-button collapsed h5 mb-0" type="button" data-bs-toggle="collapse"
                        data-bs-target="#bf-tools-sample-panel" aria-expanded="false" aria-controls="bf-tools-sample-panel">
                        <?= Text::_('COM_BREEZINGFORMSNG_TOOLS_SAMPLE_TITLE'); ?>
                    </button>
                </h2>
                <div class="accordion-collapse collapse" id="bf-tools-sample-panel">
                    <?php if ($selectableSamples > 0) : ?>
                        <div class="form-check d-flex justify-content-end gap-2 mb-0 px-3 pt-3">
                            <input class="form-check-input" type="checkbox" id="bf-tools-sample-all" name="checkall-toggle" form="bf-tools-sample-form"
                                autocomplete="off" onclick="Joomla.checkAll(this, 'bf-sample-cb-')">
                            <label class="form-check-label" for="bf-tools-sample-all">
                                <?= Text::_('COM_BREEZINGFORMSNG_TOOLS_SAMPLE_SELECT_ALL'); ?>
                                <span class="text-body-secondary small"><?= Text::_('COM_BREEZINGFORMSNG_TOOLS_SAMPLE_SELECT_ALL_HINT'); ?></span>
                            </label>
                        </div>
                    <?php endif; ?>
                    <form class="accordion-body pt-2" id="bf-tools-sample-form" method="post"
                        action="<?= Route::_('index.php?option=com_breezingformsng&task=packages.installSample'); ?>"
                        data-confirm="<?= $view->escape(Text::_('COM_BREEZINGFORMSNG_TOOLS_SAMPLE_CONFIRM')); ?>">
                        <p class="text-body-secondary small mb-3"><?= Text::_('COM_BREEZINGFORMSNG_TOOLS_SAMPLE_DESC'); ?></p>
                        <?php foreach ($view->sampleGroups as $category => $slugs) : ?>
                            <fieldset class="mb-3" data-sample-group="<?= $view->escape($category); ?>">
                                <legend class="bf-sample-group small text-uppercase text-body-secondary fw-semibold border-bottom pb-2 mb-0"><?= Text::_('COM_BREEZINGFORMSNG_TOOLS_SAMPLE_GROUP_' . strtoupper($category)); ?></legend>
                                <?php foreach ($slugs as $slug) : ?>
                                    <?php
                                    $sample = $view->samples[$slug];
                                    $key = 'COM_BREEZINGFORMSNG_TOOLS_SAMPLE_' . strtoupper($slug);
                                    $label = Text::_($key . '_ITEM');
                                    $installed = $sample['installedId'] > 0;
                                    $selectable = !$installed && $view->canInstallSample && !$sample['nameTaken'];
                                    // Joomla.checkAll only touches the ids with this prefix: an installed sample stays ticked.
                                    $inputId = ($selectable ? 'bf-sample-cb-' : 'bf-sample-installed-') . $slug;
                                    ?>
                                    <div class="bf-sample-row position-relative d-flex align-items-start gap-3 px-2 py-3 border-bottom<?= $selectable ? '' : ' is-fixed'; ?>" data-sample="<?= $view->escape($slug); ?>">
                                        <input class="form-check-input position-relative z-2 mt-1 ms-0 flex-shrink-0" type="checkbox" name="samples[]"
                                            id="<?= $view->escape($inputId); ?>" value="<?= $view->escape($slug); ?>"
                                            data-label="<?= $view->escape($label); ?>"
                                            <?= $installed ? 'checked' : ''; ?> <?= $selectable ? '' : 'disabled'; ?>>
                                        <label class="bf-sample-text flex-grow-1<?= $selectable ? ' stretched-link' : ''; ?>" for="<?= $view->escape($inputId); ?>">
                                            <span class="bf-sample-name d-block fw-medium"><?= $view->escape($label); ?>
                                                <?php if ($installed) : ?>
                                                    <span class="badge bg-success-subtle text-success-emphasis ms-1" data-tools-state="installed"><span class="icon-check" aria-hidden="true"></span> <?= Text::_('COM_BREEZINGFORMSNG_TOOLS_SAMPLE_INSTALLED'); ?></span>
                                                <?php endif; ?>
                                            </span>
                                            <span class="bf-sample-desc d-block text-body-secondary"><?= Text::_($key . '_DESC'); ?></span>
                                            <?php if ($sample['nameTaken']) : ?>
                                                <span class="d-block text-body-secondary small" data-tools-state="name-taken"><?= Text::sprintf('COM_BREEZINGFORMSNG_TOOLS_SAMPLE_ALREADY', $view->escape($label)); ?></span>
                                            <?php endif; ?>
                                        </label>
                                        <?php if ($sample['manageId'] > 0) : ?>
                                            <a class="bf-sample-form position-relative z-2 flex-shrink-0 px-2" id="bf-tools-sample-form-<?= $view->escape($slug); ?>"
                                                href="<?= Route::_('index.php?option=com_breezingformsng&task=quickmode.display&form=' . $sample['manageId']); ?>"
                                                title="<?= $view->escape(Text::_('COM_BREEZINGFORMSNG_FORMS_OPEN_EDITOR')); ?>">
                                                <?= $view->escape($sample['name']); ?>
                                            </a>
                                        <?php else : ?>
                                            <span class="bf-sample-form flex-shrink-0 px-2 text-body-secondary" id="bf-tools-sample-form-<?= $view->escape($slug); ?>">
                                                <?= $view->escape($sample['name']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </fieldset>
                        <?php endforeach; ?>
                        <?php if ($view->canInstallSample) : ?>
                            <?= HTMLHelper::_('form.token'); ?>
                            <input type="hidden" name="boxchecked" value="0">
                            <div class="position-sticky bottom-0 bg-body border-top d-flex align-items-center flex-wrap gap-3 py-3">
                                <button type="submit" class="btn btn-primary" id="bf-tools-sample-install">
                                    <span class="icon-upload" aria-hidden="true"></span>
                                    <?= Text::_('COM_BREEZINGFORMSNG_TOOLS_SAMPLE_INSTALL_SELECTION'); ?>
                                    <span id="bf-tools-sample-count"></span>
                                </button>
                                <span class="text-body-secondary small" id="bf-tools-sample-summary" aria-live="polite"><?= Text::_('COM_BREEZINGFORMSNG_TOOLS_SAMPLE_HELP_NONE'); ?></span>
                            </div>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </section>
    </div>
</div>
