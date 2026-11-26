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

namespace Vcmb\Component\BreezingformsNG\Site\Service\Rendering\Debug;

/**
 * Rules of the frontend debug bar: who sees it, which theme it may apply and
 * which fields it lists. Field values are never read.
 */
final class FormDebugContext
{
    public const THEME_PARAM = 'bf_debug_theme';

    /**
     * The bar needs both the debug mode of the form and a Super User on the site.
     *
     * @param mixed $debugMode The debug_mode column of the form.
     */
    public static function isActive(mixed $debugMode, bool $siteClient, bool $superUser): bool
    {
        return (int) $debugMode === 1 && $siteClient && $superUser;
    }

    /**
     * @return list<string> Theme folders of the given directory that have a stylesheet.
     */
    public static function installedThemes(string $root): array
    {
        $themes = [];

        foreach (glob($root . '/*/theme.css') ?: [] as $stylesheet) {
            $name = basename(dirname($stylesheet));

            if (preg_match('/^[A-Za-z0-9_-]+$/D', $name) === 1) {
                $themes[] = $name;
            }
        }

        natcasesort($themes);

        return array_values($themes);
    }

    /**
     * @param list<string> $installed
     *
     * @return string|null The requested theme when it is installed, null otherwise.
     */
    public static function requestedTheme(string $requested, array $installed): ?string
    {
        return in_array($requested, $installed, true) ? $requested : null;
    }

    /**
     * @param array<mixed> $node A node of the QuickMode form tree.
     *
     * @return list<array{name: string, type: string, label: string, required: bool}>
     */
    public static function fields(array $node): array
    {
        $fields = [];
        $properties = is_array($node['properties'] ?? null) ? $node['properties'] : [];

        if (($properties['type'] ?? '') === 'element') {
            $fields[] = [
                'name' => self::text($properties['bfName'] ?? null),
                'type' => self::text($properties['bfType'] ?? null),
                'label' => self::text($properties['label'] ?? null),
                'required' => ($properties['required'] ?? false) === true,
            ];
        }

        foreach (is_array($node['children'] ?? null) ? $node['children'] : [] as $child) {
            if (is_array($child)) {
                array_push($fields, ...self::fields($child));
            }
        }

        return $fields;
    }

    private static function text(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }
}
