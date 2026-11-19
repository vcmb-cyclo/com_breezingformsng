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
    public const OK = 'ok';
    public const FAILED = 'failed';
    public const TOO_LARGE = 'too_large';
    public const OUT_OF_ORDER = 'out_of_order';

    /**
     * Appends a chunk only when it starts exactly where the assembled file ends (ordered, unique offsets)
     * and the cumulative size stays within $maxBytes, checked BEFORE anything is written.
     * Offset 0 starts the file again. On any rejection the chunk and the partial file are deleted.
     *
     * @return self::OK|self::FAILED|self::TOO_LARGE|self::OUT_OF_ORDER
     */
    public function appendAt(
        string $chunkFile,
        string $finalFile,
        string $targetDirectory,
        int $offset,
        int $maxBytes
    ): string {
        clearstatcache();
        $chunkSize = is_file($chunkFile) ? filesize($chunkFile) : false;

        if ($chunkSize === false) {
            return $this->reject($chunkFile, $finalFile, self::FAILED);
        }

        if ($offset === 0 && is_file($finalFile)) {
            unlink($finalFile);
        }

        $current = is_file($finalFile) ? (int) filesize($finalFile) : 0;

        if ($offset < 0 || $current !== $offset) {
            return $this->reject($chunkFile, $finalFile, self::OUT_OF_ORDER);
        }

        if ($maxBytes > 0 && $current + $chunkSize > $maxBytes) {
            return $this->reject($chunkFile, $finalFile, self::TOO_LARGE);
        }

        if (!$this->append($chunkFile, $finalFile, $targetDirectory)) {
            return $this->reject($chunkFile, $finalFile, self::FAILED);
        }

        return self::OK;
    }

    /**
     * @param self::FAILED|self::TOO_LARGE|self::OUT_OF_ORDER $status
     * @return self::FAILED|self::TOO_LARGE|self::OUT_OF_ORDER
     */
    private function reject(string $chunkFile, string $finalFile, string $status): string
    {
        foreach ([$chunkFile, $finalFile] as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        return $status;
    }

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
