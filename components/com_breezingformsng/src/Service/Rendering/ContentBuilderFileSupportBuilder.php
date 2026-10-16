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

/** Consolidates the small, pure file helpers used by ContentBuilder rendering. */
final class ContentBuilderFileSupportBuilder
{
    public function displayName(string $wrappedPath): string
    {
        return str_replace('&lt;br&gt;', '<br>', htmlspecialchars(basename($wrappedPath), ENT_QUOTES, 'UTF-8'));
    }

    /** @return array{count: int, files: list<string>} */
    public function parseValue(string $value): array
    {
        $files = explode("\n", str_replace("\r", '', $value));

        return ['count' => count($files), 'files' => $files];
    }

    public function resolveSignature(string $directory, string $fileName): ?string
    {
        if ($fileName === '') {
            return null;
        }

        if ($fileName !== basename($fileName)) {
            return null;
        }

        $path = rtrim($directory, '/\\') . '/' . $fileName;

        return is_file($path) ? $path : null;
    }
}
