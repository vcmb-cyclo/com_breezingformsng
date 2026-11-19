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

namespace Vcmb\Component\BreezingformsNG\Site\Service\Notification;

/**
 * Resolves a per-form template file (mail or PDF) from a form name stored in
 * the database: the name must be a plain identifier and the real path of the
 * file must stay directly inside the expected directory. Anything else is
 * "no custom template" and the caller falls back to its default.
 */
final class TemplateFileResolver
{
    public static function isSafeName(string $name): bool
    {
        return $name !== '' && !str_contains($name, '..') && preg_match('/^[A-Za-z0-9_.-]+$/D', $name) === 1;
    }

    /** @return string The file path, or '' when there is no safe, existing template. */
    public static function resolve(string $directory, string $name, string $suffix): string
    {
        if (!self::isSafeName($name)) {
            return '';
        }

        $base = realpath($directory);
        if ($base === false) {
            return '';
        }

        $file = realpath($base . '/' . $name . $suffix);

        return $file !== false && dirname($file) === $base && is_file($file) && is_readable($file) ? $file : '';
    }
}
