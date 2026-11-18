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

/** The "carte" Bootstrap theme: CSS only, scoped to the container of the form, portable. */
final class ThemeCarteCssTest extends TestCase
{
    private const ROOT = __DIR__ . '/../../';

    private function css(): string
    {
        return (string) file_get_contents(self::ROOT . 'media/com_breezingformsng/css/theme-carte.css');
    }

    public function testEverySelectorLivesUnderTheContainerOfTheForm(): void
    {
        $css = (string) preg_replace('~/\*.*?\*/~s', '', $this->css());
        preg_match_all('~([^{}]+)\{~', $css, $matches);

        self::assertNotEmpty($matches[1]);
        foreach ($matches[1] as $header) {
            foreach (explode(',', trim($header)) as $selector) {
                self::assertStringStartsWith('.bfFormDiv', trim($selector), 'Outside the scope: ' . trim($selector));
            }
        }
    }

    public function testItIsPortable(): void
    {
        $css = $this->css();

        self::assertStringNotContainsString('!important', $css);
        self::assertStringNotContainsString('@import', $css);
        self::assertStringNotContainsString('@font-face', $css);
        self::assertDoesNotMatchRegularExpression('~https?://(?!www\.w3\.org)~', $css);
        // The native input of a choice stays in the page and focusable: never display:none.
        self::assertDoesNotMatchRegularExpression('~\.form-check-input[^{]*\{[^}]*display:\s*none~', $css);
    }

    public function testItKeepsTheAccessibleStates(): void
    {
        $css = $this->css();

        foreach ([':focus-visible', ':disabled', '.is-invalid', '.invalid-feedback', 'input:checked'] as $state) {
            self::assertStringContainsString(str_replace('input:checked', ':checked', $state), $css);
        }
        // Reset before Send, and no solid fill on the main button.
        self::assertStringContainsString('order: -1;', $css);
    }

    public function testItIsAWebAssetLoadedOnlyForTheFormsThatChoseIt(): void
    {
        $assets = (string) file_get_contents(self::ROOT . 'media/com_breezingformsng/joomla.asset.json');
        $loader = (string) file_get_contents(
            self::ROOT . 'components/com_breezingformsng/src/Service/Runtime/RuntimeAssetLoader.php'
        );
        $renderer = (string) file_get_contents(
            self::ROOT . 'components/com_breezingformsng/src/Service/Rendering/QuickMode/BootstrapRenderer.php'
        );
        $model = (string) file_get_contents(
            self::ROOT . 'administrator/components/com_breezingformsng/src/Model/QuickmodeModel.php'
        );

        self::assertStringContainsString('"com_breezingformsng.theme-carte"', $assets);
        self::assertStringContainsString('media/com_breezingformsng/css/theme-carte.css', $assets);
        self::assertStringContainsString("SHIPPED_THEMES = ['carte']", $loader);
        self::assertStringContainsString("RuntimeAssetLoader::shippedTheme(\$this->p->app, (string) (\$this->rootMdata['themebootstrap']", $renderer);
        self::assertStringContainsString("['carte', ...", $model);
    }

    public function testTheSampleFormUsesIt(): void
    {
        $package = json_decode(
            (string) file_get_contents(
                self::ROOT . 'administrator/components/com_breezingformsng/packages/samples/bfng-sample-contact.json'
            ),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        self::assertSame('carte', $package['forms'][0]['data']['template_code']['properties']['themebootstrap']);
    }
}
