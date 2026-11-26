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

use Vcmb\Component\BreezingformsNG\Site\Service\Rendering\QuickMode\QuickModeInputBuilder;

/**
 * Builds the markup of the QuickMode widgets shared by the three renderers.
 *
 * Runtime contract (media/com_breezingformsng/js/site/bfng-widgets.js): the control carries
 * data-bfng-widget and data-bfng-options (JSON), and data-bfng-value, the value restored from a
 * saved record (country: also data-value). Plain fields never reach this class.
 */
final class WidgetFieldBuilder
{
    private const INPUT_TYPES = [
        // A text input keeps the style that every theme gives to the text fields; inputmode and
        // autocomplete bring the e-mail keyboard and the browser suggestions.
        'email' => 'text',
        'url' => 'text',
        'phone' => 'tel',
        'address' => 'text',
        // Without the script the visitor still types the number.
        'rating' => 'number',
    ];

    private const DATETIME_TYPES = [
        'datetime' => 'datetime-local',
        'date' => 'date',
        'time' => 'time',
    ];

    /** @param array<string, mixed> $field */
    public static function hasWidget(array $field): bool
    {
        $widget = WidgetOptions::widget($field);

        // The rating is a presentation of the number field, every other widget one of the text field.
        return ($field['bfType'] ?? '') === 'bfTextfield' && !\in_array($widget, ['', 'rating', 'consent'], true)
            || ($field['bfType'] ?? '') === 'bfNumberInput' && $widget === 'rating'
            || ($field['bfType'] ?? '') === 'bfFile' && WidgetOptions::hasDropZone($field);
    }

    /**
     * The widget input / select of a bfTextfield with a widget.
     *
     * @param array<string, mixed> $field the element, value and placeholder already translated
     */
    public static function textControl(array $field, string $class, string $attributes): string
    {
        $widget = WidgetOptions::widget($field);
        $options = WidgetOptions::options($widget, $field);
        $value = trim((string) ($field['value'] ?? ''));
        $data = self::dataAttributes($widget, $options) . ' data-bfng-value="' . self::escape($value) . '"';

        if ($widget === 'country') {
            return '<select class="' . self::escape($class) . '" ' . $attributes
                . 'name="ff_nm_' . self::escape((string) $field['bfName']) . '[]"'
                . ' id="ff_elem' . (int) $field['dbId'] . '"' . $data
                . ' data-value="' . self::escape($value) . '"></select>' . "\n";
        }

        $type = $widget === 'datetime'
            ? self::DATETIME_TYPES[$options['mode']]
            : self::INPUT_TYPES[$widget];
        if ($widget === 'email' || $widget === 'url') {
            $attributes .= 'inputmode="' . $widget . '" autocomplete="' . $widget . '" autocapitalize="off" spellcheck="false" ';
        }

        $attributes .= \in_array($widget, ['email', 'url', 'address'], true) && is_numeric($field['maxLength'] ?? null)
            ? 'maxlength="' . (int) $field['maxLength'] . '" '
            : '';

        return (new QuickModeInputBuilder())->build(
            $class,
            $type,
            (string) $field['bfName'],
            $value,
            (int) $field['dbId'],
            $attributes,
            (string) ($field['placeholder'] ?? ''),
            $data
        );
    }

    /**
     * The extra attributes of the file input of a drop zone, or ''.
     *
     * @param array<string, mixed> $field
     */
    public static function fileAttributes(array $field): string
    {
        return WidgetOptions::hasDropZone($field) ? self::dataAttributes('dropzone', []) : '';
    }

    /** @param array<string, mixed> $options */
    private static function dataAttributes(string $widget, array $options): string
    {
        if (isset($options['fill']) && $options['fill'] === []) {
            $options['fill'] = new \stdClass();
        }
        $json = $options === []
            ? '{}'
            : (string) json_encode($options, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

        return ' data-bfng-widget="' . self::escape($widget) . '" data-bfng-options="' . self::escape($json) . '"';
    }

    private static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
