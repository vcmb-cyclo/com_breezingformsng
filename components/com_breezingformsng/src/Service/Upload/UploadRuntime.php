<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Site\Service\Upload;

\defined('_JEXEC') or die;

use Joomla\Input\Input;
use Vcmb\Component\BreezingformsNG\Site\Service\QuickMode\ElementFinder;

final class UploadRuntime
{
    private readonly ImageResizer $imageResizer;
    private readonly UploadPathResolver $pathResolver;
    private readonly UploadStorage $storage;
    private readonly ElementFinder $elementFinder;

    public function __construct(Input $input)
    {
        $this->imageResizer = new ImageResizer();
        $this->pathResolver = new UploadPathResolver($input);
        $this->storage = new UploadStorage($this->imageResizer);
        $this->elementFinder = new ElementFinder();
    }

    /**
     * @param array<int, string> $findTags
     * @param array<int, string> $replaceTags
     * @param array<int, \stdClass> $rows
     * @param array{username?: mixed, id?: mixed, name?: mixed} $identity
     */
    public function store(
        string $temporaryPath,
        string $clientFilename,
        string $destination,
        array $findTags,
        array $replaceTags,
        array $rows,
        string $submittedAt,
        string $timezone,
        array $identity,
        bool $preserveExistingFile,
        ?int $permissions,
        bool $useUrl,
        int $resizeWidth = 0,
        int $resizeHeight = 0,
        string $resizeType = '',
        ?string $resizeBackground = '#ffffff'
    ): UploadResult {
        $path = $this->pathResolver->resolve(
            $destination,
            $clientFilename,
            $findTags,
            $replaceTags,
            $rows,
            $submittedAt,
            $timezone,
            $identity
        );

        return $this->storage->store(
            $temporaryPath,
            $path['directory'],
            $path['filename'],
            $preserveExistingFile,
            $permissions,
            $useUrl,
            $resizeWidth,
            $resizeHeight,
            $resizeType,
            $resizeBackground
        );
    }

    public function imageType(string $filename): int|false
    {
        return $this->imageResizer->imageType($filename);
    }

    public function resizeFile(
        string $path,
        int $width,
        int $height,
        ?string $backgroundColor = '#ffffff',
        string $type = ''
    ): void {
        $this->imageResizer->resizeFile($path, $width, $height, $backgroundColor, $type);
    }

    /** @param list<int> $backgroundColor */
    public function resizeImage(
        mixed $sourceImage,
        int $destinationWidth,
        int $destinationHeight,
        int $type = 0,
        array $backgroundColor = [0, 0, 0]
    ): mixed {
        return $this->imageResizer->resizeImage(
            $sourceImage,
            $destinationWidth,
            $destinationHeight,
            $type,
            $backgroundColor
        );
    }

    public function parseByteSize(string $value): int|float
    {
        return $this->imageResizer->parseByteSize($value);
    }

    /**
     * @param array<string, mixed> $dataObject
     * @return array<string, mixed>|null
     */
    public function findQuickModeElement(array $dataObject, string $name): ?array
    {
        return $this->elementFinder->find($dataObject, $name);
    }
}
