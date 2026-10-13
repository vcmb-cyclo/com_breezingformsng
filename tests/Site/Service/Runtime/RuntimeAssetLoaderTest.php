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

namespace Joomla\CMS\Uri {
    if (!class_exists(Uri::class)) {
        final class Uri
        {
            public static function root(bool $pathOnly = false): string
            {
                return $pathOnly ? '/subsite' : 'https://example.test/subsite/';
            }
        }
    }
}

namespace Vcmb\Component\BreezingformsNG\Tests\Site\Service\Runtime {

require_once __DIR__ . '/../Rendering/QuickMode/joomla-cmsapplication-stub.php';

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Uri\Uri;
use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Site\Service\Runtime\RuntimeAssetLoader;

final class RuntimeAssetLoaderTest extends TestCase
{
    public function testRegistersNormalizedScriptOnceAndActivatesIt(): void
    {
        $application = new CMSApplication();
        $attributes = ['defer' => true];

        $uri = rtrim(Uri::root(true), '/') . '/media/runtime.js';
        RuntimeAssetLoader::script($application, $uri, $attributes);
        RuntimeAssetLoader::script($application, $uri, $attributes);

        $registered = $this->registeredAssets($application);
        $name = 'com_breezingformsng.runtime.script.'
            . hash('sha256', 'media/runtime.js' . serialize($attributes));

        self::assertSame(['script:' . $name => true], $registered);
    }

    public function testRegistersStylesOutsideTheApplicationRootPath(): void
    {
        $application = new CMSApplication();
        $attributes = ['media' => 'print'];

        RuntimeAssetLoader::style($application, '/shared/runtime.css', $attributes);

        $registered = $this->registeredAssets($application);
        $name = 'com_breezingformsng.runtime.style.'
            . hash('sha256', 'shared/runtime.css' . serialize($attributes));

        self::assertArrayHasKey('style:' . $name, $registered);
        self::assertCount(1, $registered);
    }

    /**
     * @return array<string, true>
     */
    private function registeredAssets(CMSApplication $application): array
    {
        $manager = $application->getDocument()->getWebAssetManager();
        $reflection = new \ReflectionProperty($manager, 'registered');
        $reflection->setAccessible(true);

        /** @var array<string, true> $registered */
        $registered = $reflection->getValue($manager);

        return $registered;
    }
}
}
