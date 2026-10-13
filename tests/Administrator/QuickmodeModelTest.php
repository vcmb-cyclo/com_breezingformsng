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

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator;

require_once __DIR__ . '/../Support/joomla-base-database-model-stub.php';

use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Administrator\Model\QuickmodeModel;

final class QuickmodeModelTest extends TestCase
{
    private string $themeDirectory;

    protected function setUp(): void
    {
        $this->themeDirectory = sys_get_temp_dir() . '/bfng-themes-' . bin2hex(random_bytes(8));
        self::assertTrue(mkdir($this->themeDirectory));
    }

    protected function tearDown(): void
    {
        foreach (new \DirectoryIterator($this->themeDirectory) as $file) {
            if ($file->isFile()) {
                unlink($file->getPathname());
            } elseif (!$file->isDot()) {
                rmdir($file->getPathname());
            }
        }

        rmdir($this->themeDirectory);
    }

    public function testScanThemeDirKeepsOnlyThemeDirectories(): void
    {
        mkdir($this->themeDirectory . '/clean');
        mkdir($this->themeDirectory . '/images');
        mkdir($this->themeDirectory . '/img');
        mkdir($this->themeDirectory . '/.svn');
        self::assertNotFalse(file_put_contents($this->themeDirectory . '/README.txt', 'ignore'));

        $themes = $this->scanThemeDir($this->themeDirectory);

        self::assertSame(['clean'], $themes);
    }

    public function testScanThemeDirReturnsEmptyForMissingDirectory(): void
    {
        self::assertSame([], $this->scanThemeDir($this->themeDirectory . '/missing'));
    }

    private function scanThemeDir(string $folder): array
    {
        $model = (new \ReflectionClass(QuickmodeModel::class))->newInstanceWithoutConstructor();
        $method = new \ReflectionMethod($model, 'scanThemeDir');

        return $method->invoke($model, $folder);
    }
}
