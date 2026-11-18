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

/** Builds the Bootstrap error-message container shared by two renderers. */
final class QuickModeErrorMessageMarkupBuilder
{
    public static function build(
        mixed $alertClass,
        mixed $errorClass,
        string $titleSingular,
        string $titlePlural,
        string $newline
    ): string {
        return '<div class="bfErrorMessage ' . (string) $alertClass . ' ' . (string) $errorClass
            . '" role="alert" aria-live="assertive" style="display:none">'
            . '<strong class="bfErrorSummaryTitle" data-title-singular="'
            . htmlspecialchars($titleSingular, ENT_QUOTES, 'UTF-8')
            . '" data-title-plural="' . htmlspecialchars($titlePlural, ENT_QUOTES, 'UTF-8')
            . '"></strong><ul class="bfErrorSummaryList"></ul></div>' . $newline;
    }
}
