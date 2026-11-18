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

require_once __DIR__ . '/../Support/joomla-base-database-model-stub.php';

use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Administrator\Model\QuickmodeModel;

final class QuickmodeWidgetAreasTest extends TestCase
{
    /**
     * @param array<string, mixed> $properties
     * @return array<string, mixed>
     */
    private function elementOf(array $properties): array
    {
        $properties += [
            'type' => 'element', 'value' => '', 'placeholder' => '', 'label' => 'Field', 'bfName' => 'field',
            'orderNumber' => -1, 'tabIndex' => 0, 'logging' => 1, 'validationId' => 0, 'validationCode' => '',
            'validationMessage' => '', 'validationFunctionName' => '', 'validationCondition' => 0, 'initId' => 0,
            'initCode' => '', 'initFunctionName' => '', 'initCondition' => 0, 'actionId' => 0,
            'actionFunctionName' => '', 'dbId' => 0,
        ];
        $tree = ['attributes' => ['id' => 'bfQuickModeElement1'], 'properties' => $properties];
        $areas = new \stdClass();
        $areas->container = [['elementCount' => 0, 'elements' => []]];
        $model = (new \ReflectionClass(QuickmodeModel::class))->newInstanceWithoutConstructor();

        (new \ReflectionMethod($model, 'createAreasFromTree'))->invoke($model, $tree, $areas);

        /** @var array{container: list<array{elements: list<array<string, mixed>>}>} $result */
        $result = json_decode((string) json_encode($areas), true, 512, JSON_THROW_ON_ERROR);

        return $result['container'][0]['elements'][0];
    }

    public function testAPlainTextFieldStoresNoWidgetKey(): void
    {
        $element = $this->elementOf(['bfType' => 'bfTextfield']);

        self::assertArrayNotHasKey('widget', $element['options']);
        self::assertArrayNotHasKey('widgetOptions', $element['options']);
    }

    public function testAPhoneFieldStoresItsWidgetAndOptions(): void
    {
        $element = $this->elementOf([
            'bfType' => 'bfTextfield', 'widget' => 'phone', 'widgetCountries' => 'fr,be', 'widgetValidate' => false,
        ]);

        self::assertSame('Text', $element['bfType']);
        self::assertSame('phone', $element['options']['widget']);
        self::assertSame(
            ['defaultCountry' => 'fr', 'countries' => ['fr', 'be'], 'flags' => true, 'dialCode' => true, 'validate' => false],
            $element['options']['widgetOptions']
        );
    }

    public function testADatetimeFieldStoresItsMode(): void
    {
        $element = $this->elementOf(['bfType' => 'bfTextfield', 'widget' => 'datetime', 'widgetMode' => 'date', 'widgetMax' => '2030-01-01']);

        self::assertSame(
            ['mode' => 'date', 'min' => '', 'max' => '2030-01-01', 'step' => 15],
            $element['options']['widgetOptions']
        );
    }

    public function testAnUnknownWidgetIsDropped(): void
    {
        self::assertArrayNotHasKey('widget', $this->elementOf(['bfType' => 'bfTextfield', 'widget' => 'evil'])['options']);
    }

    /** @return array<string, mixed> */
    private static function fileProperties(): array
    {
        return [
            'bfType' => 'bfFile', 'allowedFileExtensions' => 'JPG', 'timestamp' => false, 'uploadDirectory' => '/tmp',
            'attachToAdminMail' => false, 'attachToUserMail' => false,
        ];
    }

    public function testAFileFieldStoresTheDropZoneOnlyWhenEnabled(): void
    {
        self::assertArrayNotHasKey('dropZone', $this->elementOf(self::fileProperties())['options']);
        self::assertTrue($this->elementOf(['dropZone' => true] + self::fileProperties())['options']['dropZone']);
    }
}
