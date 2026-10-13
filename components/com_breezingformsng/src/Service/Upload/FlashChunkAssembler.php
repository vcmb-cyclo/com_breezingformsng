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

use Joomla\Filesystem\File;

/** Appends a successfully uploaded chunk to its temporary final file. */
final class FlashChunkAssembler
{
    public function append(string $chunkFile, string $finalFile, string $targetDirectory): bool
    {
        if (!is_file($chunkFile) || !is_readable($chunkFile)) {
            return false;
        }

        $chunk = file_get_contents($chunkFile);
        if ($chunk === false) {
            return false;
        }

        if (is_writable($targetDirectory)) {
            return file_put_contents($finalFile, $chunk, FILE_APPEND) !== false;
        }

        $existing = '';
        if (is_file($finalFile)) {
            $existing = file_get_contents($finalFile);
            if ($existing === false) {
                return false;
            }
        }

        return File::write($finalFile, $existing . $chunk);
    }
}
