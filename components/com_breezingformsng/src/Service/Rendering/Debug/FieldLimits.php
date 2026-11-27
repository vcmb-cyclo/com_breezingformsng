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
use Vcmb\Component\BreezingformsNG\Site\Service\Widget\WidgetValueValidator;

/**
 * The limits of a field that the field window changes: the length of a text, the
 * bounds of a number, of a rating or of a date. An empty value means no limit.
 */
final class FieldLimits
{
    private const MAX_LENGTH = 999999;

    private const NOW = 'now';

    /** Stars count from 1 to a maximum in this range. */
    private const STARS = [3, 10];

    /** A scale starts in this range and holds this many steps at most. */
    private const SCALE_START = 100;

    private const SCALE_STEPS = 20;

    /**
     * @return list<array{key: string, name: string, input: string, value: string}> The limits
     *         of the field: its property, what it limits, the kind of input and the value.
     */
    public static function of(stdClass $properties): array
    {
        $kind = self::kind($properties);
        $limits = match ($kind) {
            'text' => [['maxLength', 'length', 'number']],
            'textarea' => [['maxlength', 'length', 'number']],
            'number', 'scale' => [['min', 'min', 'number'], ['max', 'max', 'number']],
            'stars' => [['max', 'max', 'number']],
            'date', 'time', 'datetime' => [['widgetMin', 'min', $kind], ['widgetMax', 'max', $kind]],
            default => [],
        };

        return array_map(static fn (array $limit): array => [
            'key' => $limit[0],
            'name' => $limit[1],
            'input' => $limit[2],
            'value' => self::text($properties, $limit[0]),
        ], $limits);
    }

    /**
     * Writes the given limits to the properties of a field.
     *
     * @param array<string, string> $changes Value by property.
     *
     * @return list<string>|null Names of the changed properties, null when a limit is refused.
     */
    public static function apply(stdClass $properties, array $changes): ?array
    {
        $known = array_column(self::of($properties), 'value', 'key');
        $changes = array_map('trim', $changes);

        if (array_diff_key($changes, $known) !== []) {
            return null;
        }

        $changed = array_keys(array_diff_assoc($changes, $known));

        // Limits stored by earlier versions are left alone until one of them changes.
        if ($changed === []) {
            return [];
        }

        if (!self::areValid(self::kind($properties), array_merge($known, $changes))) {
            return null;
        }

        foreach ($changed as $key) {
            $properties->{$key} = $changes[$key];
        }

        return $changed;
    }

    /** @param array<string, string> $values All the limits of the field. */
    private static function areValid(string $kind, array $values): bool
    {
        if ($kind === 'text' || $kind === 'textarea') {
            $length = (string) reset($values);

            return $length === '' || self::isInteger($length, 0, self::MAX_LENGTH);
        }

        if ($kind === 'stars') {
            return $values['max'] === '' || self::isInteger($values['max'], ...self::STARS);
        }

        if ($kind === 'scale') {
            $min = $values['min'] === '' ? 0 : (int) $values['min'];

            return ($values['min'] === '' || self::isInteger($values['min'], 0, self::SCALE_START))
                && ($values['max'] === '' || self::isInteger($values['max'], $min + 1, $min + self::SCALE_STEPS));
        }

        if ($kind === 'number') {
            foreach ($values as $value) {
                if ($value !== '' && preg_match('/^-?\d{1,15}(\.\d{1,10})?$/D', $value) !== 1) {
                    return false;
                }
            }

            return in_array('', $values, true) || (float) $values['min'] <= (float) $values['max'];
        }

        foreach ($values as $value) {
            if ($value !== '' && $value !== self::NOW && !WidgetValueValidator::isShape($kind, $value)) {
                return false;
            }
        }

        // Dates of one mode compare as texts; "now" is only known when the form is sent.
        return in_array('', $values, true) || in_array(self::NOW, $values, true)
            || $values['widgetMin'] <= $values['widgetMax'];
    }

    private static function isInteger(string $value, int $min, int $max): bool
    {
        return preg_match('/^\d{1,9}$/D', $value) === 1 && (int) $value >= $min && (int) $value <= $max;
    }

    /** The family of limits of a field, '' when it has none. */
    private static function kind(stdClass $properties): string
    {
        $type = self::text($properties, 'bfType');
        $widget = WidgetOptions::widget((array) $properties);
        $option = static fn (string $key): string
            => (string) WidgetOptions::options($widget, (array) $properties)[$key];

        return match (true) {
            $type === 'bfTextarea' => 'textarea',
            $type === 'bfNumberInput' => $widget === 'rating' ? $option('style') : 'number',
            $type !== 'bfTextfield' => '',
            $widget === 'datetime' => $option('mode'),
            in_array($widget, ['', 'email', 'url', 'address'], true) => 'text',
            default => '',
        };
    }

    private static function text(stdClass $properties, string $key): string
    {
        return is_scalar($properties->{$key} ?? null) ? (string) $properties->{$key} : '';
    }
}
