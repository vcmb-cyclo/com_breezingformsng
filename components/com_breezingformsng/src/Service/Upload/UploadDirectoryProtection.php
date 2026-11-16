<?php

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Site\Service\Upload;

\defined('_JEXEC') or die;

use Joomla\Filesystem\File;

final class UploadDirectoryProtection
{
    public static function ensure(string $directory, bool $enabled): void
    {
        $file = $directory . '/.htaccess';

        if ($enabled && is_dir($directory) && !file_exists($file)) {
            File::write($file, 'deny from all');
        }
    }
}
