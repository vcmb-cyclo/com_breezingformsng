<?php

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Site\Service\Upload;

use Joomla\Filesystem\Folder;

/** Staging area for chunked uploads, inside Joomla's temporary folder. */
final class TemporaryUploadDirectory
{
    public function __construct(private readonly string $joomlaTemporaryPath)
    {
    }

    public function path(): string
    {
        return rtrim($this->joomlaTemporaryPath, '/\\') . '/breezingformsng/uploads/';
    }

    public function chunksPath(): string
    {
        return $this->path() . 'chunks/';
    }

    public function ensure(): bool
    {
        return is_dir($this->chunksPath()) || Folder::create($this->chunksPath());
    }
}
