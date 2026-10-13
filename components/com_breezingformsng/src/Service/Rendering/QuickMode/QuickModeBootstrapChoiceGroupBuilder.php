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

use Closure;

/** Builds the Bootstrap/OnePage radio and checkbox group markup. */
final class QuickModeBootstrapChoiceGroupBuilder
{
    public function __construct(
        private readonly QuickModeGroupOptionBuilder $optionBuilder,
        private readonly Closure $bootstrapClass
    ) {
    }

    /**
     * @param array<string, mixed> $field
     */
    public function build(
        string $type,
        array $field,
        string $languageTag,
        string $label,
        string $tabIndex,
        string $eventAttributes,
        string $readonly,
        string $wrapClass = ''
    ): string {
        $translationKey = 'group_translation' . $languageTag;
        if (!empty($field[$translationKey])) {
            $field['group'] = $field[$translationKey];
        }

        if (($field['group'] ?? '') === '') {
            return '';
        }

        $class = $this->bootstrapClass;
        $html = '<div class="' . $class('controls') . ' ' . $class('form-inline') . '">';
        $html .= '<div class="' . $class('form-group') . ' ' . $class('radio-form-group') . '">';
        $html .= $label . '<span class="' . $class('nonform-control') . '">';
        $wrap = (bool) ($field['wrap'] ?? false);
        if ($wrap) {
            $html .= '<div' . ($wrapClass !== '' ? ' class="' . $wrapClass . '"' : '')
                . ' style="display: inline-block; vertical-align: top;">';
        }

        foreach (explode("\n", str_replace("\r", '', (string) $field['group'])) as $index => $line) {
            $parts = explode(';', $line);
            if (count($parts) !== 3) {
                continue;
            }

            $idSuffix = $index !== 0 ? '_' . $index : '';
            $inlineClass = $wrap ? '' : ' ' . $class('inline');
            $html .= '<div class="form-check' . $inlineClass . '">';
            $html .= $this->optionBuilder->build(
                $type,
                'ff_elem form-check-input',
                (string) $field['bfName'],
                (string) $parts[2],
                (string) $field['dbId'] . $idSuffix,
                $parts[0] == 1,
                $tabIndex . $eventAttributes . ($readonly ? ' disabled="disabled" ' : '')
            ) . "\n";
            $html .= '<label class="' . $class($type === 'radio' ? 'radio' : 'checkbox')
                . '" id="bfGroupLabel' . $field['dbId'] . $idSuffix
                . '" for="ff_elem' . $field['dbId'] . $idSuffix . '">' . trim($parts[1]) . '</label>';
            $html .= '</div>';
        }

        if ($wrap) {
            $html .= '</div>';
        }

        return $html . '</span></div></div>';
    }
}
