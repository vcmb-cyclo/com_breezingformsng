<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator;

use PHPUnit\Framework\TestCase;

/**
 * Joomla finds the classes of a module or plugin from the namespace its manifest declares, and a module finds
 * its dispatcher from the one its service provider names. A rename that changes one and not the other leaves the
 * extension installed and silently not working.
 */
final class BundledExtensionNamespaceTest extends TestCase
{
    private const ROOT = __DIR__ . '/../../administrator/components/com_breezingformsng';

    /** @return array<string, array{0: string}> */
    public static function extensions(): array
    {
        $found = [];

        foreach (['modules', 'plugins'] as $kind) {
            foreach (glob(self::ROOT . '/' . $kind . '/*/*.xml') ?: [] as $manifest) {
                $found[basename(dirname($manifest))] = [$manifest];
            }
            foreach (glob(self::ROOT . '/' . $kind . '/*/*/*.xml') ?: [] as $manifest) {
                $found[basename(dirname($manifest))] = [$manifest];
            }
        }

        return $found;
    }

    /** @dataProvider extensions */
    #[\PHPUnit\Framework\Attributes\DataProvider('extensions')]
    public function testTheClassesAndTheServiceProviderUseTheNamespaceOfTheManifest(string $manifest): void
    {
        $xml = simplexml_load_file($manifest);
        self::assertNotFalse($xml);
        $namespace = trim((string) ($xml->namespace ?? ''));

        if ($namespace === '') {
            self::markTestSkipped('The manifest declares no namespace.');
        }

        $directory = dirname($manifest);

        foreach (glob($directory . '/src/{,*/,*/*/}*.php', GLOB_BRACE) ?: [] as $file) {
            // Some files declare no namespace on purpose (the compatibility classes of the former API).
            if (preg_match('/^namespace\s+([^;]+);/m', (string) file_get_contents($file), $match) === 1) {
                self::assertStringStartsWith($namespace, $match[1], $file);
            }
        }

        $provider = (string) file_get_contents($directory . '/services/provider.php');
        preg_match_all('/Vcmb(?:\\\\{1,2}[A-Za-z]+)+/', $provider, $names);

        foreach ($names[0] as $name) {
            self::assertStringStartsWith(
                str_replace('\\\\', '\\', $namespace),
                str_replace('\\\\', '\\', $name),
                $directory . '/services/provider.php names ' . $name
            );
        }
    }

    public function testTheModuleAndThePluginTheFormerBreezingFormsNamedAreNotBundledAnymore(): void
    {
        self::assertDirectoryDoesNotExist(self::ROOT . '/modules/mod_breezingforms');
        self::assertDirectoryDoesNotExist(self::ROOT . '/plugins/breezingforms');
        self::assertFileExists(self::ROOT . '/modules/mod_breezingformsng/mod_breezingformsng.xml');
        self::assertFileExists(self::ROOT . '/plugins/breezingformsng/breezingformsng.xml');
    }
}
