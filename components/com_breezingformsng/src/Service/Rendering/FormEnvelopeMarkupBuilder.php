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

namespace Vcmb\Component\BreezingformsNG\Site\Service\Rendering;

/** Assembles the form wrapper and its mode-specific closing markup. */
final class FormEnvelopeMarkupBuilder
{
    public function opening(string $formId, string $className): string
    {
        return '<div id="ff_formdiv' . $formId . '" class="bfFormDiv'
            . ($className !== '' ? ' ' . $className : '') . '">';
    }

    public function closing(string $newline = "\r\n"): string
    {
        return '</div><!-- form end -->' . $newline;
    }

    public function frontend(
        string $context,
        string $submission,
        string $token,
        string $optional,
        string $additional,
        string $technical,
        string $routing,
        string $newline
    ): string {
        return $context . $submission . $token . $optional . $additional . $technical . $routing
            . '</form>' . $newline;
    }
}
