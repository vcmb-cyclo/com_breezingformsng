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

/** Consolidates the small pure helpers used by CAPTCHA rendering. */
final class CaptchaSupportBuilder
{
    /** @return array{captcha: string, image: string, check: string, recaptcha: string} */
    public function endpoints(string $root, bool $administrator, int $form): array
    {
        $prefix = $root . ($administrator ? '/administrator' : '');

        return [
            'captcha' => $prefix . '/index.php?option=com_breezingformsng&bfCaptcha=1',
            'image' => $prefix . '/index.php?option=com_breezingformsng&bfCaptcha=1&bfMathRandom=',
            'check' => $prefix
                . '/index.php?raw=true&option=com_breezingformsng&checkCaptcha=true&Itemid=0&tmpl=component&value=',
            'recaptcha' => 'index.php?raw=true&option=com_breezingformsng&bfReCaptcha=true&form='
                . $form . '&Itemid=0&tmpl=component',
        ];
    }

    /** @return array{0: string, 1: string} */
    public function validationDefaults(string $errorMessage): array
    {
        return [
            json_encode(
                $errorMessage,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR
            ),
            'function bfCheckCaptcha(){if(checkFileExtensions())ff_submitForm2();}',
        ];
    }
}
