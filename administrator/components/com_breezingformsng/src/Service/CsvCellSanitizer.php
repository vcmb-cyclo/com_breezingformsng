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

        // An international phone number ("+33 6 12 34 56 78", "+33 (0)6.12.34.56.78"): a plus, digits,
        // spaces, dots and at most one parenthesised group of digits. No operator, no letter: nothing
        // a spreadsheet could evaluate. A number written with hyphens or slashes is neutralised.
        if (preg_match('/^\+\d[\d\s.]*(?:\(\d+\)[\d\s.]*)?$/D', $candidate) === 1) {
            return $value;
        }

        return "'" . $value;
    }
}
