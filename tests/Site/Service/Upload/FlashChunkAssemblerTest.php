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

    public function testOrderedChunksAreAssembledAndTheChunkIsKept(): void
    {
        $final = $this->directory . '/final';
        $assembler = new FlashChunkAssembler();

        self::assertSame(FlashChunkAssembler::OK, $assembler->appendAt($this->chunk('abc'), $final, $this->directory, 0, 10));
        self::assertSame(FlashChunkAssembler::OK, $assembler->appendAt($this->chunk('def'), $final, $this->directory, 3, 10));
        self::assertSame('abcdef', file_get_contents($final));
    }

    public function testRepeatedChunkIsRejectedAndPartialFileDeleted(): void
    {
        $final = $this->directory . '/final';
        $chunk = $this->chunk('abc');
        $assembler = new FlashChunkAssembler();
        $assembler->appendAt($chunk, $final, $this->directory, 0, 100);
        $assembler->appendAt($this->chunk('def'), $final, $this->directory, 3, 100);

        $repeat = $this->chunk('def');
        self::assertSame(FlashChunkAssembler::OUT_OF_ORDER, $assembler->appendAt($repeat, $final, $this->directory, 3, 100));
        self::assertFileDoesNotExist($final);
        self::assertFileDoesNotExist($repeat);
    }

    public function testOutOfOrderOffsetsAreRejected(): void
    {
        $final = $this->directory . '/final';
        $assembler = new FlashChunkAssembler();
        $assembler->appendAt($this->chunk('abc'), $final, $this->directory, 0, 100);

        self::assertSame(FlashChunkAssembler::OUT_OF_ORDER, $assembler->appendAt($this->chunk('xyz'), $final, $this->directory, 9, 100));
        self::assertFileDoesNotExist($final);
        self::assertSame(FlashChunkAssembler::OUT_OF_ORDER, $assembler->appendAt($this->chunk('xyz'), $final, $this->directory, 3, 100));
        self::assertSame(FlashChunkAssembler::OUT_OF_ORDER, $assembler->appendAt($this->chunk('xyz'), $final, $this->directory, -1, 100));
    }

    public function testCumulativeSizeIsCheckedBeforeAppendingAndRejectionDeletesPartialFile(): void
    {
        $final = $this->directory . '/final';
        $assembler = new FlashChunkAssembler();
        self::assertSame(FlashChunkAssembler::OK, $assembler->appendAt($this->chunk('abcd'), $final, $this->directory, 0, 6));

        $over = $this->chunk('efg');
        self::assertSame(FlashChunkAssembler::TOO_LARGE, $assembler->appendAt($over, $final, $this->directory, 4, 6));
        self::assertFileDoesNotExist($final);
        self::assertFileDoesNotExist($over);
    }

    public function testOffsetZeroRestartsAStaleFile(): void
    {
        $final = $this->directory . '/final';
        file_put_contents($final, 'stale data');

        self::assertSame(FlashChunkAssembler::OK, (new FlashChunkAssembler())->appendAt($this->chunk('new'), $final, $this->directory, 0, 100));
        self::assertSame('new', file_get_contents($final));
    }

    public function testMissingChunkFailsAndRemovesPartialFile(): void
    {
        $final = $this->directory . '/final';
        file_put_contents($final, 'abc');

        self::assertSame(FlashChunkAssembler::FAILED, (new FlashChunkAssembler())->appendAt($this->directory . '/none', $final, $this->directory, 3, 100));
        self::assertFileDoesNotExist($final);
    }

    public function testRejectsPathsOutsideTheTargetDirectoryWithoutDeletingThem(): void
    {
        $outside = tempnam(sys_get_temp_dir(), 'bfng-outside-');
        self::assertIsString($outside);
        self::assertSame(4, file_put_contents($outside, 'safe'));

        try {
            self::assertSame(
                FlashChunkAssembler::FAILED,
                (new FlashChunkAssembler())->appendAt(
                    $this->chunk('abc'),
                    $outside,
                    $this->directory,
                    0,
                    100
                )
            );
            self::assertSame('safe', file_get_contents($outside));
        } finally {
            unlink($outside);
        }
    }

    private function chunk(string $content): string
    {
        $path = $this->directory . '/chunk_' . bin2hex(random_bytes(4));
        file_put_contents($path, $content);

        return $path;
    }
}
