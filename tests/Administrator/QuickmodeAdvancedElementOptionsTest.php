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

/** The "Advanced element options" panel and the script that saves it must agree. */
final class QuickmodeAdvancedElementOptionsTest extends TestCase
{
    private const ROOT = __DIR__ . '/../..';

    /** Types whose value is submitted: each one can be left out of the log and ordered. */
    private const VALUE_TYPES = [
        'Hidden', 'NumberInput', 'Text', 'Textarea', 'RadioGroup', 'Calendar', 'CalendarResponsive',
        'Signature', 'CheckboxGroup', 'Checkbox', 'Select', 'File',
    ];

    private static function read(string $path): string
    {
        $source = file_get_contents(self::ROOT . '/' . $path);
        self::assertIsString($source);

        return $source;
    }

    /** @return array<string, array{string}> */
    public static function valueTypes(): array
    {
        return array_combine(self::VALUE_TYPES, array_map(static fn (string $type): array => [$type], self::VALUE_TYPES));
    }

    #[DataProvider('valueTypes')]
    public function testEveryValueTypeCanSetLoggingAndOrderNumber(string $type): void
    {
        $layout = self::read('administrator/components/com_breezingformsng/layouts/quickmode/advanced_element.php');
        $script = self::read('media/com_breezingformsng/js/admin/quickmode-app-properties.js');

        foreach (['Logging', 'OrderNumber'] as $field) {
            $id = $this->fieldId($type, $field);

            self::assertStringContainsString('id="' . $id . '"', $layout, "$type has no $field field.");
            self::assertStringContainsString("JQuery('#$id')", $script, "$type does not save its $field field.");
        }
    }

    private function fieldId(string $type, string $field): string
    {
        return match ([$type, $field]) {
            ['Text', 'Logging'] => 'bfElementAdvancedLogging',
            ['Text', 'OrderNumber'] => 'bfElementOrderNumber',
            ['NumberInput', 'OrderNumber'] => 'bfElementNumberInputOrderNumber',
            default => 'bfElement' . $type . 'Advanced' . $field,
        };
    }

    public function testEverySavedAdvancedFieldExistsInThePanel(): void
    {
        $layout = self::read('administrator/components/com_breezingformsng/layouts/quickmode/advanced_element.php');
        $script = self::read('media/com_breezingformsng/js/admin/quickmode-app-properties.js');

        preg_match_all("/mdata\\.(?:logging|orderNumber) = JQuery\\('#(\\w+)'\\)/", $script, $matches);

        self::assertNotEmpty($matches[1]);

        foreach ($matches[1] as $id) {
            self::assertStringContainsString('id="' . $id . '"', $layout, "$id is saved but not in the panel.");
        }
    }
}
