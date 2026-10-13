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
use Vcmb\Component\BreezingformsNG\Site\Service\Upload\ImageResizer;

final class ImageResizerTest extends TestCase
{
    public function testDetectsPngTypeAndRejectsMissingFile(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'bfng-image-');
        self::assertNotFalse($path);
        $image = imagecreatetruecolor(4, 3);
        self::assertTrue(imagepng($image, $path));
        imagedestroy($image);

        try {
            $resizer = new ImageResizer();
            self::assertSame(IMAGETYPE_PNG, $resizer->imageType($path));
            self::assertFalse($resizer->imageType($path . '-missing'));
        } finally {
            unlink($path);
        }
    }

    public function testCropResizingProducesRequestedDimensions(): void
    {
        $source = imagecreatetruecolor(4, 2);

        $resized = (new ImageResizer())->resizeImage($source, 3, 3, 1);

        self::assertSame(3, imagesx($resized));
        self::assertSame(3, imagesy($resized));
        imagedestroy($resized);
        imagedestroy($source);
    }

    public function testSimpleResizingPreservesAspectRatioWithinTarget(): void
    {
        $source = imagecreatetruecolor(4, 2);

        $resized = (new ImageResizer())->resizeImage($source, 3, 3, 3);

        self::assertSame(3, imagesx($resized));
        self::assertSame(2, imagesy($resized));
        imagedestroy($resized);
        imagedestroy($source);
    }

    public function testExactResizingUsesRequestedCanvas(): void
    {
        $source = imagecreatetruecolor(4, 2);

        $resized = (new ImageResizer())->resizeImage($source, 3, 3, 2);

        self::assertSame(3, imagesx($resized));
        self::assertSame(3, imagesy($resized));
        imagedestroy($resized);
        imagedestroy($source);
    }
}
