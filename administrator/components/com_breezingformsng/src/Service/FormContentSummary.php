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

/**
 * The structure of a form as flat readable lines, kept in each version so that the Joomla comparison
 * can show what changed in
 * the fields: the structure itself is stored encoded, which cannot be read.
 *
 * A field, section or page is one line, so that adding or removing it stays
 * short; the settings of the form itself have a line each. Settings left empty,
 * off or at their usual value are left out. A "content" line lists what a
 * container holds, so that a move shows without renumbering every field.
 */
final class FormContentSummary
{
    /** Name of the summary in the version data: not a column of the form. */
    public const FIELD = 'form_content';

    /** Settings which name the node or are internal to the storage. */
    private const SKIPPED = ['type', 'bfType', 'bfName', 'name', 'dbId', 'pageNumber'];

    /** The value the editor gives to a new field: not worth reading. */
    private const USUAL = ['labelPosition' => 'left', 'logging' => true, 'orderNumber' => '-1', 'tabIndex' => '-1'];

    /** Groups of settings which are unused while their condition is 0. */
    private const CONDITIONAL = ['validation', 'init', 'action'];

    /**
     * @return array<string, string> Empty when the code holds no readable structure
     */
    public static function fromTemplateCode(string $templateCode): array
    {
        $code = base64_decode($templateCode, true);
        $structure = \is_string($code) ? json_decode($code, true) : null;

        if (!\is_array($structure)) {
            return [];
        }

        $lines = [];
        $names = [];
        self::describe($structure, self::name($structure, $names), $lines, $names);

        return $lines;
    }

    /**
     * @param array<array-key, mixed> $node
     * @param array<string, string>   $lines
     * @param array<string, int>      $names Names already given, with their count
     */
    private static function describe(array $node, string $name, array &$lines, array &$names): void
    {
        $properties = \is_array($node['properties'] ?? null) ? $node['properties'] : [];

        if (($properties['type'] ?? '') === 'root') {
            // The form is never added nor removed: one line for each of its settings.
            foreach (['name' => $properties['name'] ?? ''] + self::settings($properties) as $setting => $value) {
                if ($value !== '') {
                    $lines[$name . ' · ' . $setting] = self::text($value);
                }
            }
        } else {
            $parts = array_filter([(string) ($properties['bfType'] ?? '')]);

            foreach (self::settings($properties) as $setting => $value) {
                $parts[] = $setting . ': ' . self::text($value);
            }

            if ($parts !== []) {
                $lines[$name] = implode(' | ', $parts);
            }
        }

        $children = [];

        foreach (\is_array($node['children'] ?? null) ? $node['children'] : [] as $child) {
            if (\is_array($child)) {
                $children[self::name($child, $names)] = $child;
            }
        }

        if ($children === []) {
            return;
        }

        $lines[$name . ' · content'] = implode(', ', array_keys($children));

        foreach ($children as $childName => $child) {
            self::describe($child, (string) $childName, $lines, $names);
        }
    }

    /**
     * The settings worth reading, the label first.
     *
     * @param array<array-key, mixed> $properties
     *
     * @return array<array-key, mixed>
     */
    private static function settings(array $properties): array
    {
        $settings = [];

        foreach ($properties as $setting => $value) {
            $setting = (string) $setting;

            if (
                \in_array($setting, self::SKIPPED, true)
                || \in_array($value, [null, '', [], false, 0], true)
                || (self::USUAL[$setting] ?? null) === $value
                || ($setting !== 'widget' && str_starts_with($setting, 'widget') && empty($properties['widget']))
            ) {
                continue;
            }

            foreach (self::CONDITIONAL as $group) {
                if (str_starts_with($setting, $group) && empty($properties[$group . 'Condition'])) {
                    continue 2;
                }
            }

            $settings[$setting] = $value;
        }

        $first = array_intersect_key($settings, ['label' => true, 'title' => true]);

        return $first + $settings;
    }

    private static function text(mixed $value): string
    {
        return \is_string($value)
            ? $value
            : (string) json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * The name a line starts with: the field or section name, the page number;
     * a number tells namesakes apart.
     *
     * @param array<array-key, mixed> $node
     * @param array<string, int>      $names
     */
    private static function name(array $node, array &$names): string
    {
        $properties = \is_array($node['properties'] ?? null) ? $node['properties'] : [];
        $type = (string) ($properties['type'] ?? '');

        $name = match ($type) {
            'root' => 'form',
            'page' => 'page ' . (int) ($properties['pageNumber'] ?? 0),
            'element' => (string) ($properties['bfName'] ?? ''),
            default => (string) ($properties['name'] ?? ''),
        };

        if ($name === '') {
            $name = $type !== '' ? $type : 'item';
        }

        $names[$name] = ($names[$name] ?? 0) + 1;

        return $names[$name] > 1 ? $name . ' (' . $names[$name] . ')' : $name;
    }
}
