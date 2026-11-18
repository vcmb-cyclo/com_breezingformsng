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

use PHPUnit\Framework\TestCase;

/**
 * The sample form shipped with the component: a package file that every site
 * can import, with nothing of the site it was made on.
 */
final class SampleContactPackageTest extends TestCase
{
    private const FILE = __DIR__
        . '/../../administrator/components/com_breezingformsng/packages/samples/bfng-sample-contact.json';

    /** @return array<string, mixed> */
    private function package(): array
    {
        $payload = json_decode((string) file_get_contents(self::FILE), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($payload);

        return $payload;
    }

    public function testItIsAPackageOfOneFormWithItsLibraryScripts(): void
    {
        $package = $this->package();

        self::assertSame('breezingformsng-package', $package['format']);
        self::assertSame(3, $package['version']);
        self::assertSame([], $package['pieces']);
        self::assertSame([], $package['menus']);
        self::assertCount(1, $package['forms']);
        self::assertSame('Sample_Contact', $package['forms'][0]['data']['name']);
    }

    public function testEveryScriptTheFormReferencesIsInThePackage(): void
    {
        $package = $this->package();
        $form = $package['forms'][0];
        $bundled = array_map(static fn (array $script): int => (int) $script['source_id'], $package['scripts']);

        // The import remaps the ids of the bundled scripts: an id left outside would point to another item.
        self::assertNotSame([], $bundled);
        foreach ($form['elements'] as $element) {
            foreach (['script1', 'script2', 'script3'] as $slot) {
                if ((int) $element[$slot . 'cond'] > 0) {
                    self::assertContains((int) $element[$slot . 'id'], $bundled, $element['name'] . ' ' . $slot);
                }
            }
        }

        // No custom code: the form data holds no script or piece of its own.
        foreach (['script1', 'script2', 'piece1', 'piece2', 'piece3', 'piece4'] as $slot) {
            self::assertSame(0, (int) $form['data'][$slot . 'cond'], $slot);
        }

        // Only the library functions of BreezingForms are bundled.
        foreach ($package['scripts'] as $script) {
            self::assertMatchesRegularExpression('/^ff_[a-z_]+$/i', $script['data']['name']);
        }
    }

    public function testItHoldsNothingOfTheSiteItWasMadeOn(): void
    {
        $raw = (string) file_get_contents(self::FILE);

        self::assertDoesNotMatchRegularExpression('/vcmb|gn_[a-z_]+|<\?php|heolwenn|@[a-z0-9-]+\.[a-z]{2,}/i', $raw);
        $form = $this->package()['forms'][0];
        self::assertSame('', $form['data']['emailadr']);
        self::assertNull($form['data']['checked_out']);
        self::assertSame(0, (int) $form['data']['emailntf']);
    }

    public function testItShowsTheMainKindsOfFields(): void
    {
        $types = array_column($this->package()['forms'][0]['elements'], 'type');

        foreach (['Text', 'Radio Group', 'Select List', 'Checkbox Group', 'Textarea', 'Regular Button'] as $type) {
            self::assertContains($type, $types);
        }
    }
}
