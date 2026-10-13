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
use Vcmb\Component\BreezingformsNG\Site\Service\Upload\FlashChunkAssembler;

final class FlashChunkAssemblerTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/bfng-chunks-' . bin2hex(random_bytes(8));
        self::assertTrue(mkdir($this->directory));
    }

    protected function tearDown(): void
    {
        foreach (new \DirectoryIterator($this->directory) as $file) {
            if ($file->isFile()) {
                unlink($file->getPathname());
            }
        }

        rmdir($this->directory);
    }

    public function testAppendsChunkToExistingFinalFile(): void
    {
        $chunk = $this->directory . '/chunk';
        $final = $this->directory . '/final';
        self::assertSame(6, file_put_contents($chunk, ' world'));
        self::assertSame(5, file_put_contents($final, 'hello'));

        self::assertTrue((new FlashChunkAssembler())->append($chunk, $final, $this->directory));
        self::assertSame('hello world', file_get_contents($final));
    }

    public function testRejectsMissingChunk(): void
    {
        self::assertFalse((new FlashChunkAssembler())->append(
            $this->directory . '/missing',
            $this->directory . '/final',
            $this->directory
        ));
    }
}
