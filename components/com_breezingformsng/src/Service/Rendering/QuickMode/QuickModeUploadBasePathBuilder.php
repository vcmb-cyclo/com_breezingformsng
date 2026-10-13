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

/** Resolves the base path used by the historical QuickMode uploader. */
final class QuickModeUploadBasePathBuilder
{
    public function build(string $baseUri): string
    {
        $base = explode('/', $baseUri);
        $administratorIndex = count($base) - 2;

        if (isset($base[$administratorIndex]) && $base[$administratorIndex] === 'administrator') {
            unset($base[$administratorIndex]);
            $base = array_values($base);
        }

        return implode('/', $base);
    }
}
