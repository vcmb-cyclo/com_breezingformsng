<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Site\Service\Upload;

\defined('_JEXEC') or die;

use Joomla\Filesystem\File;

final class ImageResizer
{
    public function imageType(string $filename): int|false
    {
        if (!is_file($filename) || !is_readable($filename)) {
            return false;
        }

        if (\function_exists('exif_imagetype')) {
            return exif_imagetype($filename);
        }

        $image = getimagesize($filename);

        return $image === false ? false : $image[2];
    }

    public function resizeFile(
        string $path,
        int $width,
        int $height,
        ?string $backgroundColor = '#ffffff',
        string $type = ''
    ): void {
        if (!is_file($path) || !is_readable($path)) {
            return;
        }

        $image = getimagesize($path);

        if ($image === false || !$this->hasEnoughMemory($image)) {
            return;
        }

        $background = $backgroundColor === null
            ? null
            : [
                (int) hexdec(substr($backgroundColor, 1, 2)),
                (int) hexdec(substr($backgroundColor, 3, 2)),
                (int) hexdec(substr($backgroundColor, 5, 2)),
            ];
        $resizeType = $type === 'crop' ? 1 : ($type === 'simple' ? 3 : 2);

        $this->resizeByType($path, $this->imageType($path), $width, $height, $resizeType, $background);
    }

    /**
     * @param array<int, mixed> $backgroundColor
     */
    public function resizeImage(
        mixed $sourceImage,
        int $destinationWidth,
        int $destinationHeight,
        int $type = 0,
        array $backgroundColor = [0, 0, 0]
    ): mixed {
        $sourceWidth  = imagesx($sourceImage);
        $sourceHeight = imagesy($sourceImage);
        $sourceRatio  = $sourceWidth / $sourceHeight;

        if ($destinationHeight === 0 && $type === 3) {
            $destinationHeight = $sourceHeight;
        }

        $destinationRatio = $destinationWidth / $destinationHeight;

        if ($type === 3) {
            $scale = min($destinationWidth / $sourceWidth, $destinationHeight / $sourceHeight);
            $destinationWidth  = (int) ceil($scale * $sourceWidth);
            $destinationHeight = (int) ceil($scale * $sourceHeight);
            $newDestinationWidth  = $destinationWidth;
            $newDestinationHeight = $destinationHeight;
            $sourceX = $sourceY = $destinationX = $destinationY = 0;
        } elseif ($type === 1) {
            if ($sourceRatio > $destinationRatio) {
                $temporaryWidth  = (int) ($sourceHeight * $destinationRatio);
                $temporaryHeight = $sourceHeight;
                $sourceX = (int) (($sourceWidth - $temporaryWidth) / 2);
                $sourceY = 0;
            } else {
                $temporaryWidth  = $sourceWidth;
                $temporaryHeight = (int) ($sourceWidth * $destinationRatio);
                $sourceX = 0;
                $sourceY = (int) (($sourceHeight - $temporaryHeight) / 2);
            }

            $destinationX = $destinationY = 0;
            $sourceWidth = $temporaryWidth;
            $sourceHeight = $temporaryHeight;
            $newDestinationWidth = $destinationWidth;
            $newDestinationHeight = $destinationHeight;
        } else {
            if ($sourceRatio < $destinationRatio) {
                $temporaryWidth  = (int) ($destinationHeight * $sourceRatio);
                $temporaryHeight = $destinationHeight;
                $destinationX = (int) (($destinationWidth - $temporaryWidth) / 2);
                $destinationY = 0;
            } else {
                $temporaryWidth  = $destinationWidth;
                $temporaryHeight = (int) ($destinationWidth / $sourceRatio);
                $destinationX = 0;
                $destinationY = (int) (($destinationHeight - $temporaryHeight) / 2);
            }

            $sourceX = $sourceY = 0;
            $newDestinationWidth = $temporaryWidth;
            $newDestinationHeight = $temporaryHeight;
        }

        if ($destinationWidth < 1 || $destinationHeight < 1) {
            throw new \ValueError('Image dimensions must be greater than 0');
        }

        $destinationImage = imagecreatetruecolor($destinationWidth, $destinationHeight);

        if ($type === 2) {
            $color = imagecolorallocate(
                $destinationImage,
                $this->colorChannel($backgroundColor[0]),
                $this->colorChannel($backgroundColor[1]),
                $this->colorChannel($backgroundColor[2])
            );

            if ($color !== false) {
                imagefill($destinationImage, 0, 0, $color);
            }
        }

        imagecopyresampled(
            $destinationImage,
            $sourceImage,
            $destinationX,
            $destinationY,
            $sourceX,
            $sourceY,
            $newDestinationWidth,
            $newDestinationHeight,
            $sourceWidth,
            $sourceHeight
        );

        return $destinationImage;
    }

    public function parseByteSize(string $value): int|float
    {
        $value = trim($value);

        if ($value === '') {
            return 8 * 1048576;
        }

        $last = strtolower($value[strlen($value) - 1]);
        $number = (float) substr($value, 0, -1);

        return match ($last) {
            'g' => $number * 1024 * 1048576,
            'm' => $number * 1048576,
            'k' => $number * 1024,
            default => 8 * 1048576,
        };
    }

    /**
     * @param array<int|string, mixed> $image
     */
    /** @return int<0, 255> */
    private function colorChannel(mixed $value): int
    {
        $channel = (int) $value;

        if ($channel < 0 || $channel > 255) {
            throw new \ValueError('Image color channels must be between 0 and 255');
        }

        return $channel;
    }

    /** @param array<int|string, mixed> $image */
    private function hasEnoughMemory(array $image): bool
    {
        $channels = (int) ($image['channels'] ?? 0);
        $bits = (int) ($image['bits'] ?? 0);
        $memoryNeeded = (int) round(($image[0] * $image[1] * $bits * ($channels / 8) + 65536) * 1.5);
        $memoryLimit = ini_get('memory_limit') === false
            ? 8 * 1048576
            : $this->parseByteSize((string) ini_get('memory_limit'));

        return !\function_exists('memory_get_usage') || memory_get_usage() + $memoryNeeded <= $memoryLimit;
    }

    /**
     * @param array{int, int, int}|null $background
     */
    private function resizeByType(
        string $path,
        int|false $imageType,
        int $width,
        int $height,
        int $resizeType,
        ?array $background
    ): void {
        $loader = match ($imageType) {
            IMAGETYPE_JPEG2000, IMAGETYPE_JPEG => 'imagecreatefromjpeg',
            IMAGETYPE_GIF => 'imagecreatefromgif',
            IMAGETYPE_PNG => 'imagecreatefrompng',
            default => null,
        };
        $writer = match ($imageType) {
            IMAGETYPE_JPEG2000, IMAGETYPE_JPEG => 'imagejpeg',
            IMAGETYPE_GIF => 'imagegif',
            IMAGETYPE_PNG => 'imagepng',
            default => null,
        };

        if ($loader === null || $writer === null || !\function_exists($loader)) {
            return;
        }

        $source = @$loader($path);

        if ($source === false) {
            return;
        }

        $resized = $this->resizeImage($source, $width, $height, $resizeType, $background ?? [0, 0, 0]);

        ob_start();
        @$writer($resized);
        $buffer = ob_get_clean();

        if ($buffer !== false) {
            File::write($path, $buffer);
        }

        imagedestroy($resized);
        imagedestroy($source);
    }
}
