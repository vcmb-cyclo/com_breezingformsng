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

namespace Vcmb\Component\BreezingformsNG\Tests\Site\Service\Rendering;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Site\Service\Rendering\Debug\FormDebugBarBuilder;
use Vcmb\Component\BreezingformsNG\Site\Service\Rendering\Debug\FormDebugContext;

final class FormDebugBarTest extends TestCase
{
    /** @return array<string, array{mixed, bool, bool, bool}> */
    public static function activationCases(): array
    {
        return [
            'enabled for a Super User on the site' => [1, true, true, true],
            'column read as a string' => ['1', true, true, true],
            'debug mode off' => [0, true, true, false],
            'column absent' => [null, true, true, false],
            'administrator application' => [1, false, true, false],
            'any other visitor' => [1, true, false, false],
        ];
    }

    #[DataProvider('activationCases')]
    public function testActivationNeedsDebugModeSiteAndSuperUser(
        mixed $debugMode,
        bool $site,
        bool $superUser,
        bool $expected
    ): void {
        self::assertSame($expected, FormDebugContext::isActive($debugMode, $site, $superUser));
    }

    public function testOnlyAnInstalledThemeCanBeRequested(): void
    {
        $themes = FormDebugContext::installedThemes(__DIR__ . '/../../../../media/breezingforms/themes');

        self::assertContains('vcmb_j5b', $themes);
        self::assertSame('vcmb_j5b', FormDebugContext::requestedTheme('vcmb_j5b', $themes));

        foreach (['', 'unknown', '..', '../themes', 'vcmb_j5b/img'] as $requested) {
            self::assertNull(FormDebugContext::requestedTheme($requested, $themes), $requested);
        }
    }

    public function testFieldsAreCollectedFromTheTreeWithoutTheirValues(): void
    {
        $tree = ['properties' => ['type' => 'root'], 'children' => [
            ['properties' => ['type' => 'section'], 'children' => [
                ['properties' => [
                    'type' => 'element',
                    'bfName' => 'Email',
                    'bfType' => 'bfTextfield',
                    'label' => 'E-mail',
                    'required' => true,
                    'value' => 'secret',
                    'validationCondition' => 2,
                    'validationFunctionName' => 'ff_Email_validation',
                ]],
            ]],
        ]];

        self::assertSame(
            [[
                'name' => 'Email',
                'type' => 'bfTextfield',
                'label' => 'E-mail',
                'required' => true,
                'validation' => 'ff_Email_validation',
                'customValidation' => true,
            ]],
            FormDebugContext::fields($tree)
        );
    }

    public function testBootstrapRenderersOfferTheShippedThemeAndNoTheme(): void
    {
        self::assertSame(['none', 'carte'], FormDebugContext::bootstrapThemes(__DIR__ . '/missing'));
    }

    public function testAnUnavailableStoredThemeIsShownAsNoTheme(): void
    {
        self::assertSame('a', FormDebugContext::appliedTheme(null, 'a', ['a', 'b']));
        self::assertSame('b', FormDebugContext::appliedTheme('b', 'a', ['a', 'b']));
        self::assertSame('none', FormDebugContext::appliedTheme(null, 'gone', ['a']));
    }

    public function testClassicThemesAreOfferedInTheirOwnGroup(): void
    {
        $choices = FormDebugContext::classicChoices(['vcmb_j6']);

        self::assertSame(['classic.vcmb_j6'], $choices);
        self::assertTrue(FormDebugContext::isClassicChoice($choices[0]));
        self::assertSame('vcmb_j6', FormDebugContext::themeName($choices[0]));
        self::assertSame('carte', FormDebugContext::themeName('carte'));

        $html = $this->build([['theme' => 'carte', 'url' => '/a'], ['theme' => $choices[0], 'url' => '/b']]);

        self::assertStringContainsString(
            '<optgroup label="Classic"><option value="/b">vcmb_j6</option></optgroup></select>',
            $html
        );
    }

    public function testOnlyKnownColorModesAreAccepted(): void
    {
        self::assertSame('dark', FormDebugContext::colorMode('dark'));
        self::assertSame('auto', FormDebugContext::colorMode('<script>'));
    }

    public function testBarEscapesDataAndSelectsTheAppliedTheme(): void
    {
        $html = $this->build([['theme' => 'a', 'url' => '/f?x=1&y="'], ['theme' => 'b', 'url' => '/f']]);

        self::assertStringContainsString('&lt;script&gt;', $html);
        self::assertStringNotContainsString('<script>', $html);
        self::assertStringContainsString('<option value="/f?x=1&amp;y=&quot;" selected>a</option>', $html);
        self::assertStringContainsString('>b *</option>', $html);
        self::assertStringContainsString('<a href="/f?c=dark" title="COM_BREEZINGFORMSNG_DEBUG_BAR_COLOR_DARK"', $html);
        self::assertStringContainsString('aria-current="true"', $html);
        self::assertStringContainsString('<code>ff_Email_validation</code>', $html);
        self::assertStringContainsString('42&nbsp;ms', $html);
        self::assertStringContainsString('<td>JYES</td>', $html);
    }

    public function testHelpIsAButtonWithItsOwnTooltip(): void
    {
        $html = $this->build([]);

        self::assertStringContainsString(
            '<button type="button" class="bfng-debug__help" aria-label="COM_BREEZINGFORMSNG_DEBUG_BAR_HELP">',
            $html
        );
        self::assertStringContainsString(
            '<span class="bfng-debug__tooltip" aria-hidden="true">COM_BREEZINGFORMSNG_DEBUG_BAR_HELP</span></span>',
            $html
        );
    }

    public function testFieldNamesAreShownUnlessTheAddressHidesThem(): void
    {
        self::assertTrue(FormDebugContext::showFieldNames(''));
        self::assertTrue(FormDebugContext::showFieldNames('unknown'));
        self::assertFalse(FormDebugContext::showFieldNames('0'));

        $html = $this->build([]);

        self::assertStringContainsString('data-bfng-debug-names="1"', $html);
        self::assertStringContainsString(
            '<input type="checkbox" data-bfng-debug-names-url="/f?n=0" checked> '
            . 'COM_BREEZINGFORMSNG_DEBUG_BAR_SHOW_FIELD_NAMES',
            $html
        );
    }

    public function testBarHasNoThemeListWhenTheRendererCannotChangeIt(): void
    {
        self::assertStringNotContainsString('<select', $this->build([]));
    }

    /** @param list<array{theme: string, url: string}> $options */
    private function build(array $options): string
    {
        return (new FormDebugBarBuilder(static fn (string $key): string => $key))->build([
            'formId' => 7,
            'formName' => '<script>',
            'account' => 'Admin (admin, #1)',
            'renderer' => 'Classic',
            'configuredTheme' => 'b',
            'appliedTheme' => 'a',
            'renderTime' => 42,
            'themeOptions' => $options,
            'colorMode' => 'dark',
            'colorOptions' => [['mode' => 'dark', 'url' => '/f?c=dark']],
            'fieldNames' => true,
            'fieldNamesUrl' => '/f?n=0',
            'fields' => [[
                'name' => 'Email',
                'type' => 'bfTextfield',
                'label' => 'E-mail',
                'required' => true,
                'validation' => 'ff_Email_validation',
                'customValidation' => false,
            ]],
        ]);
    }
}
