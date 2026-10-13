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

namespace Vcmb\Component\BreezingformsNG\Site\Service\Upload;

/** Finds completed Flash uploads belonging to a field and upload ticket. */
final class FlashUploadFileMatcher
{
    /** @return list<array{path: string, filename: string}> */
    public function find(string $directory, string $fieldName, string $ticket): array
    {
        $matches = [];
        foreach (glob(rtrim($directory, '/\\') . '/*') ?: [] as $path) {
            if (!is_file($path)) {
                continue;
            }

            $filename = basename($path);
            $parts = explode('_', $filename);
            if (count($parts) < 5 || end($parts) !== 'flashtmp') {
                continue;
            }

            if ($parts[count($parts) - 3] !== $ticket || $parts[count($parts) - 4] !== $fieldName) {
                continue;
            }

            $matches[] = [
                'path' => $path,
                'filename' => $filename,
            ];
        }

        return $matches;
    }
}
