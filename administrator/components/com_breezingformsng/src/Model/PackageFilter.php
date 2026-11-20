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
    /**
     * Value of the "No package" option of the filter, reserved: it selects the
     * items whose package is empty.
     */
    public const NONE = '__none__';

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

        $query->where($db->quoteName($column) . ' = :package')
            ->bind(':package', $package);
    }
}
