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

use Vcmb\Component\BreezingformsNG\Site\Service\Widget\WidgetFieldBuilder;
use Vcmb\Component\BreezingformsNG\Site\Service\Widget\WidgetOptions;

/**
 * Applies the shared QuickMode semantics for text and number controls.
 *
 * Renderers remain responsible for their surrounding markup and assets. This
 * strategy owns only field metadata that was previously repeated by every
 * renderer: translations, input type, length and numeric bounds.
 */
final class QuickModeTextFieldStrategy
{
    /**
     * @param array<string, mixed> $field
     */
    public function textfield(
        array $field,
        string $languageTag,
        string $class,
        string $attributesBeforeLength,
        string $attributesAfterLength = ''
    ): string {
        $field = $this->translatedField($field, $languageTag, ['value', 'placeholder']);
        if (WidgetOptions::widget($field) !== '') {
            return WidgetFieldBuilder::textControl($field, $class, $attributesBeforeLength . $attributesAfterLength);
        }

        $maxlength = is_numeric($field['maxLength'] ?? null)
            ? 'maxlength="' . intval($field['maxLength']) . '" '
            : '';

        return (new QuickModeInputBuilder())->build(
            $class,
            !empty($field['password']) ? 'password' : 'text',
            (string) $field['bfName'],
            (string) ($field['value'] ?? ''),
            (int) $field['dbId'],
            $attributesBeforeLength . $maxlength . $attributesAfterLength,
            (string) ($field['placeholder'] ?? '')
        );
    }

    /**
     * @param array<string, mixed> $field
     */
    public function numberInput(
        array $field,
        string $languageTag,
        string $class,
        string $attributesBeforeLength,
        string $attributesAfterLength = '',
        string $lengthAttribute = 'maxlength',
        bool $bootstrapPlaceholderCompatibility = false,
        bool $includeBounds = true
    ): string {
        $field = $this->translatedField($field, $languageTag, ['placeholder']);
        if ($bootstrapPlaceholderCompatibility && !empty($field['placeholder_translation' . $languageTag])) {
            $field['placeholder'] = '000';
        }

        $length = is_numeric($field['maxLength'] ?? null)
            ? $lengthAttribute . '="' . intval($field['maxLength']) . '" '
            : '';

        return (new QuickModeInputBuilder())->build(
            $class,
            !empty($field['range']) ? 'range' : 'number',
            (string) $field['bfName'],
            (string) ($field['value'] ?? ''),
            (int) $field['dbId'],
            $attributesBeforeLength . $length . $attributesAfterLength,
            (string) ($field['placeholder'] ?? ''),
            $includeBounds
                ? ' step="' . ($field['step'] ?? '') . '" max="' . ($field['max'] ?? '')
                    . '" min="' . ($field['min'] ?? '') . '"'
                : ''
        );
    }

    /**
     * @param array<string, mixed> $field
     * @param list<string> $translations
     * @return array<string, mixed>
     */
    private function translatedField(array $field, string $languageTag, array $translations): array
    {
        foreach ($translations as $key) {
            $translationKey = $key . '_translation' . $languageTag;
            if (!empty($field[$translationKey])) {
                $field[$key] = $field[$translationKey];
            }
        }

        return $field;
    }
}
