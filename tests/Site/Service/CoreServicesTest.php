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

namespace Vcmb\Component\BreezingformsNG\Tests\Site\Service;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Site\Service\QuickMode\ElementFinder;
use Vcmb\Component\BreezingformsNG\Site\Service\QuickMode\TranslationResolver;
use Vcmb\Component\BreezingformsNG\Site\Service\Rendering\JavascriptValueExporter;
use Vcmb\Component\BreezingformsNG\Site\Service\Rendering\JavascriptCompressor;
use Vcmb\Component\BreezingformsNG\Site\Service\Rendering\ProcessorHeaderRenderer;
use Vcmb\Component\BreezingformsNG\Site\Service\Runtime\CodeStringTools;
use Vcmb\Component\BreezingformsNG\Site\Service\Runtime\FormDisplayContextResolver;
use Vcmb\Component\BreezingformsNG\Site\Service\Runtime\RequestParameterParser;
use Vcmb\Component\BreezingformsNG\Site\Service\Upload\ImageResizer;
use Vcmb\Component\BreezingformsNG\Site\Service\Upload\UploadError;
use Vcmb\Component\BreezingformsNG\Site\Service\Upload\UploadResult;

final class CoreServicesTest extends TestCase
{
    #[DataProvider('requestParameterProvider')]
    public function testParsesRequestParameters(string $input, array $expected): void
    {
        self::assertSame($expected, (new RequestParameterParser())->parse($input));
    }

    public static function requestParameterProvider(): array
    {
        return [
            'empty input' => ['', []],
            'plain parameters' => [' first = one &second=two ', ['first' => 'one', 'second' => 'two']],
            'html encoded separator' => ['first=one&amp;second=two', ['first' => 'one', 'second' => 'two']],
            'value containing equals sign' => ['query=a=b=c', ['query' => 'a=b=c']],
            'empty parameter names are ignored' => ['=ignored&& valid = value ', ['valid' => 'value']],
        ];
    }

    public function testTrimsCodeInPlaceAndReportsWhetherCodeRemains(): void
    {
        $tools = new CodeStringTools();
        $code = "  alert('ok');\n";

        self::assertTrue($tools->trimInPlace($code));
        self::assertSame("alert('ok');", $code);

        $whitespace = " \t\n";
        self::assertFalse($tools->trimInPlace($whitespace));
        self::assertSame('', $whitespace);
    }

    #[DataProvider('whitespaceProvider')]
    public function testDetectsNonWhitespaceCode(string $code, bool $expected): void
    {
        self::assertSame($expected, (new CodeStringTools())->containsNonWhitespace($code));
    }

    public static function whitespaceProvider(): array
    {
        return [
            'empty' => ['', false],
            'spaces and line breaks' => [" \t\r\n", false],
            'zero' => ['0', true],
            'comment' => ['// comment', true],
        ];
    }

    public function testFindsNestedElement(): void
    {
        $element = [
            'attributes' => ['id' => 'nested'],
            'properties' => ['type' => 'element', 'bfName' => 'email'],
        ];
        $node = ['children' => [['children' => [$element]]]];

        self::assertSame($element, (new ElementFinder())->find($node, 'email'));
        self::assertNull((new ElementFinder())->find($node, 'missing'));
    }

    public function testResolvesDefaultAndTranslatedFormTitles(): void
    {
        $resolver = new TranslationResolver();
        $data = ['properties' => [
            'title_translationzz-ZZ' => 'Default title',
            'title_translationfr-FR' => 'Titre français',
        ]];

        self::assertSame('Default title', $resolver->formTitle($data, 'en-GB', 'en-GB'));
        self::assertSame('Titre français', $resolver->formTitle($data, 'fr-FR', 'en-GB'));
    }

    public function testResolvesNestedFieldTranslation(): void
    {
        $resolver = new TranslationResolver();
        $data = ['children' => [[
            'attributes' => [],
            'properties' => [
                'type' => 'element',
                'bfName' => 'email',
                'label_translationfr-FR' => 'Adresse e-mail',
            ],
        ]]];

        self::assertSame('Adresse e-mail', $resolver->field($data, 'label', ' email ', 'fr-FR', 'en-GB'));
        self::assertNull($resolver->field($data, 'label', 'name', 'fr-FR', 'en-GB'));
    }

    public function testExportsJavascriptValuesAndVariables(): void
    {
        $exporter = new JavascriptValueExporter();

        self::assertSame('null', $exporter->exportValue(null));
        self::assertSame('true', $exporter->exportValue(true));
        self::assertSame('42', $exporter->exportValue(42));
        self::assertSame("'line\\n'+\r\n'\\'quote\\\\\\074'", $exporter->exportValue("line\n'quote\\<"));
        self::assertStringContainsString("'value'", $exporter->exportVariable('answer', 'value'));
        self::assertStringStartsWith("items = [\r\n", $exporter->exportVariable('items', ['first']));
    }

