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

use stdClass;
use Vcmb\Component\BreezingformsNG\Site\Service\Widget\WidgetOptions;

/**
 * Rules of a field moved inside its page or section. The tree is read with objects,
 * so that writing it back keeps its empty objects. Only the moved range changes:
 * element ids and every other property stay as they are.
 */
final class FieldOrder
{
    /** The value of an element without a position of its own. */
    private const NO_POSITION = -1;

    /** Moves saved at once. */
    private const MOST_MOVES = 200;

    /**
     * Puts a field before or after another field of the same page or section.
     *
     * @return bool False when the move is impossible or changes nothing; the tree is then untouched.
     */
    public static function move(stdClass $tree, string $field, string $target, bool $after): bool
    {
        $children = is_array($tree->children ?? null) ? array_values($tree->children) : [];
        $from = self::indexOf($children, $field);
        $to = self::indexOf($children, $target);

        if ($from !== null && $to !== null) {
            $order = $children;
            $moved = array_splice($order, $from, 1);
            $to = (int) self::indexOf($order, $target) + ($after ? 1 : 0);
            array_splice($order, $to, 0, $moved);

            if ($order === $children) {
                return false;
            }

            $tree->children = $order;

            return true;
        }

        foreach ($children as $child) {
            if ($child instanceof stdClass && self::move($child, $field, $target, $after)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Reads the moves posted by the debug bar.
     *
     * @return list<array{field: string, target: string, after: bool}>|null Null when one of them cannot be read.
     */
    public static function moves(string $json): ?array
    {
        $posted = json_decode($json, true);

        if (!is_array($posted) || !array_is_list($posted) || count($posted) > self::MOST_MOVES) {
            return null;
        }

        $moves = [];

        foreach ($posted as $move) {
            $field = $move['field'] ?? null;
            $target = $move['target'] ?? null;
            $position = $move['position'] ?? null;

            if (!is_string($field) || !is_string($target) || !in_array($position, ['before', 'after'], true)) {
                return null;
            }

            $moves[] = ['field' => $field, 'target' => $target, 'after' => $position === 'after'];
        }

        return $moves;
    }

    /**
     * Applies moves one after the other.
     *
     * @param list<array{field: string, target: string, after: bool}> $moves
     *
     * @return bool False as soon as one is impossible or changes nothing; the tree is then not to be saved.
     */
    public static function moveAll(stdClass $tree, array $moves): bool
    {
        foreach ($moves as $move) {
            if (!self::move($tree, $move['field'], $move['target'], $move['after'])) {
                return false;
            }
        }

        return true;
    }

    /** @return list<string> Names of the fields, in the order of the form. */
    public static function names(stdClass $node): array
    {
        $names = [];
        $name = self::name($node);

        if ($name !== null) {
            $names[] = $name;
        }

        foreach (is_array($node->children ?? null) ? $node->children : [] as $child) {
            if ($child instanceof stdClass) {
                array_push($names, ...self::names($child));
            }
        }

        return $names;
    }

    /**
     * Hands the positions of the former order to the fields in their new order.
     *
     * @param array<string, mixed> $positions Position of each field that has one.
     * @param list<string>         $former
     * @param list<string>         $new
     *
     * @return array<string, mixed>
     */
    public static function reassign(array $positions, array $former, array $new): array
    {
        $has = static fn (string $name): bool => array_key_exists($name, $positions);
        $position = static fn (string $name): mixed => $positions[$name];
        $slots = array_map($position, array_values(array_filter($former, $has)));
        $names = array_values(array_filter($new, $has));

        return count($names) === count($slots) ? array_combine($names, $slots) : $positions;
    }

    /**
     * Moves the position numbers written in the tree along with the fields.
     *
     * @param list<string> $former Names in the order before the move.
     */
    public static function renumber(stdClass $tree, array $former): void
    {
        $elements = self::elements($tree);
        $positions = [];

        foreach ($elements as $name => $properties) {
            if (isset($properties->orderNumber) && (int) $properties->orderNumber !== self::NO_POSITION) {
                $positions[$name] = $properties->orderNumber;
            }
        }

        foreach (self::reassign($positions, $former, self::names($tree)) as $name => $position) {
            $elements[$name]->orderNumber = $position;
        }
    }

    /**
     * The element list of template_areas, in the new order. Returned as it is when it
     * does not hold such a list.
     *
     * @param list<string> $former
     * @param list<string> $new
     */
    public static function areas(string $json, array $former, array $new): string
    {
        $areas = json_decode($json);
        $list = is_array($areas) && ($areas[0] ?? null) instanceof stdClass ? ($areas[0]->elements ?? null) : null;

        if (!is_array($list)) {
            return $json;
        }

        $entries = [];
        $slots = [];

        foreach ($list as $index => $entry) {
            if ($entry instanceof stdClass && in_array($entry->name ?? null, $former, true)) {
                $entries[(string) $entry->name] = $entry;
                $slots[$index] = $entry->orderNumber ?? null;
            }
        }

        $names = array_values(array_filter($new, static fn (string $name): bool => isset($entries[$name])));

        if (count($names) !== count($slots)) {
            return $json;
        }

        foreach (array_keys($slots) as $position => $index) {
            $entry = clone $entries[$names[$position]];

            if ($slots[$index] !== null) {
                $entry->orderNumber = $slots[$index];
            }

            $list[$index] = $entry;
        }

        $areas[0]->elements = $list;

        return (string) json_encode($areas);
    }

    /**
     * The element list of template_areas with the changed texts and validation of one field.
     * Translations live in the structure only.
     *
     * @param list<string> $changed Names of the changed properties.
     */
    public static function editArea(string $json, string $field, stdClass $properties, array $changed): string
    {
        $areas = json_decode($json);
        $list = is_array($areas) && ($areas[0] ?? null) instanceof stdClass ? ($areas[0]->elements ?? null) : null;
        $keys = [
            'label' => 'title',
            'validationCondition' => 'script3cond',
            'validationId' => 'script3id',
            'validationCode' => 'script3code',
            'validationMessage' => 'script3msg',
            'validationFunctionName' => 'functionNameScript3',
        ];
        $found = false;

        foreach (is_array($list) ? $list : [] as $entry) {
            if (!$entry instanceof stdClass || ($entry->name ?? null) !== $field) {
                continue;
            }

            foreach (array_intersect_key($keys, array_flip($changed)) as $property => $key) {
                $entry->{$key} = $properties->{$property};
                $found = true;
            }

            $options = $entry->options ?? null;

            if (!$options instanceof stdClass) {
                continue;
            }

            foreach (array_intersect(['hint', 'maxLength', 'maxlength'], $changed) as $key) {
                $options->{$key} = $properties->{$key};
                $found = true;
            }

            // A widget keeps its settings, bounds included, for the check of a sent form.
            if (
                isset($options->widget, $options->widgetOptions)
                && array_intersect(['min', 'max', 'widgetMin', 'widgetMax'], $changed) !== []
            ) {
                $options->widgetOptions = WidgetOptions::options((string) $options->widget, (array) $properties);
                $found = true;
            }
        }

        return $found ? (string) json_encode($areas) : $json;
    }

    /** @return array<string, stdClass> Properties of each field, by name. */
    public static function elements(stdClass $node): array
    {
        $name = self::name($node);
        $elements = $name === null ? [] : [$name => $node->properties];

        foreach (is_array($node->children ?? null) ? $node->children : [] as $child) {
            if ($child instanceof stdClass) {
                $elements += self::elements($child);
            }
        }

        return $elements;
    }

    private static function name(mixed $node): ?string
    {
        if (!$node instanceof stdClass || !($node->properties ?? null) instanceof stdClass) {
            return null;
        }

        $properties = $node->properties;

        return ($properties->type ?? '') === 'element' && is_scalar($properties->bfName ?? null)
            ? (string) $properties->bfName
            : null;
    }

    /** @param array<mixed> $nodes */
    private static function indexOf(array $nodes, string $name): ?int
    {
        foreach (array_values($nodes) as $index => $node) {
            if (self::name($node) === $name) {
                return $index;
            }
        }

        return null;
    }
}
