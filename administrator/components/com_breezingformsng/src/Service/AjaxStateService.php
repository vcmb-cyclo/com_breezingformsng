<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Vcmb\Component\BreezingformsNG\Administrator\Service;

\defined('_JEXEC') or die;

final class AjaxStateService
{
    private const RECORD_COLUMNS = ['viewed', 'exported'];

    public static function normalizeState(int $state): int
    {
        return $state > 0 ? 1 : 0;
    }

    public static function normalizeRecordColumn(string $column): ?string
    {
        $column = preg_replace('/^bfrecord_/', '', $column);

        return in_array($column, self::RECORD_COLUMNS, true) ? $column : null;
    }

    public static function success(int $state): array
    {
        return ['Result' => 'OK', 'State' => self::normalizeState($state)];
    }

    public static function error(string $message): array
    {
        return ['Result' => 'ERROR', 'Message' => $message];
    }
}
