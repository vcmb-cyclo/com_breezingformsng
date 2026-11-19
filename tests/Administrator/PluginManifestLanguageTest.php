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

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The About screen and the Joomla extension lists show the name of a plugin
 * through the key written in its manifest: that key must be defined in the
 * .sys.ini file of the plugin, in every language the plugin ships.
 */
final class PluginManifestLanguageTest extends TestCase
{
    /** @return iterable<string, array{string}> */
    public static function manifests(): iterable
    {
        $root = dirname(__DIR__, 2) . '/administrator/components/com_breezingformsng/plugins';

        foreach ((array) glob($root . '/*/*.xml') as $manifest) {
            yield basename(dirname($manifest)) . '/' . basename($manifest) => [$manifest];
        }

        foreach ((array) glob($root . '/*/*/*.xml') as $manifest) {
            yield basename(dirname(dirname($manifest))) . '/' . basename(dirname($manifest)) . '/' . basename($manifest) => [$manifest];
        }
    }

    #[DataProvider('manifests')]
    public function testThePluginNameAndDescriptionAreDefinedInEveryShippedLanguage(string $manifestPath): void
    {
        $xml = simplexml_load_file($manifestPath);
        self::assertNotFalse($xml, $manifestPath);

        if ((string) $xml['type'] !== 'plugin') {
            self::markTestSkipped('Not a plugin manifest.');
        }

        $name = trim((string) $xml->name);
        $description = trim((string) $xml->description);
        $folder = dirname($manifestPath) . '/' . (string) ($xml->languages['folder'] ?? 'language');
        $checked = 0;

        foreach ($xml->languages->language ?? [] as $language) {
            $file = trim((string) $language);

            if (!str_ends_with($file, '.sys.ini')) {
                continue;
            }

            $strings = parse_ini_file($folder . '/' . $file, false, INI_SCANNER_RAW);
            self::assertIsArray($strings, $file);
            self::assertArrayHasKey($name, $strings, $file . ' lacks the name key of the plugin');

            if ($description !== '' && str_starts_with($description, 'PLG_')) {
                self::assertArrayHasKey($description, $strings, $file . ' lacks the description key of the plugin');
            }

            $checked++;
        }

        self::assertGreaterThan(0, $checked, 'The manifest lists no .sys.ini file.');
    }
}
