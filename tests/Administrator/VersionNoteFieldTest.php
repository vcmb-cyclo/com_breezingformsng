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

final class VersionNoteFieldTest extends TestCase
{
    private const BASE = __DIR__ . '/../../administrator/components/com_breezingformsng';

    /** Joomla's Versionable behaviour stores jform[version_note] with the version a save creates. */
    public function testScriptsAndPiecesTakeANoteForTheVersionTheirSaveCreates(): void
    {
        foreach (['script', 'piece'] as $name) {
            $xml = simplexml_load_file(self::BASE . '/forms/' . $name . '.xml');
            self::assertNotFalse($xml);

            $field = $xml->xpath('//field[@name="version_note"]');
            self::assertIsArray($field);
            self::assertCount(1, $field, $name . ' form');
            self::assertSame('text', (string) $field[0]['type']);
            self::assertSame('255', (string) $field[0]['maxlength']);
            self::assertSame('JGLOBAL_FIELD_VERSION_NOTE_LABEL', (string) $field[0]['label']);
        }
    }

    public function testTheFormOptionsPostTheNoteAndTheNextSaveStartsWithoutOne(): void
    {
        $layout = file_get_contents(self::BASE . '/layouts/forms/advanced_options.php');
        $controller = file_get_contents(self::BASE . '/src/Controller/FormsController.php');
        $script = file_get_contents(__DIR__ . '/../../media/com_breezingformsng/js/admin/quickmode-app.js');

        self::assertIsString($layout);
        self::assertIsString($controller);
        self::assertIsString($script);
        self::assertStringContainsString('id="jf_version_note" name="version_note" maxlength="255"', $layout);
        self::assertStringContainsString("post->getString('version_note', '')", $controller);
        self::assertStringContainsString('->record($id, mb_substr($versionNote, 0, 255))', $controller);
        self::assertStringContainsString("JQuery('#jf_version_note').val('');", $script);
    }
}
