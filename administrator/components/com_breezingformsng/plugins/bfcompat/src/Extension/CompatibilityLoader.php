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

namespace Vcmb\Plugin\System\Bfcompat\Extension;

defined('_JEXEC') or die;

final class CompatibilityLoader
{
    private const CLASS_FILES = [
        'BFDbo' => 'BFFactory.php',
        'BFFactory' => 'BFFactory.php',
        'BFFile' => 'BFFactory.php',
        'BFIntegrate' => 'BFIntegrate.php',
        'BFJoomlaConfig' => 'BFJoomlaConfig.php',
        'BFPDF' => 'BFPDF.php',
        'BFRequest' => 'BFRequest.php',
        'BFText' => 'BFText.php',
    ];

    private static bool $registered = false;

    public static function register(): void
    {
        if (self::$registered) {
            return;
        }

        spl_autoload_register(self::autoload(...), true, true);
        self::$registered = true;
    }

    private static function autoload(string $class): void
    {
        $file = self::CLASS_FILES[$class] ?? null;

        if ($file !== null) {
            require_once dirname(__DIR__) . '/Compat/' . $file;
        }
    }
}
