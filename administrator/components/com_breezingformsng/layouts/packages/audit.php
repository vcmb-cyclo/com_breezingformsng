<?php

/**
 * BreezingForms NG - A Joomla Forms Application
 *
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 *
 * SPDX-License-Identifier: GPL-2.0-or-later
 **/

\defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

/**
 * Audit trail (created, modified, and by whom) of the packages, in a collapsed accordion, as on
 * the record screen. Each package has its two rows; only those of `selected` show, so a screen
 * that changes the package without reloading (export) shows the right ones with the attribute
 * data-bf-audit.
 *
 * - profiles: the audit fields of each package, by package id.
 * - selected: the package shown, '' for none.
 * - timezone: time zone of the dates, shown in the tooltip.
 *
 * @var array{profiles: array<string, array<string, mixed>>, selected: string, timezone: \DateTimeZone} $displayData
 */

$timezone = $displayData['timezone'];
$escape = static fn (mixed $text): string => htmlspecialchars((string) ($text ?? ''), ENT_QUOTES, 'UTF-8');
// Local time, with the time zone in the tooltip.
$date = static fn (mixed $utc): string => $utc === null || $utc === '' ? '' : sprintf(
    '<span title="%s">%s</span>',
    $escape(Text::sprintf('COM_BREEZINGFORMSNG_TIMEZONE_WITH_OFFSET', $timezone->getName(), HTMLHelper::_('date', (string) $utc, 'P'))),
    HTMLHelper::_('date', (string) $utc, Text::_('DATE_FORMAT_LC6')),
);
$stamp = static function (mixed $when, mixed $who) use ($date, $escape): string {
    $dateText = $date($when);
    $authorText = $escape($who);

    return $dateText !== '' && $authorText !== ''
        ? Text::sprintf('COM_BREEZINGFORMSNG_AUDIT_DATE_BY', $dateText, $authorText)
        : $dateText . $authorText;
};
$selected = $displayData['selected'];
?>
<table class="table table-striped mt-4<?= isset($displayData['profiles'][$selected]) ? '' : ' d-none'; ?>" id="bf-package-audit">
    <tbody><tr class="table-primary"><th colspan="2">
        <a href="#" id="bf-package-audit-toggle" class="bf-collapse-toggle collapsed d-block text-decoration-none text-reset"
            data-bs-toggle="collapse" data-bs-target="#bf-package-audit-content"
            aria-expanded="false" aria-controls="bf-package-audit-content">
            <span class="icon-chevron-down bf-collapse-icon" aria-hidden="true"></span> <?= Text::_('COM_BREEZINGFORMSNG_RECORDS_AUDITINFO'); ?>
        </a>
    </th></tr></tbody>
    <tbody id="bf-package-audit-content" class="collapse">
        <?php foreach ($displayData['profiles'] as $id => $profile) : ?>
            <?php $hidden = (string) $id === $selected ? '' : ' class="d-none"'; ?>
            <tr<?= $hidden; ?> data-bf-audit="<?= $escape($id); ?>"><th class="w-25"><?= Text::_('COM_BREEZINGFORMSNG_TEST_CREATED'); ?></th><td><?= $stamp($profile['created'] ?? null, $profile['created_by'] ?? ''); ?></td></tr>
            <tr<?= $hidden; ?> data-bf-audit="<?= $escape($id); ?>"><th><?= Text::_('COM_BREEZINGFORMSNG_FORMS_MODIFIED'); ?></th><td><?= $stamp($profile['modified'] ?? null, $profile['modified_by'] ?? ''); ?></td></tr>
        <?php endforeach; ?>
    </tbody>
</table>
