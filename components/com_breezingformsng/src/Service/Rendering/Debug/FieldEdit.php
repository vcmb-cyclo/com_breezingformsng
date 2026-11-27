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

/**
 * Rules of the field window of the debug bar: its label, its help text and whether
 * it is required. In another language than the default one of the site, the texts are
 * the translations of that language.
 *
 * @phpstan-type Changes array{label: string, hint: string, required: bool|null, limits?: array<string, string>}
 */
final class FieldEdit
{
    /** Tag the renderers use for the default language of the site, which has no translation. */
    public const DEFAULT_LANGUAGE = 'zz-ZZ';

    /** The label is also the title of the element row. */
    public const LABEL_LENGTH = 255;

    public const HINT_LENGTH = 10000;

    /** Fields changed at once. */
    private const MOST_EDITS = 200;

    /** Validation of the script library that refuses an empty value. */
    public const REQUIRED_CHECK = 'ff_valuenotempty';

    /** Types the window can make required, as the MCP field editor. */
    private const REQUIRABLE = ['bfTextfield', 'bfTextarea'];

    /**
     * What the window shows for each field.
     *
     * @return array<string, array{
     *     label: string,
     *     labelDefault: string,
     *     hint: string,
     *     hintDefault: string,
     *     required: bool,
     *     requirable: bool,
     *     limits: list<array{key: string, name: string, input: string, value: string}>
     * }>
     */
    public static function values(stdClass $node, string $language): array
    {
        $values = [];

        foreach (FieldOrder::elements($node) as $name => $properties) {
            $suffix = $language === self::DEFAULT_LANGUAGE ? '' : '_translation' . $language;
            $values[$name] = [
                'label' => self::text($properties, 'label' . $suffix),
                'labelDefault' => $suffix === '' ? '' : self::text($properties, 'label'),
                'hint' => self::text($properties, 'hint' . $suffix),
                'hintDefault' => $suffix === '' ? '' : self::text($properties, 'hint'),
                'required' => self::isRequired($properties),
                'requirable' => self::isRequirable($properties),
                'limits' => FieldLimits::of($properties),
            ];
        }

        return $values;
    }

    /**
     * Reads the edited fields posted by the debug bar.
     *
     * @return array<string, Changes>|null By field name, null when one of them cannot be read.
     */
    public static function changes(string $json): ?array
    {
        $posted = json_decode($json, true);

        if (!is_array($posted) || !array_is_list($posted) || count($posted) > self::MOST_EDITS) {
            return null;
        }

        $changes = [];

        foreach ($posted as $edit) {
            $field = $edit['field'] ?? null;
            $label = $edit['label'] ?? null;
            $hint = $edit['hint'] ?? null;
            // Empty: the setting stays as it is.
            $required = $edit['required'] ?? null;
            $limits = $edit['limits'] ?? [];

            if (
                !is_string($field) || isset($changes[$field]) || !is_string($label) || !is_string($hint)
                || !in_array($required, ['', '0', '1'], true)
                || !is_array($limits) || array_filter($limits, 'is_string') !== $limits
            ) {
                return null;
            }

            $changes[$field] = [
                'label' => $label,
                'hint' => $hint,
                'required' => $required === '' ? null : $required === '1',
                'limits' => $limits,
            ];
        }

        return $changes;
    }

    /**
     * Writes the changes to the properties of a field.
     *
     * @param array{label: string, hint: string, required: bool|null, limits?: array<string, string>} $changes
     *        required null: leave it.
     * @param int                                                     $checkId  Id of the library validation.
     * @param string                                                  $message  Error message of a new required field.
     *
     * @return list<string>|null Names of the changed properties, null when a change is refused.
     */
    public static function apply(
        stdClass $properties,
        array $changes,
        string $language,
        int $checkId,
        string $message
    ): ?array {
        $suffix = $language === self::DEFAULT_LANGUAGE ? '' : '_translation' . $language;
        $label = trim($changes['label']);
        $hint = trim($changes['hint']);

        if (
            mb_strlen($label) > self::LABEL_LENGTH || mb_strlen($hint) > self::HINT_LENGTH
            // An empty translation falls back to the main text, an empty main label would name nothing.
            || ($suffix === '' && $label === '')
        ) {
            return null;
        }

        // On a copy: a refused change leaves the field as it was.
        $copy = clone $properties;
        $changed = FieldLimits::apply($copy, $changes['limits'] ?? []);
        $required = $changes['required'];
        $switch = $required !== null && $required !== self::isRequired($properties);

        if ($changed === null || ($switch && (!self::isRequirable($properties) || ($required && $checkId <= 0)))) {
            return null;
        }

        foreach ($changed as $key) {
            $properties->{$key} = $copy->{$key};
        }

        foreach (['label' . $suffix => $label, 'hint' . $suffix => $hint] as $key => $value) {
            if (self::text($properties, $key) !== $value) {
                $properties->{$key} = $value;
                $changed[] = $key;
            }
        }

        if (!$switch) {
            return $changed;
        }

        $stored = self::text($properties, 'validationMessage');
        $values = [
            'required' => $required,
            'validationId' => $required ? $checkId : 0,
            'validationCode' => '',
            'validationFunctionName' => $required ? self::REQUIRED_CHECK : '',
            'validationCondition' => $required ? 1 : 0,
            'validationMessage' => $required ? ($stored === '' ? $message : $stored) : '',
        ];

        foreach ($values as $key => $value) {
            $properties->{$key} = $value;
            $changed[] = $key;
        }

        return $changed;
    }

    private static function isRequired(stdClass $properties): bool
    {
        return !empty($properties->required) || self::hasRequiredCheck($properties);
    }

    /** A field with another validation keeps it: only the editor changes it. */
    private static function isRequirable(stdClass $properties): bool
    {
        $unchecked = (int) self::text($properties, 'validationCondition') === 0
            && (int) self::text($properties, 'validationId') === 0
            && self::text($properties, 'validationCode') === ''
            && self::text($properties, 'validationFunctionName') === '';

        return in_array(self::text($properties, 'bfType'), self::REQUIRABLE, true)
            && ($unchecked || self::hasRequiredCheck($properties));
    }

    private static function hasRequiredCheck(stdClass $properties): bool
    {
        return self::text($properties, 'validationFunctionName') === self::REQUIRED_CHECK
            && (int) self::text($properties, 'validationCondition') === 1
            && self::text($properties, 'validationCode') === '';
    }

    private static function text(stdClass $properties, string $key): string
    {
        return is_scalar($properties->{$key} ?? null) ? (string) $properties->{$key} : '';
    }
}
