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

namespace Vcmb\Component\BreezingformsNG\Tests\Site\Service\Upload;

use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Site\Service\Upload\TemporaryUploadDirectory;

final class TemporaryUploadDirectoryTest extends TestCase
{
    public function testPathsLiveInsideJoomlaTemporaryFolder(): void
    {
        $directory = new TemporaryUploadDirectory('/var/www/html/tmp/');

        self::assertSame('/var/www/html/tmp/breezingformsng/uploads/', $directory->path());
        self::assertSame('/var/www/html/tmp/breezingformsng/uploads/chunks/', $directory->chunksPath());
    }

    public function testNoRuntimeCodeStagesUploadsInTheComponentFolder(): void
    {
        $root = \dirname(__DIR__, 4) . '/components/com_breezingformsng/src';
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            self::assertStringNotContainsString(
                '/components/com_breezingformsng/uploads',
                (string) file_get_contents($file->getPathname()),
                $file->getPathname()
            );
        }
    }
}
