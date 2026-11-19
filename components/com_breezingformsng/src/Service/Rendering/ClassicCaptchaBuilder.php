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

use Joomla\CMS\Language\Text;

/**
 * Builds the classic renderer markup for a CAPTCHA element.
 */
final class ClassicCaptchaBuilder
{
    public function build(
        int $elementId,
        string $wrapperStyle,
        string $wrapperClass,
        string $captchaUrl,
        string $rootUrl,
        int $width,
        int $height,
        string $indent = "\t",
        string $compactNewline = "\n",
        string $newline = "\n"
    ): string {
        $dimensions = '';
        if ($width > 0) {
            $dimensions .= 'width:' . $width . 'px;';
        }
        if ($height > 0) {
            $dimensions .= 'height:' . $height . 'px;';
        }

        return $indent . '<div id="ff_div' . $elementId . '" style="' . $wrapperStyle
            . '"' . $wrapperClass . '>' . $compactNewline
            . '<img id="ff_capimgValue" class="ff_capimg" src="' . $captchaUrl . '" alt="'
            . htmlspecialchars(
                Text::_('COM_BREEZINGFORMSNG_CAPTCHA_IMAGE_ALT'),
                ENT_QUOTES | ENT_SUBSTITUTE,
                'UTF-8'
            ) . '"/>' . $compactNewline
            . '<br/>' . $compactNewline
            . '<input type="text" style="' . $dimensions . '" name="bfCaptchaEntry" id="bfCaptchaEntry" />'
            . $compactNewline
            . '<a href="#" role="button" onclick="document.getElementById(\'bfCaptchaEntry\').value=\'\';'
            . 'document.getElementById(\'bfCaptchaEntry\').focus();document.getElementById(\'ff_capimgValue\').src = \''
            . $captchaUrl . '&bfMathRandom=\' + Math.random(); return false"><img src="' . $rootUrl
            . 'media/com_breezingformsng/images/site/captcha/refresh-captcha.png" border="0" alt="'
            . htmlspecialchars(
                Text::_('COM_BREEZINGFORMSNG_RC_REFRESH'),
                ENT_QUOTES | ENT_SUBSTITUTE,
                'UTF-8'
            ) . '" /></a>' . $compactNewline
            . $indent . '</div>' . $newline;
    }
}
