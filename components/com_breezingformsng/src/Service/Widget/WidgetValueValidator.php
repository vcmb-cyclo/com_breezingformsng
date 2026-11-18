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

namespace Vcmb\Component\BreezingformsNG\Site\Service\Widget;

use DateTimeImmutable;
use DateTimeZone;

/**
 * Server-side check of the values posted by the QuickMode widgets.
 *
 * Pure logic, no Joomla dependency. Each method returns null when the value is
 * acceptable or the language key of the error. An empty value is always valid:
 * requiredness belongs to the usual validation.
 */
final class WidgetValueValidator
{
    public const INVALID_PHONE = 'COM_BREEZINGFORMSNG_WIDGET_INVALID_PHONE';
    public const INVALID_DATETIME = 'COM_BREEZINGFORMSNG_WIDGET_INVALID_DATETIME';
    public const INVALID_COUNTRY = 'COM_BREEZINGFORMSNG_WIDGET_INVALID_COUNTRY';

    private const FORMATS = [
        'datetime' => ['!Y-m-d\TH:i', '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}\z/'],
        'date' => ['!Y-m-d', '/^\d{4}-\d{2}-\d{2}\z/'],
        'time' => ['!H:i', '/^\d{2}:\d{2}\z/'],
    ];

    /**
     * The browser resolves "now" in its own time zone while the server knows
     * only UTC: a "now" bound is therefore widened by the largest UTC offset
     * so that a visitor is never refused because of the time zone.
     */
    private const NOW_TOLERANCE_HOURS = 14;

    /**
     * @param array<string, mixed> $options
     */
    public function validate(string $widget, array $options, string $value, ?DateTimeImmutable $now = null): ?string
    {
        if ($value === '') {
            return null;
        }

        return match ($widget) {
            'phone' => preg_match('/^\+[1-9]\d{7,14}\z/', $value) === 1 ? null : self::INVALID_PHONE,
            'datetime' => $this->validateDatetime($options, $value, $now),
            'country' => $this->validateCountry($options, $value),
            default => null,
        };
    }

    /**
     * Validates the posted values of the widget element called $name in the
     * stored template_areas; returns the first error key, if any.
     *
     * @param array<int|string, mixed> $areas
     * @param array<int|string, mixed> $values
     */
    public function validateAreas(array $areas, string $name, array $values, ?DateTimeImmutable $now = null): ?string
    {
        foreach ($areas as $area) {
            if (!\is_array($area) || !\is_array($area['elements'] ?? null)) {
                continue;
            }
            foreach ($area['elements'] as $element) {
                if (
                    !\is_array($element)
                    || trim((string) ($element['name'] ?? '')) !== trim($name)
                    || !\is_array($element['options'] ?? null)
                ) {
                    continue;
                }
                $widget = (string) ($element['options']['widget'] ?? '');
                if ($widget === '') {
                    return null;
                }
                $widgetOptions = \is_array($element['options']['widgetOptions'] ?? null)
                    ? $element['options']['widgetOptions']
                    : [];
                foreach ($values as $value) {
                    $error = $this->validate($widget, $widgetOptions, (string) $value, $now);
                    if ($error !== null) {
                        return $error;
                    }
                }

                return null;
            }
        }

        return null;
    }

    /** Whether the value has the (calendar-unchecked) shape of the mode. */
    public static function isShape(string $mode, string $value): bool
    {
        return isset(self::FORMATS[$mode]) && preg_match(self::FORMATS[$mode][1], $value) === 1;
    }

    /** @param array<string, mixed> $options */
    private function validateDatetime(array $options, string $value, ?DateTimeImmutable $now): ?string
    {
        $mode = (string) ($options['mode'] ?? 'datetime');
        if (!isset(self::FORMATS[$mode])) {
            $mode = 'datetime';
        }
        [$format] = self::FORMATS[$mode];

        if (!self::isShape($mode, $value) || !$this->isRealDate($format, $value)) {
            return self::INVALID_DATETIME;
        }

        $now ??= new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $min = $this->bound((string) ($options['min'] ?? ''), $mode, $format, $now, -self::NOW_TOLERANCE_HOURS);
        $max = $this->bound((string) ($options['max'] ?? ''), $mode, $format, $now, self::NOW_TOLERANCE_HOURS);

        if (($min !== null && $value < $min) || ($max !== null && $value > $max)) {
            return self::INVALID_DATETIME;
        }

        return null;
    }

    private function isRealDate(string $format, string $value): bool
    {
        $date = DateTimeImmutable::createFromFormat($format, $value, new DateTimeZone('UTC'));

        return $date !== false && $date->format(substr($format, 1)) === $value;
    }

    private function bound(string $bound, string $mode, string $format, DateTimeImmutable $now, int $hours): ?string
    {
        if ($bound === '') {
            return null;
        }
        if ($bound === 'now') {
            // A time of day cannot be compared with "now" across midnight.
            return $mode === 'time' ? null : $now->modify($hours . ' hours')->format(substr($format, 1));
        }

        return self::isShape($mode, $bound) ? $bound : null;
    }

    /** @param array<string, mixed> $options */
    private function validateCountry(array $options, string $value): ?string
    {
        if (!\in_array($value, CountryCodes::ALL, true)) {
            return self::INVALID_COUNTRY;
        }

        $allowed = array_map(
            static fn ($code): string => strtoupper((string) $code),
            \is_array($options['countries'] ?? null) ? $options['countries'] : []
        );

        return $allowed === [] || \in_array($value, $allowed, true) ? null : self::INVALID_COUNTRY;
    }
}
