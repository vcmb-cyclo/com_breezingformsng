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

/**
 * Selects the CAPTCHA row used by the historical validation callback.
 */
final class CaptchaValidationRowSelector
{
    /**
     * @param array<int, \stdClass> $rows
     * @return \stdClass|null
     */
    public function select(array $rows, int $rowCount): ?object
    {
        $selected = null;

        for ($index = 0; $index < $rowCount; $index++) {
            $row = $rows[$index];

            if ($row->type === 'Captcha') {
                return $row;
            }

            if ($row->type === 'ReCaptcha') {
                $selected = $row;
            }
        }

        return $selected;
    }
}
