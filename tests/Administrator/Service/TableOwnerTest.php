<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator\Service;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Administrator\Service\TableOwner;

final class TableOwnerTest extends TestCase
{
    /** @return iterable<string, array{string, string}> */
    public static function tables(): iterable
    {
        yield 'Joomla' => ['abc_users', TableOwner::JOOMLA];
        yield 'Joomla log' => ['abc_action_log_config', TableOwner::JOOMLA];
        yield 'BreezingForms NG' => ['abc_facileforms_records', TableOwner::BFNG];
        yield 'ContentBuilder NG' => ['abc_contentbuilderng_forms', TableOwner::CBNG];
        yield 'installed component' => ['abc_jce_profiles', TableOwner::EXTENSION];
        yield 'table named as a component' => ['abc_jce', TableOwner::EXTENSION];
        yield 'only the same beginning' => ['abc_jcenter', TableOwner::FREE];
        yield 'own table' => ['abc_bf_demo_contacts', TableOwner::FREE];
        yield 'another prefix' => ['xyz_users', TableOwner::FREE];
    }

    #[DataProvider('tables')]
    public function testTheOwnerIsReadFromTheName(string $table, string $owner): void
    {
        self::assertSame($owner, TableOwner::of($table, 'abc_', ['com_jce', 'com_content']));
    }

    public function testEveryOwnerHasItsTextInEachLanguage(): void
    {
        $base = __DIR__ . '/../../../administrator/components/com_breezingformsng/language/';

        foreach (['de-DE', 'en-GB', 'es-ES', 'fr-FR', 'hu-HU', 'it-IT', 'nl-NL', 'tr-TR'] as $tag) {
            $strings = parse_ini_file($base . $tag . '/com_breezingformsng.ini');
            self::assertIsArray($strings, $tag);

            foreach (TableOwner::ALL as $owner) {
                $key = 'COM_BREEZINGFORMSNG_INTEGRATOR_TABLE_OWNER_' . strtoupper($owner);
                self::assertNotEmpty($strings[$key] ?? '', $tag . ' ' . $key);
            }

            self::assertNotEmpty($strings['COM_BREEZINGFORMSNG_INTEGRATOR_SOURCE_FORM'] ?? '', $tag);
        }
    }
}
