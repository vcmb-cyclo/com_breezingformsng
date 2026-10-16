<?php

/**
 * BreezingForms NG - A Joomla Forms Application
 *
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 *
 * SPDX-License-Identifier: GPL-2.0-or-later
 **/

declare(strict_types=1);

namespace Vcmb\Component\BreezingFormsNG\Tests\Site\Compatibility;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PublicFacadeApiTest extends TestCase
{
    private const ROOT = __DIR__ . '/../../..';

    /**
     * @return iterable<string, array{string, list<string>}>
     */
    public static function publicApiProvider(): iterable
    {
        yield 'BFRequest' => [
            'administrator/components/com_breezingformsng/plugins/bfcompat/src/Compat/BFRequest.php',
            [
                'getUri',
                'getMethod',
                'getVar',
                'getInt',
                'getUInt',
                'getFloat',
                'getBool',
                'getWord',
                'getCmd',
                'getString',
                'setVar',
                'get',
                'set',
                'checkToken',
            ],
        ];
        yield 'BFIntegrate' => [
            'components/com_breezingformsng/src/Service/Integration/IntegratorRuntime.php',
            [
                '__construct',
                'getRules',
                'getItems',
                'getCriteria',
                'getCriteriaJoomla',
                'getCriteriaFixed',
                'field',
                'handleCode',
                'handleFinalizeCode',
                'commit',
                'collectCriteria',
            ],
        ];
        yield 'BFQuickMode' => [
            'components/com_breezingformsng/src/Service/Rendering/QuickMode/ClassicRenderer.php',
            ['process', 'render', 'parseToggleFields'],
        ];
        yield 'BFQuickModeBootstrap' => [
            'components/com_breezingformsng/src/Service/Rendering/QuickMode/BootstrapRenderer.php',
            ['getEditorContent', 'parseToggleFields', 'render', 'process'],
        ];
        yield 'BFQuickModeOnePage' => [
            'components/com_breezingformsng/src/Service/Rendering/QuickMode/OnePageRenderer.php',
            ['getEditorContent', 'process', 'render', 'parseToggleFields'],
        ];
    }

    #[DataProvider('publicApiProvider')]
    public function testPublicApiMethodsRemainAvailable(string $path, array $expectedMethods): void
    {
        $source = $this->read($path);

        preg_match_all('/public\\s+(?:static\\s+)?function\\s+(\\w+)\\s*\\(/', $source, $matches);

        foreach ($expectedMethods as $method) {
            self::assertContains($method, $matches[1], "Missing public method {$method} in {$path}");
        }
    }

    public function testCompatibilityPluginMapsRequestAndIntegratorFacades(): void
    {
        $source = $this->read(
            'administrator/components/com_breezingformsng/plugins/bfcompat/src/Extension/CompatibilityLoader.php'
        );

        self::assertStringContainsString("'BFRequest' => 'BFRequest.php'", $source);
        self::assertStringContainsString("'BFIntegrate' => 'BFIntegrate.php'", $source);
    }

    public function testStatefulFacadeDelegatesAreMarkedImpureForStaticAnalysis(): void
    {
        $source = $this->read('components/com_breezingformsng/src/Support/processor_facade.php');

        foreach (['cbCheckPermissions', 'collectSubmitdata', 'logToDatabase', 'execPiece', 'linkcode', 'trim', 'nonblank', 'bury'] as $method) {
            self::assertMatchesRegularExpression(
                // @phpstan-impure anywhere in the docblock right above the method.
                '/@phpstan-impure\b(?:(?!\*\/).)*\*\/\s*public function ' . $method . '\s*\(/s',
                $source,
                "Missing impure contract for {$method}()"
            );
        }
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function quickModeFacadeProvider(): iterable
    {
        yield 'classic' => ['BFQuickMode', 'ClassicRenderer'];
        yield 'bootstrap' => ['BFQuickModeBootstrap', 'BootstrapRenderer'];
        yield 'one page' => ['BFQuickModeOnePage', 'OnePageRenderer'];
    }

    #[DataProvider('quickModeFacadeProvider')]
    public function testQuickModeFacadeExtendsNativeRenderer(string $facade, string $renderer): void
    {
        $source = $this->read(
            "administrator/components/com_breezingformsng/libraries/crosstec/classes/{$facade}.php"
        );

        self::assertStringContainsString("class {$facade} extends {$renderer}", $source);
    }

    public function testFrontendBootstrapLoadsEveryQuickModeFacade(): void
    {
        $source = $this->read('components/com_breezingformsng/breezingformsng.php');

        foreach (['BFQuickMode', 'BFQuickModeBootstrap', 'BFQuickModeOnePage'] as $facade) {
            self::assertStringContainsString("/{$facade}.php'", $source);
        }
    }

    private function read(string $path): string
    {
        $source = file_get_contents(self::ROOT . '/' . $path);

        self::assertNotFalse($source, "Unable to read {$path}");

        return $source;
    }
}
