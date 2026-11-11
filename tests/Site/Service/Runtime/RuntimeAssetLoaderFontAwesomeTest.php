<?php

/**
 * BreezingForms NG - A Joomla Forms Application
 *
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 *
 * SPDX-License-Identifier: GPL-2.0-or-later
 **/

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Site\Service\Runtime;

require_once __DIR__ . '/../Rendering/QuickMode/joomla-cmsapplication-stub.php';

use Joomla\CMS\Application\CMSApplication;
use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Site\Service\Runtime\RuntimeAssetLoader;

final class RuntimeAssetLoaderFontAwesomeTest extends TestCase
{
    public function testUsesJoomlasFontAwesomeAssetWithoutRegisteringACopy(): void
    {
        $application = new CMSApplication();

        RuntimeAssetLoader::fontAwesome($application);
        RuntimeAssetLoader::fontAwesome($application);

        $manager = $application->getDocument()->getWebAssetManager();
        // Joomla registers "fontawesome" itself and loads an asset once.
        self::assertSame(['fontawesome', 'fontawesome'], $manager->usedStyles);
        self::assertFalse($manager->assetExists('style', 'fontawesome'));
    }

    public function testBothBootstrapFamilyRenderersLoadIt(): void
    {
        $directory = __DIR__ . '/../../../../components/com_breezingformsng/src/Service/Rendering/QuickMode/';

        foreach (['BootstrapRenderer.php', 'OnePageRenderer.php'] as $renderer) {
            $source = (string) file_get_contents($directory . $renderer);

            self::assertStringContainsString('RuntimeAssetLoader::fontAwesome($this->p->app);', $source, $renderer);
            self::assertStringContainsString('<i class="fas ', $source, $renderer);
        }
    }
}
