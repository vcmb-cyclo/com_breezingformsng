<?php
/** @psalm-suppress InvalidScope */

/**
 * BreezingForms NG - A Joomla Forms Application
 *
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 *
 * Tools / Statistics: the BreezingForms NG tables, with their rows and sizes.
 */

defined('_JEXEC') or die('Direct Access to this location is not allowed.');

/** @var \Vcmb\Component\BreezingformsNG\Administrator\View\About\HtmlView $this */

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$auditTables = (array) ($this->auditReport['tables'] ?? array());
$columnMismatches = array_count_values(array_map(
    static fn (array $issue): string => (string) ($issue['table'] ?? ''),
    (array) ($this->auditReport['column_collation_issues'] ?? array())
));
$auditHelp = static function (string $key): string {
    $text = htmlspecialchars(Text::_($key), ENT_QUOTES, 'UTF-8');

    return ' <span class="icon-info-circle text-muted ms-1" tabindex="0" role="img" title="' . $text . '" aria-label="' . $text . '"></span>';
};
?>

<form action="<?php echo Route::_('index.php?option=com_breezingformsng&task=about.runAudit'); ?>" method="post" id="bf-stats-audit-form" class="d-none">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>

<div class="card mt-3" id="bf-records-inventory">
    <div class="card-body p-3 p-lg-4">
        <h4 class="h6"><?php echo Text::_('COM_BREEZINGFORMSNG_TOOLS_RECORDS_INVENTORY'); ?><?php echo $auditHelp('COM_BREEZINGFORMSNG_TOOLS_RECORDS_INVENTORY_HELP'); ?></h4>
        <?php if ($this->recordInventory === []) : ?>
            <div class="alert alert-info mb-0"><?php echo Text::_('COM_BREEZINGFORMSNG_TOOLS_RECORDS_EMPTY'); ?></div>
        <?php else : ?>
            <?php
            $recordsUrl = static fn (int $formId, string $state): string => Route::_(
                'index.php?option=com_breezingformsng&view=records&filter[form]=' . $formId . '&filter[state]=' . $state
            );
            $sum = ['published' => 0, 'archived' => 0, 'trashed' => 0, 'total' => 0];
            $sortButton = static fn (string $label, string $type): string => '<th aria-sort="none"><button type="button" '
                . 'class="btn btn-link p-0 text-reset text-decoration-none" data-bf-table-sort="' . $type . '">'
                . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</button></th>';
    ?>
            <div class="table-responsive">
                <table class="table table-sm table-striped align-middle mb-0" data-bf-sortable-table>
                    <thead>
                        <tr>
                            <?php echo $sortButton(Text::_('COM_BREEZINGFORMSNG_TOOLS_RECORDS_FORM'), 'text'); ?>
                            <?php echo $sortButton(Text::_('JPUBLISHED'), 'number'); ?>
                            <?php echo $sortButton(Text::_('JARCHIVED'), 'number'); ?>
                            <?php echo $sortButton(Text::_('JTRASHED'), 'number'); ?>
                            <?php echo $sortButton(Text::_('COM_BREEZINGFORMSNG_TOOLS_RECORDS_TOTAL'), 'number'); ?>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($this->recordInventory as $entry) : ?>
                        <?php
                        $formId = (int) $entry['form'];
                        $formName = $entry['name'] !== ''
                            ? $entry['name']
                            : Text::sprintf('COM_BREEZINGFORMSNG_TOOLS_RECORDS_DELETED_FORM', $formId);
                        foreach (array_keys($sum) as $key) {
                            $sum[$key] += (int) $entry[$key];
                        }
                        ?>
                        <tr>
                            <td>
                                <?php if ($entry['name'] !== '') : ?>
                                    <a href="<?php echo Route::_('index.php?option=com_breezingformsng&task=quickmode.display&form=' . $formId); ?>"
                                        target="_blank" rel="noopener"
                                        title="<?php echo htmlspecialchars(Text::_('COM_BREEZINGFORMSNG_FORMS_OPEN_EDITOR'), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($formName, ENT_QUOTES, 'UTF-8'); ?></a>
                                <?php else : ?>
                                    <?php echo htmlspecialchars($formName, ENT_QUOTES, 'UTF-8'); ?>
                                <?php endif; ?>
                                <span class="text-muted small">(#<?php echo $formId; ?>)</span>
                            </td>
                            <?php foreach (['published' => '1', 'archived' => '2', 'trashed' => '-2'] as $key => $state) : ?>
                                <td data-bf-sort-value="<?php echo (int) $entry[$key]; ?>">
                                    <?php if ((int) $entry[$key] > 0) : ?>
                                        <a href="<?php echo $recordsUrl($formId, $state); ?>" target="_blank" rel="noopener"><?php echo number_format((int) $entry[$key], 0, '.', ' '); ?></a>
                                    <?php else : ?>
                                        0
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                            <td data-bf-sort-value="<?php echo (int) $entry['total']; ?>"><strong><?php echo number_format((int) $entry['total'], 0, '.', ' '); ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="fw-bold">
                            <td><?php echo Text::_('COM_BREEZINGFORMSNG_TOOLS_RECORDS_TOTAL'); ?></td>
                            <?php foreach ($sum as $value) : ?>
                                <td><?php echo number_format($value, 0, '.', ' '); ?></td>
                            <?php endforeach; ?>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="card mt-3" id="bf-statistics-section">
    <div class="card-body p-3 p-lg-4">
                <div class="bf-audit-section-block">
                    <h4 class="h6"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_TABLE_INVENTORY'); ?><?php echo $auditHelp('COM_BREEZINGFORMSNG_ABOUT_AUDIT_TABLE_INVENTORY_HELP'); ?></h4>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped align-middle mb-0" data-bf-sortable-table>
                            <thead><tr><th aria-sort="none"><button type="button" class="btn btn-link p-0 text-reset text-decoration-none" data-bf-table-sort="text"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_TABLE'); ?></button></th><th aria-sort="none"><button type="button" class="btn btn-link p-0 text-reset text-decoration-none" data-bf-table-sort="number"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_ROWS'); ?></button></th><th aria-sort="none"><button type="button" class="btn btn-link p-0 text-reset text-decoration-none" data-bf-table-sort="text"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_ENGINE'); ?></button></th><th aria-sort="none"><button type="button" class="btn btn-link p-0 text-reset text-decoration-none" data-bf-table-sort="text"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_COLLATION'); ?></button></th><th aria-sort="none"><button type="button" class="btn btn-link p-0 text-reset text-decoration-none" data-bf-table-sort="number"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_SIZE'); ?></button></th></tr></thead>
                            <tbody>
                            <?php foreach ($auditTables as $table) : ?>
                                <?php
                                $tableName = (string) ($table['table'] ?? '');
                                $tableSlug = preg_replace('/^#__facileforms_/', '', $tableName);
                                $tableDescKey = 'COM_BREEZINGFORMSNG_ABOUT_AUDIT_TABLE_DESC_' . strtoupper((string) $tableSlug);
                                $tableDesc = Text::_($tableDescKey);
                                ?>
                                <tr><td><code<?php echo $tableDesc !== $tableDescKey ? ' title="' . htmlspecialchars($tableDesc, ENT_QUOTES, 'UTF-8') . '"' : ''; ?>><?php echo htmlspecialchars($tableName, ENT_QUOTES, 'UTF-8'); ?></code></td><td data-bf-sort-value="<?php echo (int) ($table['rows'] ?? 0); ?>"><?php echo number_format((int) ($table['rows'] ?? 0), 0, '.', ' '); ?></td><td><?php echo htmlspecialchars((string) ($table['engine'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo htmlspecialchars((string) ($table['collation'] ?? ''), ENT_QUOTES, 'UTF-8'); ?><?php if (($columnMismatches[$tableName] ?? 0) > 0) :
                                    ?> <button type="submit" form="bf-stats-audit-form" class="btn btn-link p-0 ms-2 align-baseline small text-body-secondary" title="<?php echo htmlspecialchars(Text::_('COM_BREEZINGFORMSNG_TOOLS_STATISTICS_MISMATCH_HINT'), ENT_QUOTES, 'UTF-8'); ?>" data-bf-column-mismatch="<?php echo (int) $columnMismatches[$tableName]; ?>"><span class="icon-warning text-warning" aria-hidden="true"></span> <?php echo Text::plural('COM_BREEZINGFORMSNG_TOOLS_STATISTICS_MISMATCH', (int) $columnMismatches[$tableName]); ?></button><?php
                                             endif; ?></td><td data-bf-sort-value="<?php echo (int) ($table['size_bytes'] ?? 0); ?>"><?php echo HTMLHelper::_('number.bytes', (int) ($table['size_bytes'] ?? 0)); ?></td></tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
    </div>
</div>
