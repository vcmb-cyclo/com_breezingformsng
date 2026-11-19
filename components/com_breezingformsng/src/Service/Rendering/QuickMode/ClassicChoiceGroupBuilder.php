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

/** Builds the shared Classic radio and checkbox group markup. */
final class ClassicChoiceGroupBuilder
{
    public function __construct(private readonly QuickModeGroupOptionBuilder $optionBuilder)
    {
    }

    public function build(
        string $type,
        int $elementId,
        string $name,
        string $group,
        bool $wrap,
        string $labelPosition,
        string $tabIndex,
        string $eventAttributes,
        bool $readonly,
        string $newline = "\n",
        bool $labelledByElementLabel = false
    ): string {
        if ($group === '') {
            return '';
        }

        $group = str_replace("\r", '', $group);
        $wrapperClass = $wrap ? 'bfElementGroup' : 'bfElementGroupNoWrap';
        // The group is named by the element label (which carries no `for`: it would only designate
        // the first choice); the table-like Classic layout keeps the label outside the group.
        $html = '<span class="' . $wrapperClass . '" id="' . $wrapperClass . $elementId . '" role="group"'
            . ($labelledByElementLabel ? ' aria-labelledby="bfLabel' . $elementId . '"' : '') . '>'
            . $newline;

        foreach (explode("\n", $group) as $index => $line) {
            $parts = explode(';', $line);

            if (count($parts) !== 3) {
                continue;
            }

            $idSuffix = $index === 0 ? '' : '_' . $index;
            $label = '<label class="bfGroupLabel" id="bfGroupLabel' . $elementId . $idSuffix
                . '" for="ff_elem' . $elementId . $idSuffix . '">' . trim($parts[1])
                . '</label>';
            $leftLabel = $labelPosition === 'right' ? $label : '';
            $rightLabel = $labelPosition === 'right' ? '' : $label;
            $html .= $leftLabel . $this->optionBuilder->build(
                $type,
                'ff_elem',
                $name,
                (string) $parts[2],
                (string) $elementId . $idSuffix,
                (bool) $parts[0],
                $tabIndex . $eventAttributes . ($readonly ? ' disabled="disabled" ' : '')
            ) . $rightLabel . $newline;

            if ($wrap) {
                $html .= '<br/>' . $newline;
            }
        }

        return $html . '</span>' . $newline;
    }
}
