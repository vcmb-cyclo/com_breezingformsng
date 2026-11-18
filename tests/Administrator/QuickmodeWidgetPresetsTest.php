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

final class QuickmodeWidgetPresetsTest extends TestCase
{
    private const ROOT = __DIR__ . '/../..';
    private const FIELDS = ['DefaultCountry', 'Countries', 'ShowFlags', 'ShowDialCode', 'Validate', 'Mode', 'Min', 'Max', 'Step', 'Provider', 'MinChars', 'Fill'];

    private function read(string $path): string
    {
        $content = file_get_contents(self::ROOT . '/' . $path);
        self::assertIsString($content);

        return $content;
    }

    public function testEditorOffersTheTextfieldPresetsAndDropZone(): void
    {
        $layout = $this->read('administrator/components/com_breezingformsng/layouts/quickmode/properties_element.php');

        foreach (['Phone', 'DateTime', 'Country', 'Address'] as $type) {
            self::assertStringContainsString("'bfElementType" . $type . "' => Text::_('COM_BREEZINGFORMSNG_ELEMENT_" . strtoupper($type) . "')", $layout);
        }

        foreach (self::FIELDS as $field) {
            self::assertStringContainsString('id="bfElementTypeTextWidget' . $field . '"', $layout, $field);
        }

        self::assertStringContainsString('id="bfElementTypeFileDropZone"', $layout);
        self::assertStringContainsString('COM_BREEZINGFORMSNG_QM_WIDGET_PRIVACY_NOTICE', $layout);
    }

    public function testEditorScriptsReadAndWriteEveryWidgetProperty(): void
    {
        $properties = $this->read('media/com_breezingformsng/js/admin/quickmode-app-properties.js');

        foreach (self::FIELDS as $field) {
            self::assertStringContainsString("mdata.widget" . $field . ' =', $properties, $field);
            self::assertStringContainsString("'#bfElementTypeTextWidget" . $field . "'", $properties, $field);
        }

        self::assertStringContainsString('mdata.dropZone =', $properties);
        self::assertStringContainsString("'#bfElementTypeFileDropZone'", $properties);

        $app = $this->read('media/com_breezingformsng/js/admin/quickmode-app.js');
        foreach (['Phone', 'DateTime', 'Country', 'Address'] as $type) {
            self::assertStringContainsString("case 'bfElementType" . $type . "':", $app);
        }
    }
}
