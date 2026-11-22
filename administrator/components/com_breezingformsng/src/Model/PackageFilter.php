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
     *
     * One ambiguity remains: a raw package literally named like a valid
     * "package:<base64url>" value is read as the encoded one.
     */
    public static function selectedPackage(string $value): string
    {
        if ($value === self::NONE) {
            return '';
        }

        return self::decode($value) ?? $value;
    }

    /**
     * Returns the selector value that displays the given filter value: a legacy
     * raw package is encoded so that it matches the option of that package.
     */
    public static function toSelectorValue(string $value): string
    {
        if ($value === '' || $value === self::NONE || self::decode($value) !== null) {
            return $value;
        }

        return self::encode($value);
    }

    /** Decodes a valid, non-empty encoded value; null for anything else. */
    private static function decode(string $value): ?string
    {
        if (!str_starts_with($value, self::VALUE_PREFIX)) {
            return null;
        }

        $encoded = substr($value, strlen(self::VALUE_PREFIX));
        $padding = (4 - strlen($encoded) % 4) % 4;
        $decoded = base64_decode(strtr($encoded . str_repeat('=', $padding), '-_', '+/'), true);

        // A malformed or empty payload is a raw legacy value, never a package of ''.
        return is_string($decoded) && $decoded !== '' && self::encode($decoded) === $value ? $decoded : null;
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
