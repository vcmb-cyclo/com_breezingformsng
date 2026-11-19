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

namespace Vcmb\Component\BreezingformsNG\Site\Service\Rendering\QuickMode;

final class QuickModeCaptchaMarkupBuilder
{
    public function buildImage(
        string $attributes,
        string $elementId,
        string $class,
        string $src,
        string $suffix = '',
        string $alt = ''
    ): string {
        $imageAttributes = trim($attributes);
        $escapedElementId = htmlspecialchars($elementId, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $escapedClass = htmlspecialchars($class, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $escapedAlt = htmlspecialchars($alt, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $escapedSrc = htmlspecialchars($src, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return '<img alt="' . $escapedAlt . '" ' . ($imageAttributes !== '' ? $imageAttributes . ' ' : '')
            . 'id="' . $escapedElementId . '" class="' . $escapedClass
            . '" src="' . $escapedSrc . '"/>' . $suffix . "\n";
    }

    public function buildResponseInput(
        string $leadingAttributes,
        string $class,
        string $attributes = '',
        bool $preserveLeadingGap = false
    ): string {
        $escapedClass = htmlspecialchars($class, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $leading = $leadingAttributes !== ''
            ? $leadingAttributes . ' '
            : ($preserveLeadingGap ? ' ' : '');

        return '<input ' . $leading . 'autocomplete="off" class="' . $escapedClass . '" '
            . $attributes . 'type="text" name="bfCaptchaEntry" id="bfCaptchaEntry" />' . "\n";
    }
}
