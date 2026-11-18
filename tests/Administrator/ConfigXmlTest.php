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

/**
 * Guards the Options screen (config.xml): keys and defaults are persisted
 * params, and every translation key must exist in all languages.
 */
final class ConfigXmlTest extends TestCase
{
    private const BASE = __DIR__ . '/../../administrator/components/com_breezingformsng/';

    private const LANGUAGES = ['en-GB', 'fr-FR', 'de-DE', 'it-IT', 'es-ES', 'hu-HU', 'nl-NL', 'tr-TR'];

    private function config(): \SimpleXMLElement
    {
        $xml = simplexml_load_file(self::BASE . 'config.xml');
        self::assertInstanceOf(\SimpleXMLElement::class, $xml);

        return $xml;
    }

    public function testAllParametersKeepKeysAndDefaults(): void
    {
        $expected = [
            'disable_ip' => '0',
            'emailadr' => 'Enter your email address here',
            'uploads' => '{mospath}/media/breezingforms/uploads',
            'save_history' => '1',
            'history_limit' => '10',
            'csvdelimiter' => ';',
            'csvquote' => '"',
            'cellnewline' => '1',
            'mcp_enabled' => '0',
            'rules' => null,
        ];

        $actual = [];
        foreach ($this->config()->xpath('//field') as $field) {
            $actual[(string) $field['name']] = isset($field['default']) ? (string) $field['default'] : null;
        }

        ksort($expected);
        ksort($actual);
        self::assertSame($expected, $actual);
    }

    public function testTranslationKeysExistInEveryLanguage(): void
    {
        $keys = [];
        foreach ($this->config()->xpath('//*[@label or @description]') as $node) {
            foreach (['label', 'description'] as $attr) {
                $value = (string) $node[$attr];
                if (str_starts_with($value, 'COM_BREEZINGFORMSNG_')) {
                    $keys[$value] = true;
                }
            }
        }

        self::assertNotEmpty($keys);

        foreach (self::LANGUAGES as $lang) {
            $ini = (string) file_get_contents(self::BASE . "language/$lang/com_breezingformsng.ini");
            foreach (array_keys($keys) as $key) {
                $pattern = '/^' . preg_quote($key, '/') . '\s*=\s*"[^"]+"/m';
                self::assertMatchesRegularExpression($pattern, $ini, "$key missing in $lang");
            }
        }
    }

    public function testDisableIpDescriptionDiffersFromLabel(): void
    {
        $field = $this->config()->xpath('//field[@name="disable_ip"]')[0];
        self::assertNotSame((string) $field['label'], (string) $field['description']);
    }
}
