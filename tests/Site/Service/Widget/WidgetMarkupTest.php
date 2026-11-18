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

namespace Vcmb\Component\BreezingformsNG\Tests\Site\Service\Widget;

use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Site\Service\Rendering\QuickMode\QuickModeFileInputBuilder;
use Vcmb\Component\BreezingformsNG\Site\Service\Rendering\QuickMode\QuickModeTextFieldStrategy;
use Vcmb\Component\BreezingformsNG\Site\Service\Widget\WidgetFieldBuilder;
use Vcmb\Component\BreezingformsNG\Site\Service\Widget\WidgetOptions;

final class WidgetMarkupTest extends TestCase
{
    /**
     * @param array<string, mixed> $extra
     * @return array<string, mixed>
     */
    private static function field(array $extra = []): array
    {
        return $extra + [
            'bfType' => 'bfTextfield', 'bfName' => 'phone', 'dbId' => 12, 'value' => '', 'placeholder' => '',
            'maxLength' => '',
        ];
    }

    /** @param array<string, mixed> $field */
    private function render(array $field): string
    {
        return (new QuickModeTextFieldStrategy())->textfield($field, 'zz-ZZ', 'form-control', 'tabindex="1" ');
    }

    /** @return array<string, mixed> */
    private function options(string $html): array
    {
        if (preg_match('/data-bfng-options="([^"]*)"/', $html, $matches) !== 1) {
            self::fail('No data-bfng-options attribute.');
        }

        return json_decode(html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8'), true, 512, JSON_THROW_ON_ERROR);
    }

    public function testAPlainTextFieldKeepsItsHistoricalMarkup(): void
    {
        self::assertSame(
            '<input class="form-control" tabindex="1" type="text" name="ff_nm_phone[]" value="x" id="ff_elem12"/>' . "\n",
            $this->render(self::field(['value' => 'x']))
        );
        self::assertSame(
            '<input class="form-control" tabindex="1" type="text" name="ff_nm_phone[]" value="" id="ff_elem12"/>' . "\n",
            $this->render(self::field(['widget' => '']))
        );
        self::assertSame(
            '<input class="form-control" tabindex="1" type="text" name="ff_nm_phone[]" value="" id="ff_elem12"/>' . "\n",
            $this->render(self::field(['widget' => 'bogus']))
        );
        self::assertFalse(WidgetFieldBuilder::hasWidget(self::field(['widget' => 'bogus'])));
    }

    public function testPhoneKeepsAnInputWithItsOptionsAndRestoredValue(): void
    {
        $html = $this->render(self::field([
            'widget' => 'phone', 'widgetCountries' => 'fr, BE,xx', 'widgetShowFlags' => false, 'value' => '+33612345678',
        ]));

        self::assertStringContainsString('<input ', $html);
        self::assertStringContainsString('type="tel"', $html);
        self::assertStringContainsString('name="ff_nm_phone[]"', $html);
        self::assertStringContainsString('id="ff_elem12"', $html);
        self::assertStringContainsString('value="+33612345678"', $html);
        self::assertStringContainsString('data-bfng-widget="phone"', $html);
        self::assertStringContainsString('data-bfng-value="+33612345678"', $html);
        self::assertSame(
            ['defaultCountry' => 'fr', 'countries' => ['fr', 'be'], 'flags' => false, 'dialCode' => true, 'validate' => true],
            $this->options($html)
        );
    }

    public function testDatetimePicksTheNativeInputTypeFromTheMode(): void
    {
        foreach (['datetime' => 'datetime-local', 'date' => 'date', 'time' => 'time'] as $mode => $type) {
            $html = $this->render(self::field(['widget' => 'datetime', 'widgetMode' => $mode, 'widgetMin' => 'now']));
            self::assertStringContainsString('type="' . $type . '"', $html);
            self::assertSame($mode, $this->options($html)['mode']);
            self::assertSame('now', $this->options($html)['min']);
        }
        self::assertSame(
            ['mode' => 'datetime', 'min' => '', 'max' => '', 'step' => 15],
            $this->options($this->render(self::field(['widget' => 'datetime', 'widgetMin' => 'junk'])))
        );
    }

    public function testCountryRendersAnEmptySelectWithItsCurrentValue(): void
    {
        $html = $this->render(self::field(['widget' => 'country', 'value' => 'BE', 'widgetDefaultCountry' => '']));

        self::assertStringStartsWith('<select class="form-control" tabindex="1" name="ff_nm_phone[]" id="ff_elem12"', $html);
        self::assertStringContainsString('data-value="BE"', $html);
        self::assertStringEndsWith("></select>\n", $html);
        self::assertSame(['defaultCountry' => '', 'countries' => [], 'flags' => true], $this->options($html));
    }

    public function testAddressOptionsAndEscaping(): void
    {
        $html = $this->render(self::field([
            'widget' => 'address', 'widgetProvider' => 'ban', 'widgetMinChars' => '4',
            'widgetFill' => "street=Street\r\npostcode=Zip\nbogus=X\ncity=\"><script>",
            'value' => '"><b>x</b>',
        ]));

        self::assertStringNotContainsString('<script>', $html);
        self::assertStringNotContainsString('"><b>', $html);
        self::assertSame(
            [
                'provider' => 'ban', 'minChars' => 4,
                'fill' => ['street' => 'Street', 'postcode' => 'Zip', 'city' => '"><script>'],
                'countries' => [],
            ],
            $this->options($html)
        );
        self::assertSame(
            ['provider' => '', 'minChars' => 3, 'fill' => [], 'countries' => []],
            $this->options($this->render(self::field(['widget' => 'address'])))
        );
        self::assertStringContainsString('"fill":{}', html_entity_decode($this->render(self::field(['widget' => 'address'])), ENT_QUOTES));
    }

    public function testTheDropZoneAddsAttributesToTheFileInputOnly(): void
    {
        $plain = QuickModeFileInputBuilder::build('', 'file', 'doc', 7);
        self::assertSame('<input class="ff_elem" type="file" name="ff_nm_doc[]" id="ff_elem7"/>' . "\n", $plain);
        self::assertSame('', WidgetFieldBuilder::fileAttributes(['bfType' => 'bfFile']));

        $file = ['bfType' => 'bfFile', 'dropZone' => true];
        self::assertTrue(WidgetFieldBuilder::hasWidget($file));
        self::assertSame(
            '<input class="ff_elem" type="file" name="ff_nm_doc[]" id="ff_elem7"'
            . ' data-bfng-widget="dropzone" data-bfng-options="{}"/>' . "\n",
            QuickModeFileInputBuilder::build('', 'file', 'doc', 7, WidgetFieldBuilder::fileAttributes($file))
        );
        self::assertFalse(WidgetOptions::hasDropZone(['dropZone' => 'false']));
        self::assertTrue(WidgetOptions::hasDropZone(['dropZone' => 'true']));
    }
}
