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

/** Removes expired temporary and payment-cache files. */
final class UploadFileCleaner
{
    private const MAX_AGE = 86400;

    public function purgeTemporaryUploads(string $directory, string $suffix, ?int $now = null): void
    {
        $this->purge(
            $directory,
            fn(string $filename, int $creationTime, int $currentTime): bool => $this->isExpiredTemporaryCandidate(
                $filename,
                $creationTime,
                $suffix,
                $currentTime
            ),
            $now
        );
    }

    public function purgePaymentCache(string $directory, ?int $now = null): void
    {
        $this->purge(
            $directory,
            fn(string $filename, int $creationTime, int $currentTime): bool => $this->isExpiredPaymentCacheCandidate(
                $filename,
                $creationTime,
                $currentTime
            ),
            $now
        );
    }

    public function isExpiredTemporaryCandidate(string $filename, int $creationTime, string $suffix, int $now): bool
    {
        return substr_count($filename, '_') >= 4
            && str_ends_with($filename, $suffix)
            && $now - $creationTime >= self::MAX_AGE;
    }

    public function isExpiredPaymentCacheCandidate(string $filename, int $creationTime, int $now): bool
    {
        return count(explode('_', $filename)) === 4
            && $now - $creationTime >= self::MAX_AGE;
    }

    /** @param callable(string, int, int): bool $isExpired */
    private function purge(string $directory, callable $isExpired, ?int $now): void
    {
        if (!is_dir($directory) || !is_readable($directory)) {
            return;
        }

        $now ??= time();
        foreach (new \DirectoryIterator($directory) as $file) {
            if (!$file->isFile() || !is_readable($file->getPathname())) {
                continue;
            }

            if (!$isExpired($file->getFilename(), $file->getCTime(), $now)) {
                continue;
            }

            File::delete($file->getPathname());
        }
    }
}
