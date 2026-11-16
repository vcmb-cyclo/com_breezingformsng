<?php

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Site\Service\Upload;

\defined('_JEXEC') or die;

use Joomla\Filesystem\File;

final class FilemaskFilename
{
    public static function make(string $mask, string $extension): string
    {
        $name = File::makeSafe($mask);
        $extension = File::makeSafe($extension);

        return ($name === '' ? '__empty__' : $name) . '.' . $extension;
    }
}
