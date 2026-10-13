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

declare(strict_types=1);

/**
 * Minimal stub for Joomla\CMS\Language\Text, for renderer characterization
 * tests only. Returns the translation key unchanged - the same fallback
 * Joomla's own Text::_() uses for a key with no loaded translation - so
 * snapshots stay stable and legible without needing the language framework
 * loaded. Shared across renderer characterization test files; each one
 * require_once's this conditionally, so it only gets declared once.
 */

namespace Joomla\CMS\Language;

if (!class_exists(Text::class, false)) {
    final class Text
    {
        public static function _(string $key): string
        {
            return $key;
        }
    }
}
