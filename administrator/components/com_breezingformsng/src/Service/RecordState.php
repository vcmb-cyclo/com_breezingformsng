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
        self::PUBLISHED => 'JPUBLISHED',
        self::ARCHIVED  => 'JARCHIVED',
        self::TRASHED   => 'JTRASHED',
    ];

    public static function isValid(int $state): bool
    {
        return in_array($state, self::STATES, true);
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
