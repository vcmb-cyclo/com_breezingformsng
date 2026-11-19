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

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Administrator\Service;

\defined('_JEXEC') or die;

/**
 * The parts of the installation summary telling what the standard library
 * import did: created items, and items replaced by the standard version.
 */
final class LibraryImportSummary
{
    private const KEYS = [
        'createdScripts' => 'COM_BREEZINGFORMSNG_INSTALL_STANDARD_LIBRARY_CREATED_SCRIPTS',
        'createdPieces' => 'COM_BREEZINGFORMSNG_INSTALL_STANDARD_LIBRARY_CREATED_PIECES',
        'updatedScripts' => 'COM_BREEZINGFORMSNG_INSTALL_STANDARD_LIBRARY_REPLACED_SCRIPTS',
        'updatedPieces' => 'COM_BREEZINGFORMSNG_INSTALL_STANDARD_LIBRARY_REPLACED_PIECES',
    ];

    /**
     * The sentences in English, used when the language files of the version being installed are not read yet.
     * The format of each one is the format of its language key.
     */
    public const FALLBACKS = [
        'COM_BREEZINGFORMSNG_INSTALL_STANDARD_LIBRARY_CREATED_SCRIPTS' => 'Created scripts (%d): %s',
        'COM_BREEZINGFORMSNG_INSTALL_STANDARD_LIBRARY_CREATED_PIECES' => 'Created pieces (%d): %s',
        'COM_BREEZINGFORMSNG_INSTALL_STANDARD_LIBRARY_REPLACED_SCRIPTS'
            => 'Replaced by the standard version, previous code kept in the history of each script (Versions) (%d): %s',
        'COM_BREEZINGFORMSNG_INSTALL_STANDARD_LIBRARY_REPLACED_PIECES'
            => 'Replaced by the standard version, previous code kept in the history of each piece (Versions) (%d): %s',
        'COM_BREEZINGFORMSNG_INSTALL_STANDARD_LIBRARY_CHANGES' => 'Standard library changes: %s.',
    ];

    /**
     * @param callable(string, int, string): string $translate Language key, count and names to a sentence
     *
     * @return list<string> Nothing when the import changed nothing
     */
    public static function parts(object $importer, callable $translate): array
    {
        $parts = [];

        foreach (self::KEYS as $property => $key) {
            $names = self::names($importer->$property ?? []);

            if ($names !== []) {
                $parts[] = $translate($key, \count($names), implode(', ', $names));
            }
        }

        return $parts;
    }

    /**
     * @return list<string>
     */
    private static function names(mixed $items): array
    {
        if (!\is_array($items)) {
            return [];
        }

        $items = array_values(array_unique(array_filter(
            array_map(static fn(mixed $item): string => trim((string) $item), $items),
            static fn(string $item): bool => $item !== ''
        )));
        sort($items, SORT_NATURAL | SORT_FLAG_CASE);

        return $items;
    }
}
