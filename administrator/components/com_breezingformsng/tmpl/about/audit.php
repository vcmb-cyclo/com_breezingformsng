<?php

/**
 * BreezingForms NG - A Joomla Forms Application
 *
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 *
 * Tools / Audit: the report of the database audit and its repair actions.
 */

defined('_JEXEC') or die('Direct Access to this location is not allowed.');

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Vcmb\Component\BreezingformsNG\Administrator\Service\DatabaseRepairService;
use Vcmb\Component\BreezingformsNG\Administrator\Service\ExtensionAdminLink;

/** @var \Vcmb\Component\BreezingformsNG\Administrator\View\About\HtmlView $this */

Text::script('COM_BREEZINGFORMSNG_ABOUT_AUDIT_ORPHAN_TRANSFER_SELECT_FIRST');
Text::script('JCLOSE');

HTMLHelper::_('bootstrap.collapse');
/** @psalm-suppress InvalidScope */
$auditReport = $this->auditReport;
$auditSummary = (array) ($auditReport['summary'] ?? array());
$auditStringList = static function (mixed $value): array {
    if (!is_array($value)) {
        return [];
    }

    $items = [];
    foreach ($value as $item) {
        if (is_string($item)) {
            $items[] = $item;
        }
    }

    return $items;
};
/** @return list<array<string, mixed>> */
$auditEntryList = static function (mixed $value): array {
    if (!is_array($value)) {
        return [];
    }

    $entries = [];
    foreach ($value as $entry) {
        if (is_array($entry)) {
            $entries[] = $entry;
        }
    }

    return $entries;
};
$auditMissingTables = $auditStringList($auditReport['missing_tables'] ?? array());
$auditUnexpectedTables = $auditStringList($auditReport['unexpected_tables'] ?? array());
$auditMissingColumns = $auditEntryList($auditReport['missing_columns'] ?? array());
$auditDebugForms = $auditEntryList($auditReport['debug_forms'] ?? array());
$auditTrashedRecordsTotal = (int) ($auditSummary['trashed_records'] ?? 0);
$auditStaleLanguageFiles = $auditStringList($auditReport['stale_language_files'] ?? array());
$auditStaleInstallerTempDirs = $auditStringList($auditReport['stale_installer_temp_dirs'] ?? array());
$auditCollationIssues = $auditEntryList($auditReport['collation_issues'] ?? array());
$auditColumnCollationIssues = $auditEntryList($auditReport['column_collation_issues'] ?? array());
// Orphan references: the stored name of a deleted form (or its id when no
// name was kept) links to the records list filtered on it, all states. Atum
// already marks a target="_blank" link with an icon.
$orphanLink = static fn (bool $linked, array $group, string $text): string => $linked
    ? '<a href="' . Route::_(
        'index.php?option=com_breezingformsng&view=records&filter[form]='
        . (int) ($group['parent_id'] ?? 0) . '&filter[state]=*'
    ) . '" target="_blank" rel="noopener" title="'
        . htmlspecialchars(Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_ORPHAN_SHOW_RECORDS'), ENT_QUOTES, 'UTF-8') . '">'
        . $text . '<span class="visually-hidden">'
        . Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_ORPHAN_SHOW_RECORDS') . '</span></a>'
    : $text;
$auditCollationHistogram = (array) ($auditReport['collation_histogram'] ?? array());
$auditDuplicateIndexes = $auditEntryList($auditReport['duplicate_indexes'] ?? array());
$auditMenuIssues = $auditEntryList($auditReport['menu_issues'] ?? array());
$auditDuplicateForms = $auditEntryList($auditReport['duplicate_forms'] ?? array());
$auditExtensionDuplicates = $auditEntryList($auditReport['extension_duplicates'] ?? array());
$auditHelp = static function (string $key): string {
    $text = htmlspecialchars(Text::_($key), ENT_QUOTES, 'UTF-8');

    return ' <span class="icon-info-circle text-muted ms-1" tabindex="0" role="img" title="' . $text . '" aria-label="' . $text . '"></span>';
};
$auditLegacyUrl = static fn(array $entry): string => ExtensionAdminLink::forEntry($entry);
$auditExtensionLegacy = $auditEntryList($auditReport['extension_legacy'] ?? array());
$auditErrors = $auditStringList($auditReport['errors'] ?? array());
$auditOrphanChecks = array_values(array_filter(
    $auditEntryList($auditReport['orphan_checks'] ?? array()),
    static fn(array $check): bool => (int) ($check['count'] ?? 0) > 0
));
$notAvailable = Text::_('COM_BREEZINGFORMSNG_NOT_AVAILABLE');
// The report stores its date in UTC: it is shown in the time zone of the user.
$auditGeneratedAt = $notAvailable;
if (($auditReport['generated_at'] ?? '') !== '') {
    try {
        $auditGeneratedAt = HTMLHelper::_('date', (string) $auditReport['generated_at'], 'Y-m-d H:i:s', true);
    } catch (\Throwable) {
        $auditGeneratedAt = (string) $auditReport['generated_at'];
    }
}
?>

<form action="<?php echo Route::_('index.php?option=com_breezingformsng&task=about.display&view=about&layout=audit'); ?>" method="post" name="adminForm" id="adminForm">
    <div class="card mt-3" id="bf-audit-section">
        <div class="card-body p-3 p-lg-4">
        <?php /** @psalm-suppress InvalidScope */ if (!$this->canRepair) : ?>
            <div class="alert alert-info"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_READ_ONLY_NOTICE'); ?></div>
        <?php endif; ?>
        <fieldset class="border-0 p-0 m-0"<?php echo $this->canRepair ? '' : ' disabled'; ?>>
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <h3 class="h5 mb-0"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_REPORT_TITLE'); ?></h3>
                <?php if ($auditReport !== array()) : ?>
                    <span class="text-muted small">
                        <?php echo Text::sprintf(
                            'COM_BREEZINGFORMSNG_ABOUT_AUDIT_GENERATED_AT',
                            htmlspecialchars($auditGeneratedAt, ENT_QUOTES, 'UTF-8')
                        ); ?>
                    </span>
                <?php endif; ?>
            </div>

            <?php if ($this->auditError !== '') : ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo $this->auditError; ?>
                </div>
            <?php endif; ?>

            <?php if ($auditReport === array()) : ?>
                <div class="alert alert-info mb-0">
                    <?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_EMPTY'); ?>
                </div>
            <?php else : ?>
                <?php if ((int) ($auditSummary['audit_errors'] ?? 0) > 0) : ?>
                    <div class="alert alert-danger bf-audit-error-alert">
                        <?php echo Text::plural('COM_BREEZINGFORMSNG_ABOUT_AUDIT_REPORT_ERRORS', (int) ($auditSummary['audit_errors'] ?? 0)); ?>
                    </div>
                <?php elseif ((int) ($auditSummary['issues_total'] ?? 0) === 0) : ?>
                    <div class="alert alert-success bf-audit-ok-alert">
                        <span class="bf-audit-section-title">
                            <span class="bf-audit-ok-check icon-check-circle" aria-hidden="true"></span>
                            <span><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_REPORT_CLEAN'); ?></span>
                        </span>
                    </div>
                <?php else : ?>
                    <div class="alert alert-warning bf-audit-warning-alert">
                        <?php echo Text::plural('COM_BREEZINGFORMSNG_ABOUT_AUDIT_REPORT_ISSUES', (int) ($auditSummary['issues_total'] ?? 0)); ?>
                    </div>
                <?php endif; ?>

                <?php if ($auditErrors !== array()) : ?>
                    <div class="bf-audit-section-block mb-3 border-danger">
                        <h4 class="h6 text-danger"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_ERRORS'); ?></h4>
                        <ul class="mb-0">
                            <?php foreach ($auditErrors as $error) : ?>
                                <li><code><?php echo htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8'); ?></code></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div class="table-responsive mb-3">
                    <table class="table table-sm table-striped align-middle mb-0 bf-audit-summary-table">
                        <thead>
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT'); ?></th>
                                <th scope="col"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_COUNT'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="<?php echo (int) ($auditSummary['issues_total'] ?? 0) > 0 ? 'table-warning' : ''; ?>">
                                <td class="text-muted text-end pe-2">1</td>
                                <th scope="row"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_ISSUES_TOTAL'); ?></th>
                                <td><?php echo (int) ($auditSummary['issues_total'] ?? 0); ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted text-end pe-2">2</td>
                                <th scope="row"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_TABLES'); ?></th>
                                <td><?php echo (int) ($auditSummary['scanned_tables'] ?? 0); ?> / <?php echo (int) ($auditSummary['expected_tables'] ?? 0); ?></td>
                            </tr>
                            <tr class="<?php echo (int) ($auditSummary['orphan_rows'] ?? 0) > 0 ? 'table-warning' : ''; ?>">
                                <td class="text-muted text-end pe-2">3</td>
                                <th scope="row"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_ORPHANS'); ?></th>
                                <td><?php echo (int) ($auditSummary['orphan_rows'] ?? 0); ?></td>
                            </tr>
                            <tr class="<?php echo $auditMissingTables !== array() ? 'table-warning' : ''; ?>">
                                <td class="text-muted text-end pe-2">4</td>
                                <th scope="row"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_MISSING_TABLES'); ?></th>
                                <td><?php echo count($auditMissingTables); ?></td>
                            </tr>
                            <tr class="<?php echo $auditUnexpectedTables !== array() ? 'table-warning' : ''; ?>">
                                <td class="text-muted text-end pe-2">5</td>
                                <th scope="row"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_UNEXPECTED_TABLES'); ?></th>
                                <td><?php echo count($auditUnexpectedTables); ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted text-end pe-2">6</td>
                                <th scope="row"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_ROWS'); ?></th>
                                <td><?php echo number_format((int) ($auditSummary['total_rows'] ?? 0), 0, '.', ' '); ?></td>
                            </tr>
                            <tr class="<?php echo $auditMissingColumns !== array() ? 'table-warning' : ''; ?>">
                                <td class="text-muted text-end pe-2">7</td>
                                <th scope="row"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_MISSING_COLUMNS'); ?></th>
                                <td><?php echo (int) ($auditSummary['missing_columns'] ?? 0); ?></td>
                            </tr>
                            <tr class="<?php echo $auditDebugForms !== array() ? 'table-warning' : ''; ?>">
                                <td class="text-muted text-end pe-2">8</td>
                                <th scope="row"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_DEBUG_FORMS'); ?></th>
                                <td><?php echo count($auditDebugForms); ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted text-end pe-2">9</td>
                                <th scope="row"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_TRASHED_RECORDS'); ?></th>
                                <td><?php echo $auditTrashedRecordsTotal; ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="bf-audit-detail-sections">

                <?php if ($auditMissingTables !== array()) : ?>
                    <div class="bf-audit-section-block mb-3">
                        <h4 class="h6"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_MISSING_TABLES'); ?><?php echo $auditHelp('COM_BREEZINGFORMSNG_ABOUT_AUDIT_MISSING_TABLES_HELP'); ?></h4>
                        <ul class="mb-0">
                            <?php foreach ($auditMissingTables as $table) : ?>
                                <li>
                                    <code><?php echo htmlspecialchars((string) $table, ENT_QUOTES, 'UTF-8'); ?></code>
                                    <?php if ($table === '#__breezingforms' && $this->canDeleteOrphans) : ?>
                                        <button type="button" class="btn btn-sm btn-outline-danger ms-2"
                                            onclick="if (window.confirm(<?php echo htmlspecialchars((string) json_encode(Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_LEGACY_TABLE_DELETE_CONFIRM'), JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG), ENT_QUOTES, 'UTF-8'); ?>)) { Joomla.submitform('about.deleteLegacyBreezingformsTable'); }">
                                            <span class="icon-trash" aria-hidden="true"></span>
                                            <?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_LEGACY_TABLE_DELETE'); ?>
                                        </button>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if ($auditMissingColumns !== array()) : ?>
                    <div class="bf-audit-section-block mb-3">
                        <h4 class="h6"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_MISSING_COLUMNS'); ?><?php echo $auditHelp('COM_BREEZINGFORMSNG_ABOUT_AUDIT_MISSING_COLUMNS_HELP'); ?></h4>
                        <p class="text-muted small mb-2"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_MISSING_COLUMNS_DESC'); ?></p>
                        <ul class="mb-0">
                            <?php foreach ($auditMissingColumns as $entry) : ?>
                                <li>
                                    <code><?php echo htmlspecialchars((string) ($entry['table'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></code>
                                    &mdash;
                                    <?php foreach ($auditStringList($entry['columns'] ?? array()) as $column) : ?>
                                        <code><?php echo htmlspecialchars((string) $column, ENT_QUOTES, 'UTF-8'); ?></code>
                                    <?php endforeach; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if ($auditDebugForms !== array()) : ?>
                    <div class="bf-audit-section-block mb-3">
                        <h4 class="h6"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_DEBUG_FORMS'); ?><?php echo $auditHelp('COM_BREEZINGFORMSNG_ABOUT_AUDIT_DEBUG_FORMS_HELP'); ?></h4>
                        <p class="text-muted small mb-2"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_DEBUG_FORMS_DESC'); ?></p>
                        <ul class="mb-0">
                            <?php foreach ($auditDebugForms as $form) : ?>
                                <?php $debugFormId = (int) ($form['id'] ?? 0); ?>
                                <li>
                                    <code><?php echo htmlspecialchars((string) ($form['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></code>
                                    <span class="text-muted">(#<?php echo $debugFormId; ?>)</span>
                                    <a class="btn btn-sm btn-outline-secondary ms-2" target="_blank" rel="noopener"
                                        href="index.php?option=com_breezingformsng&amp;task=quickmode.display&amp;form=<?php echo $debugFormId; ?>&amp;pkg=<?php echo rawurlencode((string) ($form['package'] ?? '')); ?>">
                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_FORM_EDIT'); ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if ($auditUnexpectedTables !== array()) : ?>
                    <div class="bf-audit-section-block mb-3">
                        <h4 class="h6"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_UNEXPECTED_TABLES'); ?><?php echo $auditHelp('COM_BREEZINGFORMSNG_ABOUT_AUDIT_UNEXPECTED_TABLES_HELP'); ?></h4>
                        <ul class="mb-0">
                            <?php foreach ($auditUnexpectedTables as $table) : ?>
                                <li><code><?php echo htmlspecialchars((string) $table, ENT_QUOTES, 'UTF-8'); ?></code></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if ($auditStaleLanguageFiles !== array()) : ?>
                    <div class="bf-audit-section-block mb-3">
                        <h4 class="h6"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_STALE_LANGUAGE_FILES'); ?><?php echo $auditHelp('COM_BREEZINGFORMSNG_ABOUT_AUDIT_STALE_LANGUAGE_FILES_HELP'); ?></h4>
                        <ul class="mb-0">
                            <?php foreach ($auditStaleLanguageFiles as $file) : ?>
                                <li><code><?php echo htmlspecialchars((string) $file, ENT_QUOTES, 'UTF-8'); ?></code></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if ($auditStaleInstallerTempDirs !== array()) : ?>
                    <div class="bf-audit-section-block mb-3">
                        <h4 class="h6"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_STALE_INSTALLER_TEMP'); ?><?php echo $auditHelp('COM_BREEZINGFORMSNG_ABOUT_AUDIT_STALE_INSTALLER_TEMP_HELP'); ?></h4>
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                            <label class="form-check mb-0">
                                <input
                                    type="checkbox"
                                    class="form-check-input"
                                    data-bf-select-all="stale-installer-temp"
                                    aria-label="<?php echo htmlspecialchars(Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_STALE_INSTALLER_TEMP_SELECT_ALL'), ENT_QUOTES, 'UTF-8'); ?>"
                                >
                                <span class="form-check-label"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_STALE_INSTALLER_TEMP_SELECT_ALL'); ?></span>
                            </label>
                            <button
                                type="submit"
                                class="btn btn-sm btn-warning"
                                onclick="document.getElementById('bf-about-task').value='about.deleteStaleInstallerTemp';"
                                title="<?php echo htmlspecialchars(Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_STALE_INSTALLER_TEMP_REPAIR_SELECTED_TIP'), ENT_QUOTES, 'UTF-8'); ?>"
                            >
                                <span class="icon-trash me-1" aria-hidden="true"></span><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_STALE_INSTALLER_TEMP_REPAIR_SELECTED'); ?>
                            </button>
                        </div>
                        <ul class="mb-0">
                            <?php foreach ($auditStaleInstallerTempDirs as $index => $dir) : ?>
                                <li>
                                    <input
                                        type="checkbox"
                                        class="form-check-input me-1"
                                        data-bf-select-item="stale-installer-temp"
                                        name="stale_installer_temp_dirs[]"
                                        value="<?php echo htmlspecialchars((string) $dir, ENT_QUOTES, 'UTF-8'); ?>"
                                        aria-label="<?php echo htmlspecialchars(Text::sprintf('COM_BREEZINGFORMSNG_ABOUT_AUDIT_STALE_INSTALLER_TEMP_SELECT_ONE', (int) $index + 1), ENT_QUOTES, 'UTF-8'); ?>"
                                    >
                                    <code><?php echo htmlspecialchars((string) $dir, ENT_QUOTES, 'UTF-8'); ?></code>
                                    <button
                                        type="submit"
                                        class="btn btn-sm btn-warning ms-2"
                                        name="stale_installer_temp_dir"
                                        value="<?php echo htmlspecialchars((string) $dir, ENT_QUOTES, 'UTF-8'); ?>"
                                        onclick="document.getElementById('bf-about-task').value='about.deleteStaleInstallerTemp';"
                                        title="<?php echo htmlspecialchars(Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_STALE_INSTALLER_TEMP_REPAIR_TIP'), ENT_QUOTES, 'UTF-8'); ?>"
                                        aria-label="<?php echo htmlspecialchars(Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_STALE_INSTALLER_TEMP_REPAIR'), ENT_QUOTES, 'UTF-8'); ?>"
                                    ><span class="icon-trash" aria-hidden="true"></span></button>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if ($auditMenuIssues !== array()) : ?>
                    <div class="bf-audit-section-block mb-3">
                        <h4 class="h6"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_MENU_ISSUES'); ?><?php echo $auditHelp('COM_BREEZINGFORMSNG_ABOUT_AUDIT_MENU_ISSUES_HELP'); ?></h4>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead><tr><th><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_MENU'); ?></th><th><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_MENU_FORM'); ?></th><th><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_ISSUE'); ?></th><th><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_ACTIONS'); ?></th></tr></thead>
                                <tbody>
                                <?php foreach ($auditMenuIssues as $issue) : ?>
                                    <?php $menuId = (int) ($issue['menu_id'] ?? 0); ?>
                                    <tr class="table-warning">
                                        <td>
                                            <?php $menuTitle = htmlspecialchars((string) ($issue['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                                            <?php if ($menuId > 0) : ?>
                                                <a href="index.php?option=com_menus&amp;task=item.edit&amp;id=<?php echo $menuId; ?>" target="_blank" rel="noopener"
                                                    title="<?php echo htmlspecialchars(Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_MENU_EDIT'), ENT_QUOTES, 'UTF-8'); ?>"><?php echo $menuTitle; ?> <span class="icon-external-link small" aria-hidden="true"></span></a>
                                            <?php else : ?>
                                                <?php echo $menuTitle; ?>
                                            <?php endif; ?>
                                            <span class="text-muted">(#<?php echo $menuId; ?>)</span>
                                        </td>
                                        <td><code><?php echo htmlspecialchars((string) ($issue['form_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></code></td>
                                        <td>
                                            <?php foreach ($auditStringList($issue['issues'] ?? array()) as $code) : ?>
                                                <?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_MENU_' . strtoupper((string) $code)); ?><br />
                                            <?php endforeach; ?>
                                        </td>
                                        <td class="text-nowrap">
                                            <?php if ($menuId > 0) : ?>
                                                <button type="button" class="btn btn-sm btn-outline-danger"
                                                    title="<?php echo htmlspecialchars(Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_MENU_DELETE'), ENT_QUOTES, 'UTF-8'); ?>"
                                                    onclick="if (window.confirm('<?php echo htmlspecialchars(Text::sprintf('COM_BREEZINGFORMSNG_ABOUT_AUDIT_MENU_DELETE_CONFIRM', $menuId), ENT_QUOTES, 'UTF-8'); ?>')) { document.getElementById('bf-menu-item-id').value = '<?php echo $menuId; ?>'; Joomla.submitform('about.deleteMenuItem'); }">
                                                    <span class="icon-trash" aria-hidden="true"></span>
                                                    <span class="visually-hidden"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_MENU_DELETE'); ?></span>
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($auditDuplicateForms !== array()) : ?>
                    <div class="bf-audit-section-block mb-3">
                        <h4 class="h6"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_DUPLICATE_FORMS'); ?><?php echo $auditHelp('COM_BREEZINGFORMSNG_ABOUT_AUDIT_DUPLICATE_FORMS_HELP'); ?></h4>
                        <p class="text-muted small mb-2"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_DUPLICATE_FORMS_DESC'); ?></p>
                        <ul class="mb-0">
                            <?php foreach ($auditDuplicateForms as $group) : ?>
                                <li>
                                    <code><?php echo htmlspecialchars((string) ($group['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></code>
                                    <?php if ((string) ($group['package'] ?? '') !== '') : ?>
                                        <span class="text-muted">(<?php echo htmlspecialchars((string) $group['package'], ENT_QUOTES, 'UTF-8'); ?>)</span>
                                    <?php endif; ?>
                                    &mdash;
                                    <?php echo Text::plural(
                                        'COM_BREEZINGFORMSNG_ABOUT_AUDIT_DUPLICATE_FORMS_HINT_KEEP',
                                        (int) ($group['keep']['record_count'] ?? 0),
                                        (int) ($group['keep']['id'] ?? 0)
                                    ); ?>
                                    <?php foreach ($auditEntryList($group['drop'] ?? array()) as $entry) : ?>
                                        <?php
                                        $dropId = (int) ($entry['id'] ?? 0);
                                        $dropRecordCount = (int) ($entry['record_count'] ?? 0);
                                        ?>
                                        <span class="text-nowrap me-1">
                                            #<?php echo $dropId; ?><?php if ($dropRecordCount > 0) :
                                                ?><span class="text-muted"> (<?php echo $dropRecordCount; ?>)</span><?php
                                             else :
                                                    ?><button
                                                type="button"
                                                class="btn btn-sm btn-link link-danger p-0 align-baseline"
                                                title="<?php echo htmlspecialchars(Text::sprintf('COM_BREEZINGFORMSNG_ABOUT_AUDIT_DUPLICATE_FORM_DELETE', $dropId), ENT_QUOTES, 'UTF-8'); ?>"
                                                onclick="if (window.confirm('<?php echo htmlspecialchars(Text::sprintf('COM_BREEZINGFORMSNG_ABOUT_AUDIT_DUPLICATE_FORM_DELETE_CONFIRM', $dropId), ENT_QUOTES, 'UTF-8'); ?>')) { document.getElementById('bf-duplicate-form-id').value = '<?php echo $dropId; ?>'; Joomla.submitform('about.deleteDuplicateForm'); }"
                                            ><span class="icon-trash" aria-hidden="true"></span><span class="visually-hidden"><?php echo htmlspecialchars(Text::sprintf('COM_BREEZINGFORMSNG_ABOUT_AUDIT_DUPLICATE_FORM_DELETE', $dropId), ENT_QUOTES, 'UTF-8'); ?></span></button><?php
                                             endif; ?>
                                        </span>
                                    <?php endforeach; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if ($auditExtensionDuplicates !== array()) : ?>
                    <div class="bf-audit-section-block mb-3">
                        <h4 class="h6"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_EXTENSION_DUPLICATES'); ?><?php echo $auditHelp('COM_BREEZINGFORMSNG_ABOUT_AUDIT_EXTENSION_DUPLICATES_HELP'); ?></h4>
                        <ul class="mb-0">
                            <?php foreach ($auditExtensionDuplicates as $group) : ?>
                                <li>
                                    <code><?php echo htmlspecialchars((string) ($group['keep']['type'] ?? '') . '/' . (string) ($group['keep']['element'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></code>
                                    &mdash;
                                    <?php echo Text::sprintf(
                                        'COM_BREEZINGFORMSNG_ABOUT_AUDIT_EXTENSION_DUPLICATE_HINT',
                                        (int) ($group['keep']['extension_id'] ?? 0),
                                        htmlspecialchars(implode(', ', array_map(
                                            static fn(array $entry): string => '#' . (int) ($entry['extension_id'] ?? 0),
                                            $auditEntryList($group['drop'] ?? array())
                                        )), ENT_QUOTES, 'UTF-8')
                                    ); ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if ($auditExtensionLegacy !== array()) : ?>
                    <div class="bf-audit-section-block mb-3">
                        <h4 class="h6"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_EXTENSION_LEGACY'); ?><?php echo $auditHelp('COM_BREEZINGFORMSNG_ABOUT_AUDIT_EXTENSION_LEGACY_HELP'); ?></h4>
                        <p class="small text-muted mb-2"><?php echo Text::sprintf('COM_BREEZINGFORMSNG_ABOUT_AUDIT_EXTENSION_LEGACY_EXPLAIN', '<a href="' . htmlspecialchars(Route::_('index.php?option=com_installer&view=manage&filter[search]=breezingforms', false), ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars(Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_EXTENSION_LEGACY_LINK_LABEL'), ENT_QUOTES, 'UTF-8') . '</a>'); ?></p>
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                            <label class="form-check mb-0">
                                <input type="checkbox" class="form-check-input" data-bf-select-all="legacy-extensions" aria-label="<?php echo htmlspecialchars(Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_DUPLICATE_INDEX_SELECT_ALL'), ENT_QUOTES, 'UTF-8'); ?>">
                                <span class="form-check-label"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_DUPLICATE_INDEX_SELECT_ALL'); ?></span>
                            </label>
                            <button type="submit" class="btn btn-sm btn-warning" title="<?php echo htmlspecialchars(Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_EXTENSION_LEGACY_REMOVE_SELECTED_TIP'), ENT_QUOTES, 'UTF-8'); ?>" onclick="if (!window.confirm('<?php echo htmlspecialchars(Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_EXTENSION_LEGACY_CONFIRM'), ENT_QUOTES, 'UTF-8'); ?>')) { return false; } document.getElementById('bf-about-task').value='about.removeLegacyExtensions';document.getElementById('bf-legacy-extension').value='';"><span class="icon-trash me-1" aria-hidden="true"></span><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_EXTENSION_LEGACY_REMOVE_SELECTED'); ?></button>
                        </div>
                        <ul class="list-unstyled mb-0">
                            <?php foreach ($auditExtensionLegacy as $index => $entry) : ?>
                                <?php $legacyId = (int) ($entry['extension_id'] ?? 0); ?>
                                <?php $legacyUrl = $auditLegacyUrl($entry); ?>
                                <li class="d-flex align-items-center gap-2 mb-1">
                                    <input type="checkbox" class="form-check-input mt-0" data-bf-select-item="legacy-extensions" name="legacy_extensions[]" value="<?php echo $legacyId; ?>" aria-label="<?php echo htmlspecialchars(Text::sprintf('COM_BREEZINGFORMSNG_ABOUT_AUDIT_EXTENSION_LEGACY_SELECT_ONE', (int) $index + 1), ENT_QUOTES, 'UTF-8'); ?>">
                                    <a href="<?php echo htmlspecialchars(Route::_($legacyUrl, false), ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener" title="<?php echo htmlspecialchars(Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_EXTENSION_LEGACY_MANAGE'), ENT_QUOTES, 'UTF-8'); ?>"><code><?php echo htmlspecialchars((string) ($entry['type'] ?? '') . '/' . ((string) ($entry['folder'] ?? '') !== '' ? (string) $entry['folder'] . '/' : '') . (string) ($entry['element'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></code></a>
                                    <span class="text-muted">(#<?php echo $legacyId; ?>)</span>
                                    <button type="submit" class="btn btn-sm btn-warning" title="<?php echo htmlspecialchars(Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_EXTENSION_LEGACY_REMOVE_TIP'), ENT_QUOTES, 'UTF-8'); ?>" aria-label="<?php echo htmlspecialchars(Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_EXTENSION_LEGACY_REMOVE'), ENT_QUOTES, 'UTF-8'); ?>" onclick="if (!window.confirm('<?php echo htmlspecialchars(Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_EXTENSION_LEGACY_CONFIRM'), ENT_QUOTES, 'UTF-8'); ?>')) { return false; } document.getElementById('bf-about-task').value='about.removeLegacyExtensions';document.getElementById('bf-legacy-extension').value='<?php echo $legacyId; ?>';"><span class="icon-trash" aria-hidden="true"></span></button>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if ($auditCollationIssues !== array()) : ?>
                    <div class="bf-audit-section-block mb-3">
                        <h4 class="h6"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_COLLATIONS'); ?><?php echo $auditHelp('COM_BREEZINGFORMSNG_ABOUT_AUDIT_COLLATIONS_HELP'); ?></h4>
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                            <label class="form-check mb-0">
                                <input type="checkbox" class="form-check-input" data-bf-select-all="table-collations" aria-label="<?php echo htmlspecialchars(Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_DUPLICATE_INDEX_SELECT_ALL'), ENT_QUOTES, 'UTF-8'); ?>">
                                <span class="form-check-label"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_DUPLICATE_INDEX_SELECT_ALL'); ?></span>
                            </label>
                            <button type="submit" class="btn btn-sm btn-warning" onclick="document.getElementById('bf-about-task').value='about.repairTableCollations';document.getElementById('bf-table-collation-issue').value='';"><span class="fa-solid fa-wrench me-1" aria-hidden="true"></span><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_DUPLICATE_INDEX_REPAIR_SELECTED'); ?></button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead><tr><th></th><th><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_TABLE'); ?></th><th><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_CURRENT'); ?></th><th><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_EXPECTED'); ?></th><th><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_ACTIONS'); ?></th></tr></thead>
                                <tbody>
                                <?php foreach ($auditCollationIssues as $index => $issue) : ?>
                                    <?php $tableCollationToken = DatabaseRepairService::getTableCollationSelectionToken((array) $issue); ?>
                                    <tr class="table-warning"><td><input type="checkbox" class="form-check-input" data-bf-select-item="table-collations" name="table_collation_issues[]" value="<?php echo htmlspecialchars($tableCollationToken, ENT_QUOTES, 'UTF-8'); ?>" aria-label="<?php echo htmlspecialchars(Text::sprintf('COM_BREEZINGFORMSNG_ABOUT_AUDIT_DUPLICATE_INDEX_SELECT_ONE', (int) $index + 1), ENT_QUOTES, 'UTF-8'); ?>"></td><td><code><?php echo htmlspecialchars((string) ($issue['table'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></code></td><td><?php echo htmlspecialchars((string) ($issue['collation'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo htmlspecialchars((string) ($issue['expected'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td><td class="text-nowrap"><button type="submit" class="btn btn-sm btn-warning" onclick="document.getElementById('bf-about-task').value='about.repairTableCollations';document.getElementById('bf-table-collation-issue').value='<?php echo $tableCollationToken; ?>';" aria-label="<?php echo htmlspecialchars(Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_TABLE_COLLATION_REPAIR'), ENT_QUOTES, 'UTF-8'); ?>"><span class="fa-solid fa-wrench" aria-hidden="true"></span></button></td></tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($auditColumnCollationIssues !== array()) : ?>
                    <div class="bf-audit-section-block mb-3">
                        <h4 class="h6"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_COLUMN_COLLATIONS'); ?><?php echo $auditHelp('COM_BREEZINGFORMSNG_ABOUT_AUDIT_COLUMN_COLLATIONS_HELP'); ?></h4>
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                            <label class="form-check mb-0">
                                <input
                                    type="checkbox"
                                    class="form-check-input"
                                    data-bf-select-all="column-collations"
                                    aria-label="<?php echo htmlspecialchars(Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_COLUMN_COLLATION_SELECT_ALL'), ENT_QUOTES, 'UTF-8'); ?>"
                                >
                                <span class="form-check-label"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_COLUMN_COLLATION_SELECT_ALL'); ?></span>
                            </label>
                            <button
                                type="submit"
                                class="btn btn-sm btn-warning"
                                onclick="document.getElementById('bf-about-task').value='about.repairColumnCollations';document.getElementById('bf-column-collation-issue').value='';"
                                title="<?php echo htmlspecialchars(Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_COLUMN_COLLATION_REPAIR_SELECTED_TIP'), ENT_QUOTES, 'UTF-8'); ?>"
                            >
                                <span class="fa-solid fa-wrench me-1" aria-hidden="true"></span><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_COLUMN_COLLATION_REPAIR_SELECTED'); ?>
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead><tr><th></th><th><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_TABLE'); ?></th><th><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_COLUMN'); ?></th><th><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_CHARSET'); ?></th><th><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_COLLATION'); ?></th><th><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_EXPECTED'); ?></th><th><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_ACTIONS'); ?></th></tr></thead>
                                <tbody>
                                <?php foreach ($auditColumnCollationIssues as $index => $issue) : ?>
                                    <?php $columnCollationToken = DatabaseRepairService::getColumnCollationSelectionToken((array) $issue); ?>
                                    <tr class="table-warning"><td><input type="checkbox" class="form-check-input" data-bf-select-item="column-collations" name="column_collation_issues[]" value="<?php echo htmlspecialchars($columnCollationToken, ENT_QUOTES, 'UTF-8'); ?>" aria-label="<?php echo htmlspecialchars(Text::sprintf('COM_BREEZINGFORMSNG_ABOUT_AUDIT_COLUMN_COLLATION_SELECT_ONE', (int) $index + 1), ENT_QUOTES, 'UTF-8'); ?>"></td><td><code><?php echo htmlspecialchars((string) ($issue['table'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></code></td><td><code><?php echo htmlspecialchars((string) ($issue['column'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></code></td><td><?php echo htmlspecialchars((string) ($issue['charset'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo htmlspecialchars((string) ($issue['collation'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo htmlspecialchars((string) ($issue['expected_charset'] ?? '') . ' / ' . (string) ($issue['expected'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td><td class="text-nowrap"><button type="submit" class="btn btn-sm btn-warning" onclick="document.getElementById('bf-about-task').value='about.repairColumnCollations';document.getElementById('bf-column-collation-issue').value='<?php echo $columnCollationToken; ?>';" title="<?php echo htmlspecialchars(Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_COLUMN_COLLATION_REPAIR_TIP'), ENT_QUOTES, 'UTF-8'); ?>" aria-label="<?php echo htmlspecialchars(Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_COLUMN_COLLATION_REPAIR'), ENT_QUOTES, 'UTF-8'); ?>"><span class="fa-solid fa-wrench" aria-hidden="true"></span></button></td></tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (count($auditCollationHistogram) > 1) : ?>
                    <div class="bf-audit-section-block mb-3">
                        <h4 class="h6"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_MIXED_COLLATIONS'); ?><?php echo $auditHelp('COM_BREEZINGFORMSNG_ABOUT_AUDIT_MIXED_COLLATIONS_HELP'); ?></h4>
                        <ul class="mb-0">
                            <?php foreach ($auditCollationHistogram as $collationName => $count) : ?>
                                <li><code><?php echo htmlspecialchars((string) $collationName, ENT_QUOTES, 'UTF-8'); ?></code>: <?php echo (int) $count; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if ($auditDuplicateIndexes !== array()) : ?>
                    <div class="bf-audit-section-block mb-3">
                        <h4 class="h6"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_DUPLICATE_INDEXES'); ?><?php echo $auditHelp('COM_BREEZINGFORMSNG_ABOUT_AUDIT_DUPLICATE_INDEXES_HELP'); ?></h4>
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                            <label class="form-check mb-0">
                                <input
                                    type="checkbox"
                                    class="form-check-input"
                                    data-bf-select-all="duplicate-indexes"
                                    aria-label="<?php echo htmlspecialchars(Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_DUPLICATE_INDEX_SELECT_ALL'), ENT_QUOTES, 'UTF-8'); ?>"
                                >
                                <span class="form-check-label"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_DUPLICATE_INDEX_SELECT_ALL'); ?></span>
                            </label>
                            <button
                                type="submit"
                                class="btn btn-sm btn-warning"
                                onclick="document.getElementById('bf-about-task').value='about.repairDuplicateIndexes';document.getElementById('bf-duplicate-index-group').value='';"
                                title="<?php echo htmlspecialchars(Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_DUPLICATE_INDEX_REPAIR_SELECTED_TIP'), ENT_QUOTES, 'UTF-8'); ?>"
                            >
                                <span class="fa-solid fa-wrench me-1" aria-hidden="true"></span><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_DUPLICATE_INDEX_REPAIR_SELECTED'); ?>
                            </button>
                        </div>
                        <ul class="mb-0">
                            <?php foreach ($auditDuplicateIndexes as $index => $issue) : ?>
                                <?php $duplicateIndexToken = DatabaseRepairService::getDuplicateIndexSelectionToken((array) $issue); ?>
                                <li>
                                    <input
                                        type="checkbox"
                                        class="form-check-input me-1"
                                        data-bf-select-item="duplicate-indexes"
                                        name="duplicate_index_groups[]"
                                        value="<?php echo htmlspecialchars($duplicateIndexToken, ENT_QUOTES, 'UTF-8'); ?>"
                                        aria-label="<?php echo htmlspecialchars(Text::sprintf('COM_BREEZINGFORMSNG_ABOUT_AUDIT_DUPLICATE_INDEX_SELECT_ONE', (int) $index + 1), ENT_QUOTES, 'UTF-8'); ?>"
                                    >
                                    <code><?php echo htmlspecialchars((string) ($issue['table'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></code>:
                                    <?php echo htmlspecialchars(implode(', ', $auditStringList($issue['indexes'] ?? array())), ENT_QUOTES, 'UTF-8'); ?>
                                    <?php if (($issue['keep'] ?? '') !== '' && $auditStringList($issue['drop'] ?? array()) !== array()) : ?>
                                        &mdash;
                                        <?php echo Text::sprintf(
                                            'COM_BREEZINGFORMSNG_ABOUT_AUDIT_DUPLICATE_INDEX_HINT',
                                            htmlspecialchars((string) $issue['keep'], ENT_QUOTES, 'UTF-8'),
                                            htmlspecialchars(implode(', ', (array) $issue['drop']), ENT_QUOTES, 'UTF-8')
                                        ); ?>
                                    <?php endif; ?>
                                    <button
                                        type="submit"
                                        class="btn btn-sm btn-warning ms-2"
                                        onclick="document.getElementById('bf-about-task').value='about.repairDuplicateIndexes';document.getElementById('bf-duplicate-index-group').value='<?php echo $duplicateIndexToken; ?>';"
                                        title="<?php echo htmlspecialchars(Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_DUPLICATE_INDEX_REPAIR_TIP'), ENT_QUOTES, 'UTF-8'); ?>"
                                        aria-label="<?php echo htmlspecialchars(Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_DUPLICATE_INDEX_REPAIR'), ENT_QUOTES, 'UTF-8'); ?>"
                                    ><span class="fa-solid fa-wrench" aria-hidden="true"></span></button>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if ($auditOrphanChecks !== array()) : ?>
                    <div id="bf-audit-orphan-messages" class="mb-2" role="status" aria-live="polite"></div>
                    <div class="bf-audit-section-block mb-3" id="bf-audit-orphan-references">
                        <h4 class="h6"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_ORPHAN_REFERENCES'); ?><?php echo $auditHelp('COM_BREEZINGFORMSNG_ABOUT_AUDIT_ORPHAN_REFERENCES_HELP'); ?></h4>
                        <?php foreach ($auditOrphanChecks as $check) : ?>
                            <?php
                            $orphanGroups = $auditEntryList($check['groups'] ?? array());
                            $orphanParent = strtoupper((string) ($check['parent'] ?? ''));
                            $orphanHidden = (int) ($check['parents'] ?? 0) - count($orphanGroups);
                            $orphanHasLabel = array_filter(array_column($orphanGroups, 'label')) !== array();
                            $orphanValues = ($check['id'] ?? '') === 'subrecords_without_element';
                            // Values of a record that no longer exists: nothing can show them, they can be deleted.
                            $orphanRecordValues = ($check['id'] ?? '') === 'subrecords_without_record';
                            // Records and elements of a deleted form: one trash button per form, and a selection to delete several.
                            $orphanSelectable = in_array((string) ($check['id'] ?? ''), array('records_without_form', 'elements_without_form'), true);
                            $orphanDeleteTask = ($check['id'] ?? '') === 'records_without_form' ? 'about.deleteOrphanRecords' : 'about.deleteOrphanElements';
                            $orphanDeleteKey = ($check['id'] ?? '') === 'records_without_form' ? 'RECORDS' : 'ELEMENTS';
                            $orphanHasForm = array_filter(array_column($orphanGroups, 'form_id')) !== array();
                            $orphanHasPeriod = array_filter(array_column($orphanGroups, 'first')) !== array();
                            // Records of a deleted form: link to the records list filtered on it, all states.
                            $orphanRecordsLink = ($check['id'] ?? '') === 'records_without_form';
                            ?>
                            <details class="bf-audit-orphans mb-1" open data-bf-orphan-check="<?php echo htmlspecialchars((string) ($check['id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                <summary><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_' . strtoupper((string) ($check['id'] ?? ''))); ?>: <strong class="bf-orphan-count"><?php echo (int) ($check['count'] ?? 0); ?></strong></summary>
                                <div class="table-responsive mt-2 mb-3">
                                    <table class="table table-sm table-striped align-middle mb-0 w-auto">
                                        <thead>
                                            <tr>
                                                <?php if ($orphanSelectable) : ?>
                                                    <th scope="col" class="w-1">
                                                        <input type="checkbox" class="form-check-input" data-bf-orphan-select-all
                                                            aria-label="<?php echo htmlspecialchars(Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_DUPLICATE_INDEX_SELECT_ALL'), ENT_QUOTES, 'UTF-8'); ?>"
                                                            <?php echo $this->canDeleteOrphans ? '' : ' disabled'; ?>>
                                                    </th>
                                                <?php endif; ?>
                                                <th scope="col"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_ORPHAN_PARENT_' . $orphanParent); ?></th>
                                                <?php if ($orphanHasLabel && !$orphanValues) : ?>
                                                    <th scope="col"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_ORPHAN_LABEL'); ?></th>
                                                <?php endif; ?>
                                                <?php if ($orphanHasForm) : ?>
                                                    <th scope="col"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_ORPHAN_FORM'); ?></th>
                                                <?php endif; ?>
                                                <th scope="col" class="<?php echo $orphanValues ? 'text-center' : 'text-end'; ?>"><?php echo Text::_($orphanValues ? 'COM_BREEZINGFORMSNG_ABOUT_AUDIT_ORPHAN_RECORDS' : 'COM_BREEZINGFORMSNG_ABOUT_AUDIT_ORPHAN_ROWS'); ?></th>
                                                <?php if ($orphanValues) : ?>
                                                    <th scope="col" class="text-nowrap"><span class="fa-solid fa-arrow-right-long me-1" aria-hidden="true"></span><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_ORPHAN_TARGET'); ?><?php echo $auditHelp('COM_BREEZINGFORMSNG_ABOUT_AUDIT_ORPHAN_VALUES_REASSIGN_HELP'); ?></th>
                                                    <th scope="col"><span class="visually-hidden"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_ORPHAN_VALUES_DELETE'); ?></span></th>
                                                <?php endif; ?>
                                                <?php if ($orphanSelectable) : ?>
                                                    <th scope="col"><span class="visually-hidden"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_ORPHAN_' . $orphanDeleteKey . '_DELETE'); ?></span></th>
                                                <?php endif; ?>
                                                <?php if ($orphanRecordValues) : ?>
                                                    <th scope="col"><span class="visually-hidden"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_ORPHAN_VALUES_DELETE'); ?></span></th>
                                                <?php endif; ?>
                                                <?php if ($orphanHasPeriod) : ?>
                                                    <th scope="col"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_ORPHAN_PERIOD'); ?></th>
                                                <?php endif; ?>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($orphanGroups as $orphanGroup) : ?>
                                                <tr>
                                                    <?php if ($orphanSelectable) : ?>
                                                        <td>
                                                            <input type="checkbox" class="form-check-input" data-bf-orphan-select
                                                                value="<?php echo (int) ($orphanGroup['parent_id'] ?? 0); ?>"
                                                                data-bf-count="<?php echo (int) ($orphanGroup['count'] ?? 0); ?>"
                                                                aria-label="<?php echo htmlspecialchars(Text::sprintf('COM_BREEZINGFORMSNG_ABOUT_AUDIT_ORPHAN_SELECT_ONE', '#' . (int) ($orphanGroup['parent_id'] ?? 0)), ENT_QUOTES, 'UTF-8'); ?>"
                                                                <?php echo $this->canDeleteOrphans ? '' : ' disabled'; ?>>
                                                        </td>
                                                    <?php endif; ?>
                                                    <?php $orphanLabel = htmlspecialchars((string) ($orphanGroup['label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                                                    <?php $orphanId = '#' . (int) ($orphanGroup['parent_id'] ?? 0); ?>
                                                    <?php if ($orphanValues) : ?>
                                                        <td>
                                                            <?php echo $orphanLabel !== '' ? '<div>' . $orphanLabel . '</div>' : ''; ?>
                                                            <div class="small text-muted"><?php echo $orphanId; ?></div>
                                                        </td>
                                                    <?php else : ?>
                                                        <td><?php echo $orphanLabel === '' ? $orphanLink($orphanRecordsLink, $orphanGroup, $orphanId) : $orphanId; ?></td>
                                                        <?php if ($orphanHasLabel) : ?>
                                                            <td><?php echo $orphanLabel === '' ? '' : $orphanLink($orphanRecordsLink, $orphanGroup, $orphanLabel); ?></td>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                    <?php if ($orphanHasForm) : ?>
                                                        <?php $orphanFormId = (int) ($orphanGroup['form_id'] ?? 0); ?>
                                                        <td>
                                                            <?php if ($orphanFormId > 0) : ?>
                                                                <?php if ($orphanValues) : ?>
                                                                    <a href="<?php echo Route::_('index.php?option=com_breezingformsng&task=quickmode.display&form=' . $orphanFormId); ?>" target="_blank" rel="noopener"
                                                                        title="<?php echo htmlspecialchars(Text::_('COM_BREEZINGFORMSNG_FORMS_OPEN_EDITOR'), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars(trim((string) ($orphanGroup['form_title'] ?? '')) !== '' ? trim((string) $orphanGroup['form_title']) : '#' . $orphanFormId, ENT_QUOTES, 'UTF-8'); ?></a>
                                                                <?php else : ?>
                                                                    <?php echo $orphanLink(true, array('parent_id' => $orphanFormId), htmlspecialchars(trim((string) ($orphanGroup['form_title'] ?? '') . ' #' . $orphanFormId), ENT_QUOTES, 'UTF-8')); ?>
                                                                <?php endif; ?>
                                                                <?php if ((int) ($orphanGroup['forms'] ?? 1) > 1) : ?>
                                                                    <span class="text-muted">+<?php echo (int) $orphanGroup['forms'] - 1; ?></span>
                                                                <?php endif; ?>
                                                            <?php endif; ?>
                                                        </td>
                                                    <?php endif; ?>
                                                    <td class="<?php echo $orphanValues ? 'text-center' : 'text-end'; ?>">
                                                        <?php if ($orphanValues && (int) ($orphanGroup['parent_id'] ?? 0) > 0) : ?>
                                                            <a href="<?php echo Route::_('index.php?option=com_breezingformsng&view=records&filter[orphan_element]=' . (int) $orphanGroup['parent_id'] . '&filter[state]=*'); ?>" target="_blank" rel="noopener"
                                                                title="<?php echo htmlspecialchars(Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_ORPHAN_SHOW_RECORDS'), ENT_QUOTES, 'UTF-8'); ?>"><?php echo (int) ($orphanGroup['records'] ?? 0); ?></a>
                                                        <?php else : ?>
                                                            <?php echo (int) ($orphanGroup['count'] ?? 0); ?>
                                                        <?php endif; ?>
                                                    </td>
                                                    <?php if ($orphanValues) : ?>
                                                        <td class="text-nowrap">
                                                            <?php $orphanCandidates = $auditEntryList($orphanGroup['candidates'] ?? array()); ?>
                                                            <?php if ($orphanCandidates === array()) : ?>
                                                                <span class="text-muted"><span class="fa-solid fa-arrow-right-long me-1" aria-hidden="true"></span><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_ORPHAN_TARGET_NONE'); ?></span>
                                                            <?php else : ?>
                                                                <?php
                                                                $orphanSelectId = 'bf-orphan-target-' . (int) ($orphanGroup['parent_id'] ?? 0);
                                                                $orphanPreselected = (int) ($orphanGroup['target_id'] ?? 0);
                                                                $orphanOptions = array();
                                                                foreach ($orphanCandidates as $candidate) {
                                                                    $orphanOptions[(int) $candidate['form']]['title'] = (string) $candidate['form_title'];
                                                                    $orphanOptions[(int) $candidate['form']]['items'][] = $candidate;
                                                                }
                                                                ?>
                                                                <div class="input-group input-group-sm">
                                                                    <select class="form-select form-select-sm" id="<?php echo $orphanSelectId; ?>" aria-label="<?php echo htmlspecialchars(Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_ORPHAN_REPAIR_TARGET'), ENT_QUOTES, 'UTF-8'); ?>"<?php echo $this->canDeleteOrphans ? '' : ' disabled'; ?>>
                                                                        <option value="0"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_ORPHAN_REPAIR_SELECT'); ?></option>
                                                                        <?php foreach ($orphanOptions as $optionForm => $optionGroup) : ?>
                                                                            <optgroup label="<?php echo htmlspecialchars(trim($optionGroup['title'] . ' #' . $optionForm), ENT_QUOTES, 'UTF-8'); ?>">
                                                                                <?php foreach ($optionGroup['items'] as $candidate) : ?>
                                                                                    <option value="<?php echo (int) $candidate['id']; ?>"<?php echo (int) $candidate['id'] === $orphanPreselected ? ' selected' : ''; ?>><?php echo htmlspecialchars(trim((string) $candidate['name'] . ' ' . ((string) $candidate['title'] !== '' ? '(' . $candidate['title'] . ')' : '') . ' #' . (int) $candidate['id']), ENT_QUOTES, 'UTF-8'); ?></option>
                                                                                <?php endforeach; ?>
                                                                            </optgroup>
                                                                        <?php endforeach; ?>
                                                                    </select>
                                                                    <button
                                                                        type="button"
                                                                        class="btn btn-outline-primary"
                                                                        title="<?php echo htmlspecialchars(Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_ORPHAN_VALUES_REASSIGN'), ENT_QUOTES, 'UTF-8'); ?>"
                                                                        data-bf-orphan-preview
                                                                        data-bf-orphan-target-select="<?php echo $orphanSelectId; ?>"
                                                                        <?php echo $this->canDeleteOrphans ? '' : ' disabled'; ?>
                                                                        data-bf-orphan-element="<?php echo (int) ($orphanGroup['parent_id'] ?? 0); ?>"
                                                                    ><span class="icon-shuffle" aria-hidden="true"></span><span class="visually-hidden"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_ORPHAN_VALUES_REASSIGN'); ?></span></button>
                                                                </div>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php if ((int) ($orphanGroup['deletable'] ?? 0) === (int) ($orphanGroup['count'] ?? 0)) : ?>
                                                            <button
                                                                type="button"
                                                                class="btn btn-sm btn-outline-danger"
                                                                title="<?php echo htmlspecialchars(Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_ORPHAN_VALUES_DELETE'), ENT_QUOTES, 'UTF-8'); ?>"
                                                                data-bf-orphan-task="about.deleteOrphanValues"
                                                                <?php echo $this->canDeleteOrphans ? '' : ' disabled'; ?>
                                                                data-bf-orphan-element="<?php echo (int) ($orphanGroup['parent_id'] ?? 0); ?>"
                                                                data-bf-confirm="<?php echo htmlspecialchars(Text::plural('COM_BREEZINGFORMSNG_ABOUT_AUDIT_ORPHAN_VALUES_DELETE_CONFIRM', (int) ($orphanGroup['count'] ?? 0)), ENT_QUOTES, 'UTF-8'); ?>"
                                                            ><span class="icon-trash" aria-hidden="true"></span><span class="visually-hidden"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_ORPHAN_VALUES_DELETE'); ?></span></button>
                                                            <?php elseif ((int) ($orphanGroup['parent_id'] ?? 0) > 0) : ?>
                                                            <button
                                                                type="button"
                                                                class="btn btn-sm btn-danger"
                                                                title="<?php echo htmlspecialchars(Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_ORPHAN_VALUES_DELETE_LIVE'), ENT_QUOTES, 'UTF-8'); ?>"
                                                                data-bf-orphan-task="about.deleteOrphanValuesWithRecords"
                                                                data-bf-orphan-acknowledge="1"
                                                                <?php echo $this->canDeleteOrphans ? '' : ' disabled'; ?>
                                                                data-bf-orphan-element="<?php echo (int) $orphanGroup['parent_id']; ?>"
                                                                data-bf-confirm="<?php echo htmlspecialchars(Text::plural('COM_BREEZINGFORMSNG_ABOUT_AUDIT_ORPHAN_VALUES_DELETE_LIVE_CONFIRM', (int) ($orphanGroup['count'] ?? 0)), ENT_QUOTES, 'UTF-8'); ?>"
                                                            ><span class="icon-trash" aria-hidden="true"></span><span class="visually-hidden"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_ORPHAN_VALUES_DELETE_LIVE'); ?></span></button>
                                                            <?php endif; ?>
                                                        </td>
                                                    <?php endif; ?>
                                                    <?php if ($orphanSelectable) : ?>
                                                        <td>
                                                            <button
                                                                type="button"
                                                                class="btn btn-sm btn-outline-danger"
                                                                title="<?php echo htmlspecialchars(Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_ORPHAN_' . $orphanDeleteKey . '_DELETE'), ENT_QUOTES, 'UTF-8'); ?>"
                                                                data-bf-orphan-task="<?php echo $orphanDeleteTask; ?>"
                                                                <?php echo $this->canDeleteOrphans ? '' : ' disabled'; ?>
                                                                data-bf-orphan-element="<?php echo (int) ($orphanGroup['parent_id'] ?? 0); ?>"
                                                                data-bf-confirm="<?php echo htmlspecialchars(Text::plural('COM_BREEZINGFORMSNG_ABOUT_AUDIT_ORPHAN_' . $orphanDeleteKey . '_DELETE_CONFIRM', (int) ($orphanGroup['count'] ?? 0)), ENT_QUOTES, 'UTF-8'); ?>"
                                                            ><span class="icon-trash" aria-hidden="true"></span><span class="visually-hidden"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_ORPHAN_' . $orphanDeleteKey . '_DELETE'); ?></span></button>
                                                        </td>
                                                    <?php endif; ?>
                                                    <?php if ($orphanRecordValues) : ?>
                                                        <td>
                                                            <button
                                                                type="button"
                                                                class="btn btn-sm btn-outline-danger"
                                                                title="<?php echo htmlspecialchars(Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_ORPHAN_VALUES_DELETE'), ENT_QUOTES, 'UTF-8'); ?>"
                                                                data-bf-orphan-task="about.deleteOrphanSubrecords"
                                                                <?php echo $this->canDeleteOrphans ? '' : ' disabled'; ?>
                                                                data-bf-orphan-element="<?php echo (int) ($orphanGroup['parent_id'] ?? 0); ?>"
                                                                data-bf-confirm="<?php echo htmlspecialchars(Text::plural('COM_BREEZINGFORMSNG_ABOUT_AUDIT_ORPHAN_RECORD_VALUES_DELETE_CONFIRM', (int) ($orphanGroup['count'] ?? 0)), ENT_QUOTES, 'UTF-8'); ?>"
                                                            ><span class="icon-trash" aria-hidden="true"></span><span class="visually-hidden"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_ORPHAN_VALUES_DELETE'); ?></span></button>
                                                        </td>
                                                    <?php endif; ?>
                                                    <?php if ($orphanHasPeriod) : ?>
                                                        <td class="text-nowrap"><?php echo htmlspecialchars(substr((string) ($orphanGroup['first'] ?? ''), 0, 10) . ' → ' . substr((string) ($orphanGroup['last'] ?? ''), 0, 10), ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <?php endif; ?>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                    <?php if ($orphanHidden > 0) : ?>
                                        <p class="small text-muted mb-0 mt-1"><?php echo Text::sprintf('COM_BREEZINGFORMSNG_ABOUT_AUDIT_ORPHAN_MORE', $orphanHidden); ?></p>
                                    <?php endif; ?>
                                    <?php if ($orphanValues && (int) ($check['reassignable'] ?? 0) > 0) : ?>
                                        <button
                                            id="bf-audit-reassign-all-orphan-values"
                                            type="button"
                                            class="btn btn-sm btn-outline-primary mt-2 me-1"
                                            data-bf-orphan-task="about.reassignOrphanValues"
                                            <?php echo $this->canDeleteOrphans ? '' : ' disabled'; ?>
                                            data-bf-orphan-element="0"
                                            data-bf-orphan-all="1"
                                            data-bf-confirm="<?php echo htmlspecialchars(Text::plural('COM_BREEZINGFORMSNG_ABOUT_AUDIT_ORPHAN_VALUES_REASSIGN_ALL_CONFIRM', (int) ($check['reassignable'] ?? 0)), ENT_QUOTES, 'UTF-8'); ?>"
                                        ><span class="icon-shuffle" aria-hidden="true"></span> <?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_ORPHAN_VALUES_REASSIGN_ALL'); ?></button>
                                    <?php endif; ?>
                                    <?php if ($orphanValues && (int) ($check['deletable'] ?? 0) === (int) ($check['count'] ?? 0)) : ?>
                                        <button
                                            id="bf-audit-delete-all-orphan-values"
                                            type="button"
                                            class="btn btn-sm btn-outline-danger mt-2"
                                            data-bf-orphan-task="about.deleteOrphanValues"
                                            <?php echo $this->canDeleteOrphans ? '' : ' disabled'; ?>
                                            data-bf-orphan-element="0"
                                            data-bf-orphan-all="1"
                                            data-bf-confirm="<?php echo htmlspecialchars(Text::plural('COM_BREEZINGFORMSNG_ABOUT_AUDIT_ORPHAN_VALUES_DELETE_ALL_CONFIRM', (int) ($check['count'] ?? 0)), ENT_QUOTES, 'UTF-8'); ?>"
                                        ><span class="icon-trash" aria-hidden="true"></span> <?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_ORPHAN_VALUES_DELETE_ALL'); ?></button>
                                    <?php endif; ?>
                                    <?php if ($orphanSelectable) : ?>
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-danger mt-2"
                                            data-bf-orphan-task="<?php echo $orphanDeleteTask; ?>"
                                            data-bf-orphan-selection
                                            data-bf-confirm-template="<?php echo htmlspecialchars(Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_ORPHAN_' . $orphanDeleteKey . '_SELECTED_CONFIRM'), ENT_QUOTES, 'UTF-8'); ?>"
                                            disabled
                                        ><span class="icon-trash" aria-hidden="true"></span> <?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_ORPHAN_SELECTED_DELETE'); ?> <span data-bf-orphan-selected-count></span></button>
                                    <?php endif; ?>
                                    <?php if ($orphanRecordValues && (int) ($check['count'] ?? 0) > 0) : ?>
                                        <button
                                            id="bf-audit-delete-all-orphan-record-values"
                                            type="button"
                                            class="btn btn-sm btn-outline-danger mt-2"
                                            data-bf-orphan-task="about.deleteOrphanSubrecords"
                                            <?php echo $this->canDeleteOrphans ? '' : ' disabled'; ?>
                                            data-bf-orphan-element="0"
                                            data-bf-orphan-all="1"
                                            data-bf-confirm="<?php echo htmlspecialchars(Text::plural('COM_BREEZINGFORMSNG_ABOUT_AUDIT_ORPHAN_RECORD_VALUES_DELETE_ALL_CONFIRM', (int) ($check['count'] ?? 0)), ENT_QUOTES, 'UTF-8'); ?>"
                                        ><span class="icon-trash" aria-hidden="true"></span> <?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_ORPHAN_VALUES_DELETE'); ?></button>
                                    <?php endif; ?>
                                    <?php if (($check['id'] ?? '') === 'elements_without_form') : ?>
                                        <button
                                            id="bf-audit-delete-orphan-elements"
                                            type="button"
                                            class="btn btn-sm btn-outline-danger mt-2"
                                            data-bf-orphan-task="about.deleteOrphanElements"
                                            <?php echo $this->canDeleteOrphans ? '' : ' disabled'; ?>
                                            data-bf-orphan-all="1"
                                            data-bf-confirm="<?php echo htmlspecialchars(Text::plural('COM_BREEZINGFORMSNG_ABOUT_AUDIT_ORPHAN_ELEMENTS_DELETE_CONFIRM', (int) ($check['count'] ?? 0)), ENT_QUOTES, 'UTF-8'); ?>"
                                        ><span class="icon-trash" aria-hidden="true"></span> <?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_ORPHAN_ELEMENTS_DELETE'); ?></button>
                                    <?php endif; ?>
                                </div>
                            </details>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                </div>
            <?php endif; ?>
        </fieldset>
        </div>
    </div>

    <input type="hidden" name="option" value="com_breezingformsng" />
    <input type="hidden" name="task" id="bf-about-task" value="about.display" />
    <input type="hidden" name="view" value="about" />
    <input type="hidden" name="layout" value="audit" />
    <input type="hidden" name="duplicate_form_id" id="bf-duplicate-form-id" value="" />
    <input type="hidden" name="menu_item_id" id="bf-menu-item-id" value="" />
    <input type="hidden" name="orphan_element_id" id="bf-orphan-element-id" value="0" />
    <input type="hidden" name="duplicate_index_group" id="bf-duplicate-index-group" value="" />
    <input type="hidden" name="column_collation_issue" id="bf-column-collation-issue" value="" />
    <input type="hidden" name="table_collation_issue" id="bf-table-collation-issue" value="" />
    <input type="hidden" name="legacy_extension" id="bf-legacy-extension" value="" />
    <?php echo HTMLHelper::_('form.token'); ?>
</form>

<dialog id="bf-orphan-reassign-dialog" class="bf-orphan-reassign" aria-labelledby="bf-orphan-reassign-title"
    data-bf-label-move="<?php echo htmlspecialchars(Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_ORPHAN_REASSIGN_ACTION_MOVE'), ENT_QUOTES, 'UTF-8'); ?>"
    data-bf-label-duplicate="<?php echo htmlspecialchars(Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_ORPHAN_REASSIGN_ACTION_DUPLICATE'), ENT_QUOTES, 'UTF-8'); ?>"
    data-bf-label-taken="<?php echo htmlspecialchars(Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_ORPHAN_REASSIGN_ACTION_TAKEN'), ENT_QUOTES, 'UTF-8'); ?>"
    data-bf-label-empty="<?php echo htmlspecialchars(Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_ORPHAN_REASSIGN_EMPTY'), ENT_QUOTES, 'UTF-8'); ?>"
    data-bf-label-truncated="<?php echo htmlspecialchars(Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_ORPHAN_REASSIGN_TRUNCATED'), ENT_QUOTES, 'UTF-8'); ?>"
    data-bf-label-none-selected="<?php echo htmlspecialchars(Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_ORPHAN_REASSIGN_NO_SELECTION'), ENT_QUOTES, 'UTF-8'); ?>"
    data-bf-record-url="<?php echo htmlspecialchars(Route::_('index.php?option=com_breezingformsng&task=record.edit&id=', false), ENT_QUOTES, 'UTF-8'); ?>">
    <div class="card border-primary">
    <div class="card-header d-flex align-items-start gap-2">
        <span class="icon-shuffle mt-1" aria-hidden="true"></span>
        <div>
            <div><strong id="bf-orphan-reassign-title"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_ORPHAN_REASSIGN_TITLE'); ?></strong></div>
            <div data-bf-reassign-summary></div>
        </div>
        <button type="button" class="btn-close ms-auto" data-bf-reassign-cancel aria-label="<?php echo htmlspecialchars(Text::_('JCLOSE'), ENT_QUOTES, 'UTF-8'); ?>"></button>
    </div>
    <div class="card-body">
        <p class="small text-muted"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_ORPHAN_REASSIGN_INTRO'); ?></p>
        <div class="table-responsive bf-orphan-reassign-list">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th scope="col"><input type="checkbox" class="form-check-input" data-bf-reassign-all aria-label="<?php echo htmlspecialchars(Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_DUPLICATE_INDEX_SELECT_ALL'), ENT_QUOTES, 'UTF-8'); ?>"></th>
                        <th scope="col"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_ORPHAN_REASSIGN_COL_RECORD'); ?></th>
                        <th scope="col"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_ORPHAN_REASSIGN_COL_VALUE'); ?></th>
                        <th scope="col" class="text-nowrap"><span class="fa-solid fa-arrow-right-long me-1" aria-hidden="true"></span><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_ORPHAN_REASSIGN_COL_CURRENT'); ?></th>
                        <th scope="col"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_ORPHAN_REASSIGN_COL_ACTION'); ?></th>
                    </tr>
                </thead>
                <tbody data-bf-reassign-rows></tbody>
            </table>
        </div>
        <p class="small text-muted mt-2 mb-0" data-bf-reassign-note></p>
    </div>
    <div class="card-footer d-flex justify-content-end gap-2">
        <button type="button" class="btn btn-secondary" data-bf-reassign-cancel><span class="icon-times" aria-hidden="true"></span> <?php echo Text::_('JCANCEL'); ?></button>
        <button type="button" class="btn btn-primary" data-bf-reassign-apply><span class="icon-shuffle" aria-hidden="true"></span> <?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_ORPHAN_REASSIGN_APPLY'); ?></button>
    </div>
    </div>
</dialog>
