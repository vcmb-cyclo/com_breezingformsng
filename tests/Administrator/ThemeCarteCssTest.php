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
                $selector = trim($selector);

                if (str_starts_with($selector, '@media ')) {
                    self::assertMatchesRegularExpression('/^@media\s+\(.+\)$/', $selector);
                    continue;
                }

                self::assertTrue(
                    preg_match('/(?:^|\s)\.bfFormDiv(?:$|\s|\.|#|:|\[)/', $selector) === 1,
                    'Outside the scope: ' . $selector
                );
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
        self::assertStringContainsString('color: var(--bf-accent-ink);', $css);
        self::assertStringContainsString('display: block;', $css);
        self::assertStringContainsString('.bfErrorSummaryTitle', $css);
        self::assertStringContainsString('.bfErrorSummaryList a', $css);
    }

    public function testTheSectionsOfAPageAreFramed(): void
    {
        $css = $this->css();

        // Only the sections: a group of choices is a fieldset too, deeper in the page.
        self::assertMatchesRegularExpression('/\.bfPage > fieldset \{[^}]*border: 1px solid var\(--bf-line\);/', $css);
        self::assertMatchesRegularExpression('/\.bfPage > fieldset > legend \{[^}]*float: left;/', $css);
        self::assertMatchesRegularExpression('/\.bfPage > fieldset > legend \+ \* \{\s*clear: both;/', $css);
    }

    public function testBootstrapErrorSummaryTitlesExistInEverySiteLanguage(): void
    {
        foreach (['en-GB', 'fr-FR', 'de-DE', 'it-IT', 'es-ES', 'hu-HU', 'nl-NL', 'tr-TR'] as $language) {
            $ini = (string) file_get_contents(
                self::ROOT . 'components/com_breezingformsng/language/' . $language . '/com_breezingformsng.ini'
            );

            self::assertMatchesRegularExpression(
                '/^COM_BREEZINGFORMSNG_QM_ERROR_FIELD_TITLE_SINGULAR="[^"\\r\\n]+"$/m',
                $ini,
                $language
            );
            self::assertMatchesRegularExpression(
                '/^COM_BREEZINGFORMSNG_QM_ERROR_FIELD_TITLE_PLURAL="[^"\\r\\n]*%d[^"\\r\\n]+"$/m',
                $ini,
                $language
            );
        }
    }

    public function testItDefinesDarkColorsWhenBootstrapDarkModeIsActive(): void
    {
        $css = $this->css();

        self::assertStringContainsString('[data-bs-theme="dark"] .bfFormDiv', $css);
        self::assertStringContainsString('--bf-card: #1d232b;', $css);
        self::assertStringContainsString('--bf-field: #262d36;', $css);
        self::assertStringContainsString('--bf-select-arrow: #f2f2f0;', $css);
        self::assertStringContainsString('--bf-color-scheme: dark;', $css);
        self::assertStringContainsString('background-color: var(--bf-card);', $css);
        self::assertStringContainsString('background: var(--bf-danger-bg);', $css);
    }

    public function testSelectArrowUsesTheThemeColorVariable(): void
    {
        $css = $this->css();

        self::assertStringContainsString('--bf-select-arrow: #5b5853;', $css);
        self::assertStringContainsString('var(--bf-select-arrow)', $css);
        self::assertStringNotContainsString("stroke='%235b5853'", $css);
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
        self::assertStringContainsString('RuntimeAssetLoader::shippedTheme($this->p->app, $themeBootstrap);', $renderer);
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
