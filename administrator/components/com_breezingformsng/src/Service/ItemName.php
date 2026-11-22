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

/**
 * The name of a form, piece or script is an identifier: letters, digits and underscores, never empty.
 * The QuickMode editor and the item forms check it in the browser; the models check it here as well,
 * so that no other path (options screen, import, API) stores a name the frontends cannot use.
 */
final class ItemName
{
    /**
     * @return string|null The language key of the problem, null when the name is valid.
     */
    public static function problem(string $name, ?int $maxLength = null): ?string
    {
        if (trim($name) === '') {
            return 'COM_BREEZINGFORMSNG_ERROR_ENTER_NAME';
        }

        if (preg_match('/^[a-zA-Z0-9_]+$/D', $name) !== 1 || ($maxLength !== null && strlen($name) > $maxLength)) {
            return 'COM_BREEZINGFORMSNG_ERROR_ENTER_NAME_CHARACTERS';
        }

        return null;
    }

    /**
     * The rule applied when an item is saved: a stored name is never revalidated while it stays as it is, so
     * a legacy or imported name (hyphen, space, dot) does not make its item unsaveable. A new item, or a
     * changed name, must follow the rule. An empty name is always refused.
     *
     * @param string|null $storedName The name stored for the item being saved, null for a new item.
     * @return string|null The language key of the problem, null when the name may be saved.
     */
    public static function problemOnSave(string $name, ?string $storedName, ?int $maxLength = null): ?string
    {
        if ($storedName !== null && $name === $storedName && trim($name) !== '') {
            return null;
        }

        return self::problem($name, $maxLength);
    }
}
