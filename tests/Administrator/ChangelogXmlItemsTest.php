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

final class ChangelogXmlItemsTest extends TestCase
{
    /**
     * Joomla's changelog parser (Joomla\CMS\Changelog\Changelog) stores every chunk of character data of an <item> as
     * an item of its own, and the XML parser cuts a text at each entity (&gt;, &quot;, &amp;): an entity would show up
     * as several broken bullets in the changelog dialog. Every <item> must reach the parser as one chunk.
     */
    public function testEveryChangelogItemIsReadAsASingleChunk(): void
    {
        $xml = file_get_contents(__DIR__ . '/../../com_breezingformsng_changelog.xml');
        self::assertIsString($xml);

        $chunks = 0;
        $inItem = false;
        $parser = xml_parser_create('UTF-8');
        xml_set_element_handler(
            $parser,
            static function ($parser, string $name) use (&$inItem): void {
                $inItem = $name === 'ITEM';
            },
            static function ($parser, string $name) use (&$inItem): void {
                $inItem = false;
            }
        );
        xml_set_character_data_handler(
            $parser,
            static function ($parser, string $data) use (&$chunks, &$inItem): void {
                if ($inItem) {
                    $chunks++;
                }
            }
        );

        self::assertSame(1, xml_parse($parser, $xml, true), xml_error_string(xml_get_error_code($parser)));

        self::assertGreaterThan(0, $chunks);
        self::assertSame(substr_count($xml, '<item>'), $chunks);
    }
}