    public function testExportsNestedArraysAndObjectsWithIndentation(): void
    {
        $value = (object) [
            'enabled' => true,
            'items' => ['one', 2],
        ];

        $javascript = (new JavascriptValueExporter())->exportValue($value);

        self::assertStringContainsString("enabled:\r\n\ttrue", $javascript);
        self::assertStringContainsString("items:\r\n\t[\r\n\t\t'one',\r\n\t\t2\r\n\t]", $javascript);
    }

    public function testRendersProcessorHeaderUncompressedAndCompressed(): void
    {
        $renderer = new ProcessorHeaderRenderer(new JavascriptValueExporter());
        $variables = ['answer' => 42];

        $plain = $renderer->render($variables, false, static fn (string $code): string => $code);
        self::assertStringContainsString('ff_processor = new Object();', $plain);
        self::assertStringContainsString('answer = 42;', $plain);

        $compressed = $renderer->render($variables, true, static fn (string $code): string => 'compressed:' . $code);
        self::assertStringContainsString('compressed:ff_processor = new Object();', $compressed);
    }

    #[DataProvider('byteSizeProvider')]
    public function testParsesUploadByteSizes(string $value, int|float $expected): void
    {
        self::assertSame($expected, (new ImageResizer())->parseByteSize($value));
    }

    public static function byteSizeProvider(): array
    {
        return [
            'kilobytes' => ['2k', 2.0 * 1024],
            'megabytes with whitespace' => [' 1M ', 1048576.0],
            'gigabytes' => ['1g', 1024.0 * 1048576],
            'empty value uses safe default' => ['', 8 * 1048576],
            'unsupported unit uses safe default' => ['100b', 8 * 1048576],
        ];
    }

    public function testCompressesJavascriptWhilePreservingStrings(): void
    {
        $javascript = "var value = 1; // ignored\nvar text = 'a b'; /* ignored */";

        self::assertSame(
            "var value=1;var text='a b';\n",
            (new JavascriptCompressor())->compress($javascript, 80, "\n")
        );
    }

    public function testCompressorPreservesCommentMarkersInsideStrings(): void
    {
        $javascript = "var url = 'https://example.test/a//b'; /* removed */ var value = 2;";

        $compressed = (new JavascriptCompressor())->compress($javascript, 80, "\n");

        self::assertSame("var url='https://example.test/a//b';var value=2;\n", $compressed);
    }

    public function testBuildsSuccessfulAndFailedUploadResults(): void
    {
        $success = UploadResult::success('/files/example.pdf', '/var/www/files/example.pdf');
        self::assertTrue($success->isSuccessful());
        self::assertSame('/files/example.pdf', $success->path);
        self::assertNull($success->error);

        $failure = UploadResult::failure(UploadError::MoveFailed);
        self::assertFalse($failure->isSuccessful());
        self::assertSame(UploadError::MoveFailed, $failure->error);
        self::assertSame('', $failure->path);
    }

    #[DataProvider('displayContextProvider')]
    public function testResolvesFormDisplayContext(
        int $runMode,
        bool $inFrame,
        int $formRunMode,
        bool $published,
        int $previewMode,
        bool $gridEnabled,
        int $gridSize,
        array $expected
    ): void {
        $context = (new FormDisplayContextResolver())->resolve(
            $runMode,
            $inFrame,
            12,
            $formRunMode,
            $published,
            $previewMode,
            $gridEnabled,
            $gridSize,
            'https://example.test'
        );

        self::assertSame($expected, [
            $context->inline,
            $context->template,
            $context->formId,
            $context->homepage,
            $context->showGrid,
            $context->canRun,
        ]);
    }

    public static function displayContextProvider(): array
    {
        return [
            'frontend form allowed' => [
                0, false, 0, true, 0, false, 1,
                [0, 0, 'ff_form12', 'https://example.test', false, true],
            ],
            'frontend form rejected when backend only' => [
                0, false, 1, true, 0, false, 1,
                [0, 0, 'ff_form12', 'https://example.test', false, false],
            ],
            'frontend form rejected when unpublished' => [
                0, false, 0, false, 0, false, 1,
                [0, 0, 'ff_form12', 'https://example.test', false, false],
            ],
            'backend iframe uses administrator homepage' => [
                1, true, 2, true, 0, false, 1,
                [0, 1, 'ff_form12', 'https://example.test/administrator/index.php?tmpl=component', false, true],
            ],
            'backend non iframe uses component homepage' => [
                1, false, 1, true, 0, false, 1,
                [0, 1, 'ff_form12', 'index.php?tmpl=component', false, false],
            ],
            'preview enables grid and inline form' => [
                2, false, 0, false, 1, true, 2,
                [1, 1, 'adminForm', 'index.php?tmpl=component', true, false],
            ],
            'preview keeps grid hidden when disabled' => [
                2, false, 0, true, 1, false, 4,
                [1, 1, 'adminForm', 'index.php?tmpl=component', false, true],
            ],
        ];
    }
}
