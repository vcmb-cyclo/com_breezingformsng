<?php

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Administrator\Service;

\defined('_JEXEC') or die;

final class QuickmodeThemePath
{
    public static function resolve(string $root, string $theme): ?string
    {
        if (
            $theme === '' || $theme === '.' || $theme === '..'
            || str_contains($theme, '/') || str_contains($theme, '\\') || str_contains($theme, "\0")
        ) {
            return null;
        }

        $base = realpath($root);
        $directory = $root . '/' . $theme;

        if ($base === false || is_link($directory)) {
            return null;
        }

        $resolvedDirectory = realpath($directory);
        $file = $directory . '/vars.txt';

        if ($resolvedDirectory === false || dirname($resolvedDirectory) !== $base || is_link($file)) {
            return null;
        }

        $resolvedFile = realpath($file);

        return $resolvedFile !== false && dirname($resolvedFile) === $resolvedDirectory && is_file($resolvedFile)
            ? $resolvedFile
            : null;
    }
}
