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

final class QuickmodeElementTypeIconsTest extends TestCase
{
    private const ROOT = __DIR__ . '/../..';
    private const COMPONENT = self::ROOT . '/administrator/components/com_breezingformsng';

    /** @var array<string, array{string, list<string>}> family key => [kind, types in menu order] */
    private const FAMILIES = [
        'INPUT' => ['input', ['Text', 'Textarea', 'NumberInput', 'Calendar', 'CalendarResponsive', 'Hidden', 'File']],
        'CHOICE' => ['struct', ['Checkbox', 'CheckboxGroup', 'RadioGroup', 'Select', 'Slider']],
        'SECURITY_PAYMENT' => ['logic', ['Captcha', 'ReCaptcha', 'Signature', 'PayPal', 'Stripe']],
        'ACTION_VIEW' => ['static', ['SubmitButton', 'Summarize']],
    ];

    public function testTypesAreGroupedByFamilyInTheSpecifiedOrder(): void
    {
        $layout = file_get_contents(self::COMPONENT . '/layouts/quickmode/properties_element.php');
        self::assertIsString($layout);
        self::assertStringContainsString('<option value="" disabled selected>', $layout);
        self::assertStringContainsString('<optgroup label=', $layout);

        preg_match_all("/'(COM_BREEZINGFORMSNG_ELEMENT_FAMILY_[A-Z_]+|bfElementType\\w+)' =>/", $layout, $matches);

        $expected = [];
        foreach (self::FAMILIES as $family => [, $types]) {
            $expected[] = 'COM_BREEZINGFORMSNG_ELEMENT_FAMILY_' . $family;
            foreach ($types as $type) {
                $expected[] = 'bfElementType' . $type;
            }
        }

        self::assertSame($expected, $matches[1]);
    }

    public function testEveryTypeHasItsOwnIconTintedByItsFamily(): void
    {
        $css = file_get_contents(self::ROOT . '/media/com_breezingformsng/css/bfng-admin.css');
        self::assertIsString($css);

        $icons = [];
        foreach (self::FAMILIES as [$kind, $types]) {
            foreach ($types as $type) {
                self::assertMatchesRegularExpression(
                    '/\.bfng-type-icon--bfElementType' . $type . ' \{\s*--bfng-type-icon: url\("(data:image\/svg\+xml,[^"]+)"\);\s*--bfng-type-color: var\(--bfng-kind-' . $kind . '\);/',
                    $css,
                    $type
                );
                preg_match('/\.bfng-type-icon--bfElementType' . $type . ' \{\s*--bfng-type-icon: url\("([^"]+)"/', $css, $icon);
                $icons[] = $icon[1];
            }
        }

        self::assertCount(19, array_unique($icons));
    }

    public function testFamilyLabelsAreTranslated(): void
    {
        foreach (['en-GB', 'fr-FR', 'de-DE', 'it-IT', 'es-ES', 'hu-HU', 'nl-NL', 'tr-TR'] as $language) {
            $ini = file_get_contents(self::COMPONENT . '/language/' . $language . '/com_breezingformsng.ini');
            self::assertIsString($ini);

            foreach (array_keys(self::FAMILIES) as $family) {
                self::assertMatchesRegularExpression('/^COM_BREEZINGFORMSNG_ELEMENT_FAMILY_' . $family . '="[^"]+"$/m', $ini, $language);
            }
        }
    }
}
