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

\defined('_JEXEC') or die;

/** Resolves the display content of the historical QuickMode hint syntax. */
final class QuickModeHintContentResolver
{
    public function resolve(string $hint, bool $splitOnlyOnce): string
    {
        $trimmedHint = trim($hint);
        $hintParts = $splitOnlyOnce
            ? explode('<<<style', $trimmedHint, 2)
            : explode('<<<style', $trimmedHint);

        if (count($hintParts) > 1 && trim($hintParts[0]) !== '') {
            return trim($hintParts[1]);
        }

        return $trimmedHint;
    }
}
