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

namespace Vcmb\Component\BreezingformsNG\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;
use Joomla\Database\QueryInterface;

/**
 * The package filter of the lists of forms, menus, scripts and pieces.
 */
final class PackageFilter
{
    /** Actual package values are encoded in the selector, so they cannot collide with its controls. */
    private const VALUE_PREFIX = 'package:';

    /**
     * Value of the "No package" option of the filter, reserved: it selects the
     * items whose package is empty.
     */
    public const NONE = '__none__';

    /**
     * Encodes a real package identifier for use as the selector value. Every
     * identifier, including "__none__", stays distinct from the control values.
     */
    public static function encode(string $package): string
    {
        return self::VALUE_PREFIX . rtrim(strtr(base64_encode($package), '+/', '-_'), '=');
    }

    /**
     * Returns the real package represented by a selector value. Raw values stay
     * accepted for bookmarks and user state saved before package values were encoded.
     */
    public static function selectedPackage(string $value): string
    {
        if ($value === '' || $value === self::NONE || !str_starts_with($value, self::VALUE_PREFIX)) {
            return $value === self::NONE ? '' : $value;
        }

        $encoded = substr($value, strlen(self::VALUE_PREFIX));
        $padding = (4 - strlen($encoded) % 4) % 4;
        $decoded = base64_decode(strtr($encoded . str_repeat('=', $padding), '-_', '+/'), true);

        // A malformed prefixed raw identifier remains a usable legacy value.
        return is_string($decoded) && self::encode($decoded) === $value ? $decoded : $value;
    }

    /**
     * Restricts the query to one package, to the items without package, or not at all.
     *
     * @param string $column Allowlisted column of the query, never a user value.
     */
    public static function apply(DatabaseInterface $db, QueryInterface $query, string $column, string $package): void
    {
        if ($package === '') {
            return;
        }

        if ($package === self::NONE) {
            $query->where($db->quoteName($column) . ' = ' . $db->quote(''));

            return;
        }

        $package = self::selectedPackage($package);

        $query->where($db->quoteName($column) . ' = :package')
            ->bind(':package', $package);
    }
}
