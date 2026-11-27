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

    public const COLOR_PARAM = 'bf_debug_color';

    public const COLOR_MODES = ['auto', 'light', 'dark'];

    public const NAMES_PARAM = 'bf_debug_names';

    /** Marks a Classic theme tried on a Bootstrap or one-page form; a theme folder has no dot. */
    public const CLASSIC_PREFIX = 'classic.';

    /** Name of each type of the editor, as translation keys of the administrator. */
    private const TYPE_LABELS = [
        'Text' => 'TEXTFIELD',
        'Textarea' => 'TEXTAREA',
        'Hidden' => 'HIDDEN',
        'NumberInput' => 'NUMBER_INPUT',
        'DateTime' => 'ELEMENT_DATETIME',
        'Email' => 'ELEMENT_EMAIL',
        'Phone' => 'ELEMENT_PHONE',
        'File' => 'FILE',
        'Address' => 'ELEMENT_ADDRESS',
        'Country' => 'ELEMENT_COUNTRY',
        'Url' => 'ELEMENT_URL',
        'Calendar' => 'CALENDAR',
        'CalendarResponsive' => 'CALENDAR_RESPONSIVE',
        'RadioGroup' => 'RADIO_GROUP',
        'Select' => 'SELECT',
        'Checkbox' => 'CHECKBOX',
        'CheckboxGroup' => 'CHECKBOX_GROUP',
        'Consent' => 'ELEMENT_CONSENT',
        'Captcha' => 'ELEMENT_ROBOT',
        'Robot' => 'ELEMENT_ROBOT',
        'ReCaptcha' => 'ELEMENT_ROBOT',
        'Stripe' => 'STRIPE',
        'PayPal' => 'PAYPAL',
        'Signature' => 'SIGNATURE',
        'SubmitButton' => 'SUBMIT_BUTTON',
        'Summarize' => 'SUMMARIZE',
    ];

    /** Text field presets: widget => type of the editor. */
    private const WIDGET_TYPES = [
        'email' => 'Email',
        'url' => 'Url',
        'robot' => 'Robot',
        'phone' => 'Phone',
        'datetime' => 'DateTime',
        'country' => 'Country',
        'address' => 'Address',
    ];

    /**
     * Type of an element as the editor shows it (see getElementTypeOption() of its tree model).
     *
     * @param array<mixed> $properties Properties of an element of the QuickMode tree.
     *
     * @return string The suffix of its "bfElementType…" option, empty without a type.
     */
    public static function editorType(array $properties): string
    {
        $type = self::text($properties['bfType'] ?? null);
        $widget = self::text($properties['widget'] ?? null);

        return match (true) {
            $type === 'bfTextfield' => self::WIDGET_TYPES[$widget] ?? 'Text',
            $type === 'bfCheckbox' && $widget === 'consent' => 'Consent',
            $type === 'bfNumberInput' && !empty($properties['range']) => 'Slider',
            default => substr($type, 2),
        };
    }

    /** @return string The translation key of the type name, empty when the name is not translated. */
    public static function typeLabelKey(string $editorType): string
    {
        $key = self::TYPE_LABELS[$editorType] ?? '';

        return $key === '' ? '' : 'COM_BREEZINGFORMSNG_' . $key;
    }

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

        $stylesheets = glob($root . '/*/theme.css');

        if ($stylesheets === false) {
            return [];
        }

        foreach ($stylesheets as $stylesheet) {
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
     * @return list<string> Themes of the Bootstrap and one-page renderers: no theme,
     *                      the shipped one and the installed folders.
     */
    public static function bootstrapThemes(string $root): array
    {
        return array_values(array_unique(['none', 'carte', ...self::installedThemes($root)]));
    }

    /**
     * @param list<string> $themes Themes of the Classic renderer.
     *
     * @return list<string> Their choices on a Bootstrap or one-page form.
     */
    public static function classicChoices(array $themes): array
    {
        return array_map(static fn (string $theme): string => self::CLASSIC_PREFIX . $theme, $themes);
    }

    public static function isClassicChoice(string $choice): bool
    {
        return str_starts_with($choice, self::CLASSIC_PREFIX);
    }

    /** Theme folder of a choice of the theme list. */
    public static function themeName(string $choice): string
    {
        return self::isClassicChoice($choice) ? substr($choice, strlen(self::CLASSIC_PREFIX)) : $choice;
    }

    /**
     * @param list<string> $available
     *
     * @return string The theme the renderer uses: an unavailable stored theme means no theme.
     */
    public static function appliedTheme(?string $override, string $stored, array $available): string
    {
        return $override ?? (in_array($stored, $available, true) ? $stored : 'none');
    }

    /** The technical names are shown next to the fields unless the address hides them. */
    public static function showFieldNames(string $requested): bool
    {
        return $requested !== '0';
    }

    public static function colorMode(string $requested): string
    {
        return in_array($requested, self::COLOR_MODES, true) ? $requested : 'auto';
    }

    /**
     * @param array<mixed> $node A node of the QuickMode form tree.
     * @param string       $path Place of this node in the tree.
     *
     * @return list<array{
     *     id: int,
     *     name: string,
     *     type: string,
     *     editorType: string,
     *     label: string,
     *     required: bool,
     *     validation: string,
     *     customValidation: bool,
     *     parent: string
     * }>
     */
    public static function fields(array $node, string $path = '0'): array
    {
        $fields = [];
        $properties = is_array($node['properties'] ?? null) ? $node['properties'] : [];

        if (($properties['type'] ?? '') === 'element') {
            // 0: no validation, 1: library script, 2: script written in the form.
            $validation = (int) self::text($properties['validationCondition'] ?? null);

            $fields[] = [
                'id' => (int) self::text($properties['dbId'] ?? null),
                'name' => self::text($properties['bfName'] ?? null),
                'type' => self::text($properties['bfType'] ?? null),
                'editorType' => self::editorType($properties),
                'label' => self::text($properties['label'] ?? null),
                'required' => !empty($properties['required']),
                'validation' => $validation > 0 ? self::text($properties['validationFunctionName'] ?? null) : '',
                'customValidation' => $validation === 2,
                // Fields with the same parent can change places.
                'parent' => substr($path, 0, (int) strrpos($path, '.')),
            ];
        }

        foreach (is_array($node['children'] ?? null) ? $node['children'] : [] as $key => $child) {
            if (is_array($child)) {
                array_push($fields, ...self::fields($child, $path . '.' . $key));
            }
        }

        return $fields;
    }

    private static function text(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }
}
