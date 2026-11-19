<?php

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Site\Service\Upload;

\defined('_JEXEC') or die;

use Joomla\Filesystem\File;

final class UploadDirectoryProtection
{
    /** Apache 2.4 rule first, Apache 2.2 (mod_access_compat) rule as the fallback. */
    private const HTACCESS_RULES = "<IfModule mod_authz_core.c>\n"
        . "    Require all denied\n"
        . "</IfModule>\n"
        . "<IfModule !mod_authz_core.c>\n"
        . "    deny from all\n"
        . "</IfModule>\n";

    /**
     * Writes the .htaccess and index.html guards that are missing from an existing directory.
     * Existing files are never overwritten: administrator rules stay as they are.
     */
    public static function ensure(string $directory, bool $enabled): void
    {
        if (!$enabled || !is_dir($directory)) {
            return;
        }

        $htaccess = $directory . '/.htaccess';
        if (!file_exists($htaccess)) {
            File::write($htaccess, self::HTACCESS_RULES);
        }

        $index = $directory . '/index.html';
        if (!file_exists($index)) {
            File::write($index, '<!DOCTYPE html><title></title>' . "\n");
        }
    }

    /** Protects the directory only when it lives under the given web root (the Joomla site root). */
    public static function ensureWithinWebRoot(string $directory, string $webRoot): void
    {
        self::ensure($directory, self::isWithin($directory, $webRoot));
    }

    public static function isWithin(string $directory, string $webRoot): bool
    {
        $realDirectory = realpath($directory);
        $realRoot = realpath($webRoot);

        if ($realDirectory === false || $realRoot === false) {
            return false;
        }

        $realRoot = rtrim($realRoot, '/\\') . DIRECTORY_SEPARATOR;

        return str_starts_with(rtrim($realDirectory, '/\\') . DIRECTORY_SEPARATOR, $realRoot);
    }
}
