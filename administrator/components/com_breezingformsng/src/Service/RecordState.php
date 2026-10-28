<?php
/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Vcmb\Component\BreezingformsNG\Administrator\Service;

\defined('_JEXEC') or die;

/**
 * Publication states of a submitted record, with the values Joomla uses for
 * its own content. A submission is never unpublished, so state 0 is not used.
 */
final class RecordState
{
    public const PUBLISHED = 1;
    public const ARCHIVED = 2;
    public const TRASHED = -2;

    public const STATES = [self::PUBLISHED, self::ARCHIVED, self::TRASHED];

    public const FILTER_ALL = '*';

    private const LABELS = [
        self::PUBLISHED => 'COM_BREEZINGFORMSNG_RECORDS_STATE_ACTIVE',
        self::ARCHIVED  => 'JARCHIVED',
        self::TRASHED   => 'JTRASHED',
    ];

    public static function isValid(int $state): bool
    {
        return in_array($state, self::STATES, true);
    }

    /**
     * State of an imported record. A file written for the former `archived`
     * flag (0/1) has no `state` column: its archived records stay archived.
     * This reading of older files is intended.
     */
    public static function fromImport(mixed $state, mixed $archived): int
    {
        if ($state !== null) {
            return is_numeric($state) && self::isValid((int) $state) ? (int) $state : self::PUBLISHED;
        }

        return (bool) $archived ? self::ARCHIVED : self::PUBLISHED;
    }

    /**
     * Language key of the reason a selection cannot be deleted for good, or
     * null when it can: every record has to exist and to be in the trash. The
     * whole selection is refused, whatever the order of its records.
     *
     * @param int[]           $ids    Selected record ids.
     * @param array<int, int> $states State by id of the records that exist.
     */
    public static function deletionRefusal(array $ids, array $states): ?string
    {
        foreach ($ids as $id) {
            if (!isset($states[$id])) {
                return 'JLIB_APPLICATION_ERROR_NOT_EXIST';
            }
        }

        foreach ($ids as $id) {
            if ($states[$id] !== self::TRASHED) {
                return 'JLIB_APPLICATION_ERROR_DELETE_NOT_PERMITTED';
            }
        }

        return null;
    }

    public static function label(int $state): string
    {
        return self::LABELS[$state] ?? self::LABELS[self::PUBLISHED];
    }

    /**
     * Reduces a list filter value to '' (no filter), '*' (every state) or one
     * of the states as a string.
     */
    public static function normalizeFilter(string $filter): string
    {
        $filter = trim($filter);

        if ($filter === self::FILTER_ALL) {
            return $filter;
        }

        return is_numeric($filter) && self::isValid((int) $filter) ? (string) (int) $filter : '';
    }

    /**
     * States a list filter keeps, or null when it keeps them all. Without a
     * filter, archived and trashed records are hidden, as in Joomla lists.
     *
     * @return int[]|null
     */
    public static function filterStates(string $filter): ?array
    {
        $filter = self::normalizeFilter($filter);

        if ($filter === self::FILTER_ALL) {
            return null;
        }

        return [$filter === '' ? self::PUBLISHED : (int) $filter];
    }
}
