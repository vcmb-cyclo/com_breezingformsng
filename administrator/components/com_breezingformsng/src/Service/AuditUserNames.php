<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Administrator\Service;

\defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/** Resolves historical audit usernames and numeric user IDs for display. */
final class AuditUserNames
{
    /**
     * Resolve existing Joomla user IDs for historical usernames.
     *
     * @param list<string> $usernames
     * @return array<string, int>
     */
    public static function resolveUserIds(DatabaseInterface $db, array $usernames): array
    {
        $usernames = array_values(array_unique(array_filter($usernames, static fn (string $username): bool => $username !== '')));

        if ($usernames === []) {
            return [];
        }

        $query = $db->createQuery()
            ->select($db->quoteName(['username', 'id']))
            ->from($db->quoteName('#__users'))
            ->whereIn($db->quoteName('username'), $usernames, ParameterType::STRING);
        $users = $db->setQuery($query)->loadAssocList('username') ?: [];
        $result = [];

        foreach ($users as $username => $user) {
            $result[(string) $username] = (int) $user['id'];
        }

        return $result;
    }

    /**
     * Exact usernames take precedence. Numeric values are treated as user IDs
     * only when the caller knows its field stores IDs.
     *
     * @param list<string> $values
     * @return array<string, string>
     */
    public static function resolve(DatabaseInterface $db, array $values, bool $numericValuesAreIds = false): array
    {
        $values = array_values(array_unique(array_filter($values, static fn (string $value): bool => $value !== '')));
        $result = array_combine($values, $values);

        if ($values === []) {
            return $result;
        }

        $query = $db->createQuery()
            ->select($db->quoteName(['username', 'name']))
            ->from($db->quoteName('#__users'))
            ->whereIn($db->quoteName('username'), $values, ParameterType::STRING);
        $names = $db->setQuery($query)->loadAssocList('username') ?: [];
        $ids = [];

        foreach ($values as $value) {
            if (isset($names[$value])) {
                $result[$value] = (string) $names[$value]['name'];
                continue;
            }

            if (!$numericValuesAreIds) {
                continue;
            }

            $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($id !== false) {
                $ids[$value] = $id;
            }
        }

        if ($ids !== []) {
            $query = $db->createQuery()
                ->select($db->quoteName(['id', 'name']))
                ->from($db->quoteName('#__users'))
                ->whereIn($db->quoteName('id'), array_values(array_unique($ids)), ParameterType::INTEGER);
            $users = $db->setQuery($query)->loadAssocList('id') ?: [];

            foreach ($ids as $value => $id) {
                if (isset($users[$id])) {
                    $result[$value] = (string) $users[$id]['name'];
                }
            }
        }

        return $result;
    }
}
