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

/** Builds the native file/placeholder input shared by QuickMode upload flows. */
final class QuickModeFileInputBuilder
{
    public static function build(string $attributes, string $type, string $fieldName, int $elementId): string
    {
        $escapedType = htmlspecialchars($type, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $escapedFieldName = htmlspecialchars($fieldName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return '<input class="ff_elem" ' . $attributes
            . 'type="' . $escapedType . '" name="ff_nm_' . $escapedFieldName . '[]"'
            . ' id="ff_elem' . $elementId . '"/>' . "\n";
    }
}
