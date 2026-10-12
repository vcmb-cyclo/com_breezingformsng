<?php

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Site\Service\Integration;

/**
 * Tells whether the optional ContentBuilder NG component is installed.
 *
 * ContentBuilder NG renamed its manifest: releases up to 6.1.18 ship
 * com_contentbuilderng.xml only, 6.1.19 to 6.1.22.3 ship both names, and
 * later releases remove the old one. Both names are therefore accepted.
 */
final class ContentBuilderInstallation
{
    public const MANIFESTS = ['contentbuilderng.xml', 'com_contentbuilderng.xml'];

    public static function basePath(): string
    {
        return JPATH_ADMINISTRATOR . '/components/com_contentbuilderng';
    }

    public static function isInstalled(?string $basePath = null): bool
    {
        $basePath ??= self::basePath();

        foreach (self::MANIFESTS as $manifest) {
            if (is_file($basePath . '/' . $manifest)) {
                return true;
            }
        }

        return false;
    }
}
