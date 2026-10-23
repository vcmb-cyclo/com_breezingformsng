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

use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/**
 * The version of origin of an item: its state before its first versioned
 * change, kept forever so that the history limit never deletes it.
 */
final class VersionOrigin
{
    /**
     * @param list<int> $ids
     *
     * @return list<int> The ids of the items without any version
     */
    public static function missing(DatabaseInterface $db, string $typeAlias, array $ids): array
    {
        $ids = array_values(array_unique(array_filter($ids, static fn(int $id): bool => $id > 0)));

        if ($ids === []) {
            return [];
        }

        $versioned = $db->setQuery(
            $db->createQuery()
                ->select('DISTINCT ' . $db->quoteName('item_id'))
                ->from($db->quoteName('#__history'))
                ->whereIn(
                    $db->quoteName('item_id'),
                    array_map(static fn(int $id): string => $typeAlias . '.' . $id, $ids),
                    ParameterType::STRING
                )
        )->loadColumn();

        return array_values(array_filter(
            $ids,
            static fn(int $id): bool => !\in_array($typeAlias . '.' . $id, $versioned, true)
        ));
    }

    /**
     * Keeps the current version of the item forever.
     */
    public static function keepForever(DatabaseInterface $db, string $itemId): void
    {
        $db->setQuery(
            $db->createQuery()
                ->update($db->quoteName('#__history'))
                ->set($db->quoteName('keep_forever') . ' = 1')
                ->where($db->quoteName('item_id') . ' = :itemId')
                ->where($db->quoteName('is_current') . ' = 1')
                ->bind(':itemId', $itemId)
        )->execute();
    }
}
