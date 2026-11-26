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
                ]],
            ]],
        ]];

        self::assertSame(
            [['name' => 'Email', 'type' => 'bfTextfield', 'label' => 'E-mail', 'required' => true]],
            FormDebugContext::fields($tree)
        );
    }

    public function testBarEscapesDataAndSelectsTheAppliedTheme(): void
    {
        $html = $this->build([['theme' => 'a', 'url' => '/f?x=1&y="'], ['theme' => 'b', 'url' => '/f']]);

        self::assertStringContainsString('&lt;script&gt;', $html);
        self::assertStringNotContainsString('<script>', $html);
        self::assertStringContainsString('<option value="/f?x=1&amp;y=&quot;" selected>a</option>', $html);
        self::assertStringContainsString('b (COM_BREEZINGFORMSNG_DEBUG_BAR_THEME_CONFIGURED)', $html);
        self::assertStringContainsString('42&nbsp;ms', $html);
        self::assertStringContainsString('<td>JYES</td>', $html);
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
            'fields' => [['name' => 'Email', 'type' => 'bfTextfield', 'label' => 'E-mail', 'required' => true]],
        ]);
    }
}
