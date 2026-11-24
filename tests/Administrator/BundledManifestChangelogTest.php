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
 * The plugins and the module are released with the component, in the same package and at the same version: they
 * show the changelog of the component, which the Extensions screen links.
 */
final class BundledManifestChangelogTest extends TestCase
{
    private const ROOT = __DIR__ . '/../..';

    /** @return array<string, array{0: string}> */
    public static function bundledManifests(): array
    {
        $base = self::ROOT . '/administrator/components/com_breezingformsng';
        $manifests = array_merge(
            glob($base . '/plugins/*/*.xml') ?: [],
            glob($base . '/plugins/*/*/*.xml') ?: [],
            glob($base . '/modules/*/*.xml') ?: []
        );
        $found = [];

        foreach ($manifests as $manifest) {
            $found[substr($manifest, strlen($base) + 1)] = [$manifest];
        }

        return $found;
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('bundledManifests')]
    public function testEveryBundledExtensionDeclaresTheChangelogOfTheComponent(string $manifest): void
    {
        $component = simplexml_load_file(self::ROOT . '/breezingformsng.xml');
        $extension = simplexml_load_file($manifest);

        self::assertNotFalse($component);
        self::assertNotFalse($extension);
        self::assertNotSame('', trim((string) $component->changelogurl));
        self::assertSame(trim((string) $component->changelogurl), trim((string) $extension->changelogurl), $manifest);
    }
}
