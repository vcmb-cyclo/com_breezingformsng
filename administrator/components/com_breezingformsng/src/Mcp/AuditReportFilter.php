<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Vcmb\Component\BreezingformsNG\Administrator\Mcp;

\defined('_JEXEC') or die;

/**
 * Turns the report of the database audit into what an AI client may receive.
 *
 * The About screen shows the whole report to an administrator. A client passes
 * what it reads to a model, so the report keeps the identifiers and codes a
 * diagnosis needs and nothing else: the values of the parameters of a menu
 * link are masked, paths are relative to the site, the detail of a failure is
 * left out, and every list is cut, at any depth, with its total kept.
 */
final class AuditReportFilter
{
    /** Items kept per list, whatever its depth. */
    public const MAX_ITEMS = 50;

    /** Lists of the report, in the order the About screen shows them. */
    public const LISTS = [
        'errors', 'missing_tables', 'missing_columns', 'unexpected_tables', 'collation_issues', 'column_collation_issues',
        'duplicate_indexes', 'orphan_checks', 'duplicate_forms', 'debug_forms', 'trashed_records', 'menu_issues',
        'extension_duplicates', 'extension_legacy', 'stale_language_files', 'stale_installer_temp_dirs',
    ];

    /** Parameters of a menu link whose value says what the link is, not what it holds. */
    private const LINK_KEYS = ['option', 'view', 'layout', 'task', 'Itemid'];

    /** Keys of an item that are never returned: a repair token is a secret of the screen. */
    private const DROPPED_KEYS = ['token'];

    private const MASK = '***';

    /**
     * @param array<string, mixed> $report The report of DatabaseAuditService.
     * @param string               $root   The root folder of the site, whose path is not given.
     * @return array<string, mixed>
     */
    public static function filter(array $report, string $root): array
    {
        $result = [
            'generated_at' => (string) ($report['generated_at'] ?? ''),
            'target_collation' => (string) ($report['target_collation'] ?? ''),
            'summary' => (array) ($report['summary'] ?? []),
        ];
        $truncated = [];

        foreach (self::LISTS as $list) {
            $items = array_values((array) ($report[$list] ?? []));
            $items = array_map(static fn (mixed $item): mixed => self::clean($list, $item, $root), $items);
            $result[$list] = self::bound($items, $list, $truncated);
        }
        $result['truncated'] = array_keys($truncated);

        return $result;
    }

    /**
     * @param array<array-key, mixed>|string|int|float|bool|null $item
     */
    private static function clean(string $list, mixed $item, string $root): mixed
    {
        if (is_array($item)) {
            $clean = [];
            foreach ($item as $key => $value) {
                if (in_array($key, self::DROPPED_KEYS, true)) {
                    continue;
                }
                $clean[$key] = $key === 'link' && is_string($value)
                    ? self::link($value)
                    : self::clean($list, $value, $root);
            }

            return $clean;
        }

        if (!is_string($item)) {
            return $item;
        }

        return match ($list) {
            // A folder of the temporary path: its name says what it is, its place does not matter.
            'stale_installer_temp_dirs' => basename(str_replace('\\', '/', $item)),
            // The text before the first colon names the step that failed; the rest quotes the server.
            'errors' => mb_substr(explode(': ', $item, 2)[0], 0, 120),
            default => self::relative($item, $root),
        };
    }

    private static function relative(string $text, string $root): string
    {
        $root = rtrim(str_replace('\\', '/', $root), '/');
        $text = str_replace('\\', '/', $text);

        return $root === '' ? $text : str_replace($root, '', $text);
    }

    /**
     * Keeps the route of a link and the names of its parameters; the value of a
     * parameter is kept only when it says which screen the link opens.
     */
    private static function link(string $link): string
    {
        [$path, $query] = array_pad(explode('?', explode('#', $link, 2)[0], 2), 2, '');

        if (preg_match('#^[a-z][a-z0-9+.-]*://#i', $path) === 1) {
            $path = '[external]';
        }

        parse_str($query, $parameters);
        $pairs = [];
        foreach ($parameters as $name => $value) {
            $safe = in_array((string) $name, self::LINK_KEYS, true)
                && is_string($value)
                && preg_match('/^[A-Za-z0-9_.-]{1,40}$/', $value) === 1;
            $pairs[] = rawurlencode((string) $name) . '=' . ($safe ? $value : self::MASK);
        }

        return $path . ($pairs === [] ? '' : '?' . implode('&', $pairs));
    }

    /**
     * Cuts every list to MAX_ITEMS, at any depth. A cut list is named in
     * $truncated by its path (a list inside the items of another one is
     * "duplicate_forms[].drop"), and the list that holds it gets "<key>_total".
     *
     * @param array<string, true> $truncated
     */
    private static function bound(mixed $value, string $path, array &$truncated): mixed
    {
        if (!is_array($value)) {
            return $value;
        }

        if (array_is_list($value)) {
            if (count($value) > self::MAX_ITEMS) {
                $value = array_slice($value, 0, self::MAX_ITEMS);
                $truncated[$path] = true;
            }

            // Loops, not an arrow function: it would take $truncated by value and lose what is noted in it.
            foreach ($value as $index => $item) {
                $value[$index] = self::bound($item, $path . '[]', $truncated);
            }

            return $value;
        }

        $bounded = [];
        foreach ($value as $key => $item) {
            $bounded[$key] = self::bound($item, $path . '.' . $key, $truncated);
            if (is_array($item) && array_is_list($item) && count($item) > self::MAX_ITEMS) {
                $bounded[$key . '_total'] = count($item);
            }
        }

        return $bounded;
    }
}
