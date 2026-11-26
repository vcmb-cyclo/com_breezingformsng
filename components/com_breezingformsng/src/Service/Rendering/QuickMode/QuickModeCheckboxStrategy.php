<?php

/**
 * BreezingForms NG - A Joomla Forms Application
 *
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 *
 * SPDX-License-Identifier: GPL-2.0-or-later
 **/

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Site\Service\Rendering\QuickMode;

use Vcmb\Component\BreezingformsNG\Site\Service\Widget\WidgetOptions;

/**
 * Builds the shared checkbox control while renderers retain their wrappers.
 */
final class QuickModeCheckboxStrategy
{
    /**
     * @param array<string, mixed> $field
     * @param string $policyLabel text of the privacy policy link of a consent box
     * @param string $languageTag suffix of the translated properties, such as "fr_FR"
     */
    public function build(
        array $field,
        string $class,
        string $attributes,
        bool $readonly = false,
        string $policyLabel = '',
        string $languageTag = ''
    ): string {
        $consent = WidgetOptions::widget($field) === 'consent';
        $html = self::checkboxMarkup(
            $class,
            (string) $field['bfName'],
            (string) ($field['value'] ?? ''),
            (int) $field['dbId'],
            // A consent is given by the visitor: its box is never checked beforehand.
            !$consent && ($field['checked'] ?? false),
            $attributes . ($readonly ? ' disabled="disabled" ' : '')
        );

        if (!$consent) {
            return $html;
        }

        $options = WidgetOptions::options('consent', $field);
        $translated = trim((string) ($field['consentText_translation' . $languageTag] ?? ''));
        $text = $translated !== '' ? $translated : $options['text'];

        if ($text !== '') {
            $html .= '<label class="bfng-consent-text" for="ff_elem' . (int) $field['dbId'] . '">'
                . self::escape($text) . '</label>' . "\n";
        }

        if ($options['url'] !== '' && $policyLabel !== '') {
            $href = self::escape($options['url']);
            $html .= '<a class="bfng-consent-link" href="' . $href . '" target="_blank" rel="noopener">'
                . self::escape($policyLabel) . '</a>' . "\n";
        }

        return '<span class="bfng-consent">' . $html . '</span>' . "\n";
    }

    private static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private static function checkboxMarkup(
        string $class,
        string $fieldName,
        string $value,
        int $elementId,
        bool $checked,
        string $attributes
    ): string {
        $escapedFieldName = htmlspecialchars($fieldName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return '<input class="' . htmlspecialchars($class, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '" '
            . ($checked ? 'checked="checked" ' : '')
            . $attributes
            . 'type="checkbox" name="ff_nm_' . $escapedFieldName . '[]" value="'
            . htmlentities(trim($value), ENT_QUOTES, 'UTF-8') . '" id="ff_elem' . $elementId . '"/>' . "\n";
    }
}
