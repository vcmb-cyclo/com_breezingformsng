<?php

/**
 * BreezingForms NG - A Joomla Forms Application
 *
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 *
 * SPDX-License-Identifier: GPL-2.0-or-later
 */

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator;

use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

final class StdlibDatabasePiecesTest extends TestCase
{
    /** Runs the actual code imported from XML without the bfcompat plugin. */
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testDatabasePiecesWorkWithoutBfcompatAndReportFailures(): void
    {
        eval('namespace Joomla\\Database; interface DatabaseInterface {}');
        eval('namespace Joomla\\CMS; final class Factory {
            public static object $container;
            public static object $application;
            public static function getContainer(): object { return self::$container; }
            public static function getApplication(): object { return self::$application; }
        }');
        class_alias(\Joomla\CMS\Factory::class, 'Factory');
        eval('namespace Joomla\\CMS\\Log; final class Log {
            public const ERROR = 8;
            public static array $entries = [];
            public static function add(string $entry, int $priority, string $category): void { self::$entries[] = [$entry, $priority, $category]; }
        }');
        eval('namespace Joomla\\CMS\\Language; final class Text { public static function _(string $key): string { return $key; } }');
        // The ff_die piece stops the form: here it only records why.
        $GLOBALS['ff_died'] = [];
        eval('function ff_die($message = "") { $GLOBALS["ff_died"][] = $message; }');

        $db = new class implements \Joomla\Database\DatabaseInterface {
            public string $query = '';
            public bool $fail = false;
            public mixed $result = 'chosen';
            public array $rows = [];

            public function setQuery(string $query): self
            {
                $this->query = $query;
                return $this;
            }

            public function execute(): bool
            {
                if ($this->fail) {
                    throw new \RuntimeException('database error');
                }
                return true;
            }

            public function insertid(): int
            {
                return 37;
            }

            public function loadObjectList(): array
            {
                if ($this->fail) {
                    throw new \RuntimeException('database error');
                }
                return $this->rows;
            }

            public function loadResult(): mixed
            {
                if ($this->fail) {
                    throw new \RuntimeException('database error');
                }
                return $this->result;
            }
        };

        \Joomla\CMS\Factory::$container = new class ($db) {
            public function __construct(private readonly object $database)
            {
            }

            public function get(string $id): object
            {
                return $this->database;
            }
        };
        \Joomla\CMS\Factory::$application = new class {
            public array $messages = [];
            public function enqueueMessage(string $message, string $type = 'message'): void
            {
                $this->messages[] = [$message, $type];
            }
        };

        \define('FF_DIE', 1);
        \define('FF_IGNOREDIE', 2);
        $GLOBALS['ff_processor'] = (object) ['database' => $db, 'dying' => false, 'rows' => []];
        $path = __DIR__ . '/../../administrator/components/com_breezingformsng/packages/stdlib.english.xml';
        $xml = simplexml_load_file($path);
        self::assertNotFalse($xml);
        $names = ['ff_databaseToSelect', 'ff_query', 'ff_select', 'ff_selectValue'];
        foreach ($xml->piece as $piece) {
            if (in_array((string) $piece->name, $names, true)) {
                eval(stripcslashes((string) $piece->code));
            }
        }
        foreach ($names as $name) {
            self::assertTrue(function_exists($name), $name);
        }
        self::assertFalse(class_exists('BFFactory', false));

        self::assertSame(0, \ff_query('UPDATE test SET x = 1'));
        self::assertSame('UPDATE test SET x = 1', $db->query);
        self::assertSame(37, \ff_query('INSERT INTO test VALUES (1)', true));
        $GLOBALS['ff_processor']->dying = true;
        self::assertSame(-1, \ff_query('UPDATE ignored', false, FF_DIE));
        self::assertSame(0, \ff_query('UPDATE allowed', false, FF_IGNOREDIE));
        self::assertSame('UPDATE allowed', $db->query);
        $GLOBALS['ff_processor']->dying = false;

        $db->rows = [(object) ['label' => 'One', 'value' => '1']];
        self::assertEquals($db->rows, \ff_select('SELECT label, value FROM test'));
        self::assertSame('chosen', \ff_selectValue('SELECT value FROM test', 'default'));
        $db->result = null;
        self::assertSame('default', \ff_selectValue('SELECT missing FROM test', 'default'));
        $db->result = '0';
        self::assertSame('default', \ff_selectValue('SELECT zero FROM test', 'default'));

        $field = (object) ['name' => 'choice', 'data2' => ''];
        $GLOBALS['ff_processor']->rows = [$field];
        \ff_databaseToSelect('choice', 'test', 'label', 'value', 'SELECT label, value FROM test');
        self::assertSame("0;One;1\n", $field->data2);
        self::assertEquals([(object) ['selected' => 0, 'label' => 'One', 'value' => '1']], $field->selectList);

        $db->fail = true;
        // FF_DIE, the default: a failed query stops the form through ff_die.
        self::assertSame([], \ff_select('SELECT broken'));
        self::assertSame('default', \ff_selectValue('SELECT broken', 'default'));
        self::assertSame(0, \ff_query('UPDATE broken'));
        self::assertSame(0, \ff_query('INSERT broken', true));
        self::assertSame(array_fill(0, 4, 'database error'), $GLOBALS['ff_died']);

        // FF_IGNOREDIE: the caller chose to go on.
        $GLOBALS['ff_died'] = [];
        self::assertSame([], \ff_select('SELECT broken', FF_IGNOREDIE));
        self::assertSame('default', \ff_selectValue('SELECT broken', 'default', FF_IGNOREDIE));
        self::assertSame(0, \ff_query('UPDATE broken', false, FF_IGNOREDIE));
        self::assertSame([], $GLOBALS['ff_died']);

        // A list that cannot be filled says so, and leaves the field as it was.
        \ff_databaseToSelect('choice', 'test', 'label', 'value', 'SELECT broken');
        self::assertSame("0;One;1\n", $field->data2);
        self::assertSame([['JERROR_AN_ERROR_HAS_OCCURRED', 'error']], \Joomla\CMS\Factory::$application->messages);
        self::assertCount(1, \Joomla\CMS\Log\Log::$entries);
        self::assertStringContainsString('RuntimeException', \Joomla\CMS\Log\Log::$entries[0][0]);
        // The text of the database error may quote data: it is not logged.
        self::assertStringNotContainsString('database error', \Joomla\CMS\Log\Log::$entries[0][0]);
    }
}
