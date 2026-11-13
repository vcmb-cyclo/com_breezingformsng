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

namespace Vcmb\Component\BreezingformsNG\Administrator\Service;

\defined('_JEXEC') or die;

/** Prevent spreadsheet applications from evaluating untrusted CSV cells. */
final class CsvCellSanitizer
{
    public static function neutralizeFormula(string $value): string
    {
        $candidate = ltrim($value, " \t\n\r\0\x0B\x0C");

        if (preg_match('/^[\t\r\n]/', $value) === 1) {
            return "'" . $value;
        }

        if (preg_match('/^[=+\-@]/', $candidate) !== 1) {
            return $value;
        }

        // Keep ordinary signed numbers numeric when opened from CSV.
        if (preg_match('/^[+-]?(?:\d+(?:\.\d*)?|\.\d+)(?:[Ee][+-]?\d+)?$/D', $candidate) === 1) {
            return $value;
        }

        return "'" . $value;
    }
}
