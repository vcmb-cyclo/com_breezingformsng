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

\defined('_JEXEC') or die;

use HTML_facileFormsProcessor;

/**
 * Hydrates QuickMode metadata from the submitted record rows.
 *
 * The three active renderers share this record-value semantics while keeping
 * their own field markup and theme-specific translation steps.
 */
final class QuickModeSubmittedValueHydrator
{
    /**
     * @param array<string, mixed> $metadata
     *
     * @return array<string, mixed>
     */
    public static function hydrate(
        HTML_facileFormsProcessor $processor,
        array $metadata,
        string $languageTag
    ): array {
        $rows = $processor->rows ?? [];
        for ($i = 0; $i < $processor->rowcount; $i++) {
            $row = $rows[$i];
            if ($metadata['bfName'] != $row->name) {
                continue;
            }

            if (
                (isset($metadata['value']) || isset($metadata['list']) || isset($metadata['group']))
                && in_array(
                    $metadata['bfType'],
                    [
                        'bfTextfield',
                        'bfTextarea',
                        'bfCheckbox',
                        'bfCheckboxGroup',
                        'bfSubmitButton',
                        'bfHidden',
                        'bfCalendar',
                        'bfNumberInput',
                        'bfCalendarResponsive',
                        'bfSelect',
                        'bfRadioGroup',
                    ]
                )
            ) {
                $valueTranslationKey = 'value_translation' . $languageTag;
                if (isset($metadata[$valueTranslationKey]) && $metadata[$valueTranslationKey] != '') {
                    $metadata[$valueTranslationKey] = $processor->replaceCode(
                        $metadata[$valueTranslationKey],
                        'data1 of ' . $metadata['bfName'],
                        'e',
                        $metadata['dbId'],
                        0
                    );
                }

                $groupTranslationKey = 'group_translation' . $languageTag;
                if (isset($metadata[$groupTranslationKey]) && $metadata[$groupTranslationKey] != '') {
                    $metadata[$groupTranslationKey] = $processor->replaceCode(
                        $metadata[$groupTranslationKey],
                        'data2 of ' . $metadata['bfName'],
                        'e',
                        $metadata['dbId'],
                        0
                    );
                }

                $listTranslationKey = 'list_translation' . $languageTag;
                if (isset($metadata[$listTranslationKey]) && $metadata[$listTranslationKey] != '') {
                    $metadata[$listTranslationKey] = $processor->replaceCode(
                        $metadata[$listTranslationKey],
                        'data2 of ' . $metadata['bfName'],
                        'e',
                        $metadata['dbId'],
                        0
                    );
                }

                if ($metadata['bfType'] == 'bfSelect') {
                    $metadata['list'] = $processor->replaceCode(
                        $row->data2,
                        'data2 of ' . $metadata['bfName'],
                        'e',
                        $metadata['dbId'],
                        0
                    );
                } elseif ($metadata['bfType'] == 'bfCheckboxGroup' || $metadata['bfType'] == 'bfRadioGroup') {
                    $metadata['group'] = $processor->replaceCode(
                        $row->data2,
                        'data2 of ' . $metadata['bfName'],
                        'e',
                        $metadata['dbId'],
                        0
                    );
                } else {
                    $metadata['value'] = $processor->replaceCode(
                        $row->data1,
                        'data1 of ' . $metadata['bfName'],
                        'e',
                        $metadata['dbId'],
                        0
                    );
                }
            }

            if (isset($metadata['checked']) && $metadata['bfType'] == 'bfCheckbox') {
                $metadata['checked'] = $row->flag1 == 1 ? true : false;
            }

            break;
        }

        return $metadata;
    }
}
