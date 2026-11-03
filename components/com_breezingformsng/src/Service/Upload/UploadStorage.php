<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Site\Service\Upload;

\defined('_JEXEC') or die;

use Closure;
use Joomla\CMS\Uri\Uri;
use Joomla\Filesystem\File;

final class UploadStorage
{
    private readonly Closure $fileMover;

    public function __construct(
        private readonly ImageResizer $imageResizer,
        ?Closure $fileMover = null
    ) {
        $this->fileMover = $fileMover ?? static fn (string $source, string $destination): bool =>
            move_uploaded_file($source, $destination);
    }

    public function store(
        string $temporaryPath,
        string $directory,
        string $filename,
        bool $preserveExistingFile,
        ?int $permissions,
        bool $useUrl,
        int $resizeWidth = 0,
        int $resizeHeight = 0,
        string $resizeType = '',
        ?string $resizeBackground = '#ffffff'
    ): UploadResult {
        if (!is_dir($directory)) {
            return UploadResult::failure(UploadError::DirectoryMissing);
        }

        $path = $directory . '/' . $filename;

        if (file_exists($path)) {
            if ($preserveExistingFile) {
                $path = $directory . '/' . bin2hex(random_bytes(16)) . '_' . $filename;

                if (file_exists($path)) {
                    return UploadResult::failure(UploadError::FileExists);
                }
            } elseif (!File::delete($path)) {
                return UploadResult::failure(UploadError::MoveFailed);
            }
        }

        if (!(($this->fileMover)($temporaryPath, $path))) {
            return UploadResult::failure(UploadError::MoveFailed);
        }

        if ($permissions !== null && !chmod($path, $permissions)) {
            return UploadResult::failure(UploadError::ChmodFailed);
        }

        if ($resizeWidth > 0 && $resizeHeight > 0) {
            $this->imageResizer->resizeFile(
                $path,
                $resizeWidth,
                $resizeHeight,
                $resizeBackground,
                $resizeType
            );
        }

        if (!$useUrl) {
            return UploadResult::success($path, $path);
        }

        $relativeDirectory = str_replace(JPATH_SITE . '/', '', $directory);
        $url = Uri::root() . rtrim($relativeDirectory, '/') . '/' . basename($path);

        return UploadResult::success($url, $path);
    }
}
