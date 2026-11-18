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
    /**
     * @param array<string, mixed> $attributes
     */
    public static function script(CMSApplication $application, string $uri, array $attributes = []): void
    {
        $uri = self::normalizeUri($uri);
        $document = $application->getDocument() ?? throw new \RuntimeException('The document is not available.');
        $manager = $document->getWebAssetManager();
        $name = self::name('script', $uri, $attributes);

        if (!$manager->assetExists('script', $name)) {
            $manager->registerScript($name, $uri, [], $attributes);
        }

        $manager->useScript($name);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public static function style(CMSApplication $application, string $uri, array $attributes = []): void
    {
        $uri = self::normalizeUri($uri);
        $document = $application->getDocument() ?? throw new \RuntimeException('The document is not available.');
        $manager = $document->getWebAssetManager();
        $name = self::name('style', $uri, $attributes);

        if (!$manager->assetExists('style', $name)) {
            $manager->registerStyle($name, $uri, [], $attributes);
        }

        $manager->useStyle($name);
    }

    /** Bootstrap themes shipped with the component, as a web asset (the others are folders of media/breezingforms). */
    private const SHIPPED_THEMES = ['carte'];

    /**
     * Loads the stylesheet of a shipped Bootstrap theme ("carte"), registered in
     * the joomla.asset.json of the component, when the form chose it.
     */
    public static function shippedTheme(CMSApplication $application, string $theme): void
    {
        if (!in_array($theme, self::SHIPPED_THEMES, true)) {
            return;
        }

        $document = $application->getDocument() ?? throw new \RuntimeException('The document is not available.');
        $manager = $document->getWebAssetManager();
        $manager->getRegistry()->addExtensionRegistryFile('com_breezingformsng');
        $manager->useStyle('com_breezingformsng.theme-' . $theme);
    }

    /**
     * Joomla's own Font Awesome (the "fontawesome" web asset), for the icons
     * written as <i class="fa-solid fa-...">: a site template that does not load it
     * would show them blank. Joomla loads an asset once.
     */
    public static function fontAwesome(CMSApplication $application): void
    {
        $document = $application->getDocument() ?? throw new \RuntimeException('The document is not available.');
        $document->getWebAssetManager()->useStyle('fontawesome');
    }

    /**
     * @param array<string, mixed> $attributes
     */
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
