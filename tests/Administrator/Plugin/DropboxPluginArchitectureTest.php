<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator\Plugin;

use PHPUnit\Framework\TestCase;

final class DropboxPluginArchitectureTest extends TestCase
{
    private const ROOT = __DIR__ . '/../../..';

    public function testDropboxIsInstalledAsASeparateAddonPlugin(): void
    {
        $manifest = (string) file_get_contents(
            self::ROOT . '/administrator/components/com_breezingformsng/plugins/dropbox/dropbox.xml'
        );
        $installer = (string) file_get_contents(self::ROOT . '/script.php');

        self::assertStringContainsString('group="breezingforms_addons"', $manifest);
        self::assertStringContainsString('<folder plugin="dropbox">services</folder>', $manifest);
        self::assertStringContainsString("\$plugins['breezingforms_addons']", $installer);
        self::assertStringContainsString("'dropbox'", $installer);
    }

    public function testDropboxConsumesValidatedSubmissionArtifacts(): void
    {
        $plugin = (string) file_get_contents(
            self::ROOT . '/administrator/components/com_breezingformsng/plugins/dropbox/src/Extension/Dropbox.php'
        );
        $component = (string) file_get_contents(
            self::ROOT . '/components/com_breezingformsng/src/Service/Submission/SubmissionEngine.php'
        );

        self::assertStringContainsString('SubmissionArtifactsEvent::NAME', $plugin);
        self::assertStringContainsString('getArtifacts()', $plugin);
        self::assertStringContainsString('new DropboxUploader()', $plugin);
        self::assertStringNotContainsString('DropboxUploader', $component);
    }
}
