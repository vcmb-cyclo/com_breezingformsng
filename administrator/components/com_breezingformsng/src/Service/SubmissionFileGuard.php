<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Administrator\Service;

\defined('_JEXEC') or die;

/**
 * Decides which files referenced by submitted values may be deleted.
 *
 * A stored value is only a string: it can have been edited, and the form may
 * have been reconfigured since. A file is removable only when it still sits
 * inside the directory its field writes to, so a value can never point the
 * deletion at an arbitrary file.
 */
final class SubmissionFileGuard
{
    public const FILE_UPLOAD = 'File Upload';
    public const SIGNATURE = 'Signature';

    private readonly string $siteRoot;

    /**
     * @param string $uploadsPattern Component "uploads" setting, the {ff_uploads} path.
     */
    public function __construct(string $siteRoot, private readonly string $uploadsPattern)
    {
        $this->siteRoot = rtrim($siteRoot, '/\\');
    }

    /**
     * Path of the file a stored value refers to, null when it names none.
     */
    public function locate(string $type, string $value): ?string
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        if ($type === self::SIGNATURE) {
            return $value === basename($value) && str_ends_with($value, '.png')
                ? $this->signaturesDirectory() . '/' . $value
                : null;
        }

        if ($type !== self::FILE_UPLOAD || preg_match('#^[a-z][a-z0-9+.-]*://#i', $value) === 1) {
            return null;
        }

        return str_ireplace('{cbsite}', $this->siteRoot, $value);
    }

    /**
     * @param string $directoryPattern Upload directory configured on the field (elements.data1).
     */
    public function isRemovable(string $type, string $path, string $directoryPattern): bool
    {
        $directory = $type === self::SIGNATURE
            ? realpath($this->signaturesDirectory())
            : $this->uploadRoot($directoryPattern);
        $file = realpath($path);

        if (!\is_string($directory) || $file === false || is_link($path) || !is_file($file)) {
            return false;
        }

        return str_starts_with($file, rtrim($directory, '/\\') . DIRECTORY_SEPARATOR);
    }

    /**
     * Fixed part of a field's upload directory: everything before its first
     * per-submission token. Null when that leaves the site root or one of its
     * parents, which is too wide to vouch for a file.
     */
    private function uploadRoot(string $directoryPattern): ?string
    {
        $uploads = str_ireplace(['{mospath}', '{cbsite}'], $this->siteRoot, $this->uploadsPattern);
        $directory = str_ireplace(
            ['{ff_uploads}', '{mospath}', '{cbsite}'],
            [$uploads, $this->siteRoot, $this->siteRoot],
            trim($directoryPattern)
        );
        $dynamic = strcspn($directory, '{|');

        // "|" closes a complete folder name, "{" may start in the middle of one.
        if ($dynamic < \strlen($directory) && $directory[$dynamic] === '{') {
            $directory = (string) preg_replace('#[^/\\\\]*$#', '', substr($directory, 0, $dynamic));
        } else {
            $directory = substr($directory, 0, $dynamic);
        }

        if ($directory === '') {
            return null;
        }

        $directory = realpath($directory);
        $siteRoot = realpath($this->siteRoot);

        if ($directory === false || $siteRoot === false) {
            return null;
        }

        return str_starts_with($siteRoot . DIRECTORY_SEPARATOR, rtrim($directory, '/\\') . DIRECTORY_SEPARATOR)
            ? null
            : $directory;
    }

    private function signaturesDirectory(): string
    {
        return $this->siteRoot . '/media/breezingforms/signatures';
    }
}
