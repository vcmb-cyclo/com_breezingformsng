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

/** Builds the signature canvas and reset control shared by QuickMode themes. */
final class QuickModeSignatureMarkupBuilder
{
    public static function build(
        int $elementId,
        string $fieldName,
        string $buttonAttributes,
        string $buttonLabel,
        bool $includeFieldNameMarker
    ): string {
        $escapedFieldName = htmlspecialchars($fieldName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $marker = $includeFieldNameMarker ? "<span class='bfSignature" . $escapedFieldName . "'></span>" : '';

        return '<div class="bfSignature" id="bfSignature' . $elementId
            . '"><div class="bfSignatureCanvasBorder"><canvas></canvas></div>' . "\n"
            . '<button ' . $buttonAttributes . '><span>' . $buttonLabel . '</span></button>' . "\n"
            . $marker . '</div>';
    }
}
