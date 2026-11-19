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

/** Builds scripts registering disabled QuickMode sections and fields. */
final class QuickModeDeactivationScriptBuilder
{
    public function section(string $name, string $newline = "\n"): string
    {
        return '<script type="text/javascript">bfRegisterDeactivatedSection('
            . json_encode(
                $name,
                JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR
            )
            . ');</script>' . $newline;
    }

    public function field(string $name, string $newline = "\n"): string
    {
        return '<script type="text/javascript">bfRegisterDeactivatedField('
            . json_encode(
                $name,
                JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR
            )
            . ');</script>' . $newline;
    }
}
