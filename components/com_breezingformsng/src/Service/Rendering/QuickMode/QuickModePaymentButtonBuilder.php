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

/** Builds the shared payment method control used by QuickMode renderers. */
final class QuickModePaymentButtonBuilder
{
    public static function build(
        string $provider,
        string $fieldName,
        int $elementId,
        string $image,
        string $buttonValue,
        string $imageAlt,
        string $tabIndex,
        string $onblur,
        string $onchange,
        string $onfocus,
        string $onselect,
        string $readonly,
        bool $actionClick,
        string $actionFunctionName
    ): string {
        $type = $image === '' ? 'submit' : 'image';
        $value = $image === ''
            ? 'value="' . htmlspecialchars($buttonValue, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '" '
            : '';
        $source = self::paymentImageAttributes($image, $imageAlt);
        $onclick = 'onclick="document.getElementById(\'bfPaymentMethod\').value=\'' . $provider . '\';';
        if ($actionClick) {
            $onclick .= $actionFunctionName . '(this,\'click\');';
        }
        $onclick .= '" ';
        $escapedType = htmlspecialchars($type, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $escapedFieldName = htmlspecialchars($fieldName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return '<input class="ff_elem" ' . $value . $source . $tabIndex . $onclick
            . $onblur . $onchange . $onfocus . $onselect . $readonly
            . 'type="' . $escapedType . '" name="ff_nm_' . $escapedFieldName . '[]"'
            . ' id="ff_elem' . $elementId . '"/>' . "\n";
    }

    private static function paymentImageAttributes(string $image, string $alt = ''): string
    {
        if ($image === '') {
            return '';
        }

        $altAttribute = $alt !== ''
            ? ' alt="' . htmlspecialchars($alt, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"'
            : '';

        return 'src="' . htmlspecialchars($image, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"'
            . $altAttribute . ' ';
    }
}
