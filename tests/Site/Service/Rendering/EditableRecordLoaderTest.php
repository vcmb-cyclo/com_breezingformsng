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

namespace Vcmb\Component\BreezingformsNG\Tests\Site\Service\Rendering;

use Joomla\Database\DatabaseInterface;
use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Site\Service\Rendering\EditableRecordLoader;

if (!interface_exists(DatabaseInterface::class)) {
    eval('namespace Joomla\\Database; interface DatabaseInterface {}');
}

if (!class_exists('Joomla\\Database\\ParameterType')) {
    eval('namespace Joomla\\Database; final class ParameterType { public const INTEGER = 1; }');
}

final class EditableRecordLoaderTest extends TestCase
{
    public function testLoadReturnsNullWhenNoEditableRecordExists(): void
    {
        $database = new EditableRecordDatabaseDouble([]);

        self::assertNull((new EditableRecordLoader($database))->load(7, 12));
        self::assertCount(1, $database->queries);
        self::assertSame(
            [
                'form = :formValue',
                'user_id = :userId',
                'user_id <> 0',
                'state = 1',
            ],
            $database->queryObjects[0]->where
        );
        self::assertSame(
            [
                [':formValue', 7, 1],
                [':userId', 12, 1],
            ],
            $database->queryObjects[0]->bindings
        );
    }

    public function testLoadReturnsLatestRecordAndItsSubrecords(): void
    {
        $entries = [(object) ['name' => 'name', 'value' => 'Alice']];
        $database = new EditableRecordDatabaseDouble(
            [(object) ['id' => 42, 'form' => 7]],
            $entries
        );

        $record = (new EditableRecordLoader($database))->load(7, 12);

        self::assertNotNull($record);
        self::assertSame(42, $record->id);
        self::assertSame($entries, $record->entries);
        self::assertCount(2, $database->queries);
        self::assertSame(
            ['record = :recordId'],
            $database->queryObjects[1]->where
        );
        self::assertSame(
            [[':recordId', 42, 1]],
            $database->queryObjects[1]->bindings
        );
    }

    public function testLoadKeepsGuestUsersOutOfTheEditableRecordQuery(): void
    {
        $database = new EditableRecordDatabaseDouble([]);

        self::assertNull((new EditableRecordLoader($database))->load(7, 0));
        self::assertSame(
            [
                'form = :formValue',
                'user_id = :userId',
                'user_id <> 0',
                'state = 1',
            ],
            $database->queryObjects[0]->where
        );
        self::assertSame(
            [[':formValue', 7, 1], [':userId', 0, 1]],
            $database->queryObjects[0]->bindings
        );
    }
}

final class EditableRecordDatabaseDouble implements DatabaseInterface
{
    /** @var list<object> */
    public array $queries = [];

    /** @var list<EditableRecordQueryDouble> */
    public array $queryObjects = [];

    /** @param list<object> $records */
    public function __construct(private array $records, private array $entries = [])
    {
    }

    public function createQuery(): object
    {
        return $this->queryObjects[] = new EditableRecordQueryDouble();
    }

    public function quoteName(string|array $name): string|array
    {
        return $name;
    }

    public function setQuery(object $query, int $offset = 0, int $limit = 0): void
    {
        $this->queries[] = $query;
    }

    /** @return list<object> */
    public function loadObjectList(): array
    {
        return count($this->queries) === 1 ? $this->records : $this->entries;
    }
}

final class EditableRecordQueryDouble
{
    /** @var list<string> */
    public array $where = [];

    /** @var list<array{string, mixed, mixed}> */
    public array $bindings = [];

    public function select(mixed $columns): self
    {
        return $this;
    }

    public function from(string $table): self
    {
        return $this;
    }

    public function where(string $condition): self
    {
        $this->where[] = $condition;

        return $this;
    }

    public function order(string $ordering): self
    {
        return $this;
    }

    public function bind(string $key, mixed $value, mixed $type): self
    {
        $this->bindings[] = [$key, $value, $type];

        return $this;
    }
}
