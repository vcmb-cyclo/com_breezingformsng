<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * Collapsed "Audit information" table of an item editor.
 *
 * @var array{id: string, created: ?string, createdBy: string, modified: ?string, modifiedBy: string, timezone: \DateTimeZone, hidden?: bool} $displayData
 */

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

$id = preg_replace('/[^a-z0-9_-]/i', '', (string) $displayData['id']);
$timezone = $displayData['timezone'];
$text = static fn (mixed $value): string => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
$date = static fn (mixed $utc): string => $utc === null || $utc === '' || str_starts_with((string) $utc, '0000') ? '' : sprintf(
    '<span title="%s">%s</span>',
    $text(Text::sprintf('COM_BREEZINGFORMSNG_TIMEZONE_WITH_OFFSET', $timezone->getName(), HTMLHelper::_('date', (string) $utc, 'P'))),
    HTMLHelper::_('date', (string) $utc, Text::_('DATE_FORMAT_LC6')),
);
$line = static function (mixed $utc, mixed $author) use ($date, $text): string {
    $dateText = $date($utc);
    $authorText = $text($author);

    return $dateText !== '' && $authorText !== ''
        ? Text::sprintf('COM_BREEZINGFORMSNG_AUDIT_DATE_BY', $dateText, $authorText)
        : ($dateText . $authorText !== '' ? $dateText . $authorText : '-');
};
?>
<table class="table table-striped mt-4" id="<?= $id; ?>"<?= !empty($displayData['hidden']) ? ' hidden' : ''; ?>>
    <tbody><tr class="table-primary"><th colspan="2">
        <a href="#" id="<?= $id; ?>-toggle" class="bf-collapse-toggle collapsed d-block text-decoration-none text-reset"
            data-bs-toggle="collapse" data-bs-target="#<?= $id; ?>-content"
            aria-expanded="false" aria-controls="<?= $id; ?>-content">
            <span class="icon-chevron-down bf-collapse-icon" aria-hidden="true"></span> <?= Text::_('COM_BREEZINGFORMSNG_RECORDS_AUDITINFO'); ?>
        </a>
    </th></tr></tbody>
    <tbody id="<?= $id; ?>-content" class="collapse">
        <tr><th class="w-25"><?= Text::_('COM_BREEZINGFORMSNG_TEST_CREATED'); ?></th><td><?= $line($displayData['created'], $displayData['createdBy']); ?></td></tr>
        <tr><th><?= Text::_('COM_BREEZINGFORMSNG_FORMS_MODIFIED'); ?></th><td><?= $line($displayData['modified'], $displayData['modifiedBy']); ?></td></tr>
    </tbody>
</table>
