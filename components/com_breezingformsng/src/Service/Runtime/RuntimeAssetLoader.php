<?php

/**
 * BreezingForms NG - A Joomla Forms Application
 *
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 *
 * SPDX-License-Identifier: GPL-2.0-or-later
 **/

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Site\Service\Runtime;

defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Uri\Uri;

final class RuntimeAssetLoader
{
    public static function script(CMSApplication $application, string $uri, array $attributes = []): void
    {
        $uri = self::normalizeUri($uri);
        $manager = $application->getDocument()->getWebAssetManager();
        $name = self::name('script', $uri, $attributes);

        if (!$manager->assetExists('script', $name)) {
            $manager->registerScript($name, $uri, [], $attributes);
        }

        $manager->useScript($name);
    }

    public static function style(CMSApplication $application, string $uri, array $attributes = []): void
    {
        $uri = self::normalizeUri($uri);
        $manager = $application->getDocument()->getWebAssetManager();
        $name = self::name('style', $uri, $attributes);

        if (!$manager->assetExists('style', $name)) {
            $manager->registerStyle($name, $uri, [], $attributes);
        }

        $manager->useStyle($name);
    }

    private static function name(string $type, string $uri, array $attributes): string
    {
        return 'com_breezingformsng.runtime.' . $type . '.' . hash('sha256', $uri . serialize($attributes));
    }

    private static function normalizeUri(string $uri): string
    {
        if (!str_starts_with($uri, '/')) {
            return $uri;
        }

        $rootPath = rtrim(Uri::root(true), '/');

        if ($rootPath !== '' && str_starts_with($uri, $rootPath . '/')) {
            return substr($uri, strlen($rootPath) + 1);
        }

        return ltrim($uri, '/');
    }
}
