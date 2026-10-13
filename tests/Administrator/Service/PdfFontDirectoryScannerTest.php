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

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator\Service;

use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Administrator\Service\PdfFontDirectoryScanner;

final class PdfFontDirectoryScannerTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/bfng-pdf-scan-' . bin2hex(random_bytes(8));
        self::assertTrue(mkdir($this->directory));
    }

    protected function tearDown(): void
    {
        foreach (new \DirectoryIterator($this->directory) as $file) {
            if ($file->isFile()) {
                unlink($file->getPathname());
            } elseif (!$file->isDot()) {
                rmdir($file->getPathname());
            }
        }

        rmdir($this->directory);
    }

    public function testScanReturnsReadableRegularFilesAndExcludesDirectories(): void
    {
        self::assertNotFalse(file_put_contents($this->directory . '/custom.php', '<?php'));
        self::assertNotFalse(file_put_contents($this->directory . '/custom_active', ''));
        mkdir($this->directory . '/nested');

        $files = (new PdfFontDirectoryScanner())->scan($this->directory);

        self::assertContains('custom.php', $files);
        self::assertContains('custom_active', $files);
        self::assertNotContains('nested', $files);
    }

    public function testScanReturnsEmptyForMissingDirectory(): void
    {
        self::assertSame([], (new PdfFontDirectoryScanner())->scan($this->directory . '/missing'));
    }
}
