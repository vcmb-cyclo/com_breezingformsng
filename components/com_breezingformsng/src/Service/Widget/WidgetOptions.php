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

/**
 * Turns the widget properties of a QuickMode element (bfTextfield / bfFile)
 * into the normalised option set shared by the renderers, the stored
 * template_areas and the server-side validator.
 */
final class WidgetOptions
{
    public const WIDGETS = ['email', 'url', 'phone', 'datetime', 'country', 'address', 'rating', 'consent'];

    private const MODES = ['datetime', 'date', 'time'];
    private const PROVIDERS = ['ban', 'photon'];
    private const RATING_STYLES = ['stars', 'scale'];
    private const FILL_ROLES = ['street', 'postcode', 'city', 'country'];

    /**
     * The widget of a bfTextfield, or '' for a plain text field.
     *
     * @param array<string, mixed> $properties
     */
    public static function widget(array $properties): string
    {
        $widget = (string) ($properties['widget'] ?? '');

        return \in_array($widget, self::WIDGETS, true) ? $widget : '';
    }

    /** @param array<string, mixed> $properties */
    public static function hasDropZone(array $properties): bool
    {
        return self::bool($properties['dropZone'] ?? false, false);
    }

    /**
     * The options of the widget, keyed like the data-bfng-options JSON.
     *
     * @param array<string, mixed> $properties
     * @return array<string, mixed>
     */
    public static function options(string $widget, array $properties): array
    {
        return match ($widget) {
            'email', 'url' => [
                'validate' => self::bool($properties['widgetValidate'] ?? true, true),
            ],
            'phone' => [
                'defaultCountry' => self::defaultCountry($properties),
                'countries' => self::countries($properties),
                'flags' => self::bool($properties['widgetShowFlags'] ?? true, true),
                'dialCode' => self::bool($properties['widgetShowDialCode'] ?? true, true),
                'validate' => self::bool($properties['widgetValidate'] ?? true, true),
            ],
            'datetime' => self::datetimeOptions($properties),
            'rating' => self::ratingOptions($properties),
            'consent' => [
                'url' => self::link((string) ($properties['consentUrl'] ?? '')),
                'text' => trim((string) ($properties['consentText'] ?? '')),
            ],
            'country' => [
                'defaultCountry' => self::defaultCountry($properties),
                'countries' => self::countries($properties),
                'flags' => self::bool($properties['widgetShowFlags'] ?? true, true),
            ],
            'address' => [
                'provider' => self::provider($properties),
                'minChars' => max(1, min(10, (int) ($properties['widgetMinChars'] ?? 3))),
                'fill' => self::fill((string) ($properties['widgetFill'] ?? '')),
                'countries' => self::countries($properties),
            ],
            default => [],
        };
    }

    /**
     * @param array<string, mixed> $properties
     * @return array<string, mixed>
     */
    private static function datetimeOptions(array $properties): array
    {
        $mode = (string) ($properties['widgetMode'] ?? 'datetime');
        if (!\in_array($mode, self::MODES, true)) {
            $mode = 'datetime';
        }

        return [
            'mode' => $mode,
            'min' => self::bound((string) ($properties['widgetMin'] ?? ''), $mode),
            'max' => self::bound((string) ($properties['widgetMax'] ?? ''), $mode),
            'step' => max(0, (int) ($properties['widgetStep'] ?? 15)),
        ];
    }

    /**
     * Stars count from 1 to the maximum (3 to 10 stars); a scale goes from the minimum to the
     * maximum of the number field, 0 to 10 by default and 21 choices at most.
     *
     * @param array<string, mixed> $properties
     * @return array<string, mixed>
     */
    private static function ratingOptions(array $properties): array
    {
        $style = (string) ($properties['ratingStyle'] ?? 'stars');
        $style = \in_array($style, self::RATING_STYLES, true) ? $style : 'stars';
        $min = self::integer($properties['min'] ?? null);
        $max = self::integer($properties['max'] ?? null);

        if ($style === 'stars') {
            $min = 1;
            $max = $max === null ? 5 : max(3, min(10, $max));
        } else {
            $min = max(0, min(100, $min ?? 0));
            $max = max($min + 1, min($min + 20, $max ?? $min + 10));
        }

        $color = trim((string) ($properties['ratingColor'] ?? ''));

        return [
            'style' => $style,
            'min' => $min,
            'max' => $max,
            'low' => trim((string) ($properties['ratingLow'] ?? '')),
            'high' => trim((string) ($properties['ratingHigh'] ?? '')),
            'large' => self::bool($properties['ratingLarge'] ?? false, false),
            'color' => preg_match('/^#[0-9a-fA-F]{6}\z/', $color) === 1 ? $color : '',
        ];
    }

    /** A web address or a page of the site: nothing a click could execute. */
    private static function link(string $url): string
    {
        $url = trim($url);

        return preg_match('~^(?:https?://[^\s"<>]+|/(?!/)[^\s"<>]*|index\.php\?[^\s"<>]*)\z~i', $url) === 1 ? $url : '';
    }

    private static function integer(mixed $value): ?int
    {
        $number = filter_var(\is_string($value) ? trim($value) : $value, FILTER_VALIDATE_INT);

        return $number === false ? null : $number;
    }

    /** A bound is "", "now" or an ISO value of the shape of the mode. */
    private static function bound(string $value, string $mode): string
    {
        $value = trim($value);
        if ($value === 'now') {
            return $value;
        }

        return WidgetValueValidator::isShape($mode, $value) ? $value : '';
    }

    /** @param array<string, mixed> $properties */
    private static function defaultCountry(array $properties): string
    {
        $code = strtolower(trim((string) ($properties['widgetDefaultCountry'] ?? 'fr')));

        return CountryCodes::isValid($code) ? $code : '';
    }

    /**
     * @param array<string, mixed> $properties
     * @return list<string>
     */
    private static function countries(array $properties): array
    {
        $countries = [];
        foreach (explode(',', (string) ($properties['widgetCountries'] ?? '')) as $code) {
            $code = strtolower(trim($code));
            if (CountryCodes::isValid($code) && !\in_array($code, $countries, true)) {
                $countries[] = $code;
            }
        }

        return $countries;
    }

    /** @param array<string, mixed> $properties */
    private static function provider(array $properties): string
    {
        $provider = (string) ($properties['widgetProvider'] ?? '');

        return \in_array($provider, self::PROVIDERS, true) ? $provider : '';
    }

    /**
     * @return array<string, string>
     */
    private static function fill(string $text): array
    {
        $fill = [];
        foreach (preg_split('/\R/', $text) ?: [] as $line) {
            $parts = explode('=', $line, 2);
            if (\count($parts) !== 2) {
                continue;
            }
            $role = strtolower(trim($parts[0]));
            $field = trim($parts[1]);
            if (\in_array($role, self::FILL_ROLES, true) && $field !== '' && mb_strlen($field) <= 128) {
                $fill[$role] = $field;
            }
        }

        return $fill;
    }

    private static function bool(mixed $value, bool $default): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $default;
    }
}
