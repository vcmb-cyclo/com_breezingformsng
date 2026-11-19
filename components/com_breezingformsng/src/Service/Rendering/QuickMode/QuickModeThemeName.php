<?php

/**
 * BreezingForms NG - A Joomla Forms Application
 *
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 *
 * SPDX-License-Identifier: GPL-2.0-or-later
 **/

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Site\Service\Rendering\QuickMode;

/**
 * Validates, at render time, a theme name read from the stored form JSON: it
 * must be a plain directory name that exists in the installed themes folder
 * (or be a theme shipped as a web asset). Otherwise the form renders as if it
 * had no theme.
 */
final class QuickModeThemeName
{
    /**
     * @param list<string> $shipped Themes shipped with the component, not present as a folder.
     */
    public static function resolve(string $root, mixed $theme, array $shipped = [], string $none = ''): string
    {
        if (!is_string($theme) || $theme === '' || $theme === 'none') {
            return is_string($theme) ? $theme : $none;
        }

        if (preg_match('/^[A-Za-z0-9_-]+$/D', $theme) !== 1) {
            return $none;
        }

        if (in_array($theme, $shipped, true)) {
            return $theme;
        }

        $base = realpath($root);
        $directory = $base === false ? false : realpath($base . '/' . $theme);

        return $directory !== false && dirname($directory) === $base && is_dir($directory) ? $theme : $none;
    }
}
