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
}
