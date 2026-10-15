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
use Vcmb\Component\BreezingformsNG\Site\Service\Rendering\ContentBuilderFormMetadataLoader;

if (!interface_exists(DatabaseInterface::class)) {
    eval('namespace Joomla\\Database; interface DatabaseInterface {}');
}

if (!class_exists('Joomla\\Database\\ParameterType')) {
    eval('namespace Joomla\\Database; final class ParameterType { public const INTEGER = 1; }');
}

final class ContentBuilderFormMetadataLoaderTest extends TestCase
{
    public function testLoadsOnlyPublishedBreezingFormsAssociations(): void
    {
        $database = new ContentBuilderMetadataDatabaseDouble([41, 42], null);

        self::assertSame([41, 42], (new ContentBuilderFormMetadataLoader($database))->loadAssociatedFormIds(7));
        self::assertSame(
            [
                "type = 'com_breezingformsng'",
                'reference_id = :referenceId',
                'published = 1',
            ],
            $database->query->where
        );
        self::assertSame([[':referenceId', 7, 1]], $database->query->bindings);
    }

    public function testReturnsAnEmptyListWhenNoAssociationExists(): void
    {
        $database = new ContentBuilderMetadataDatabaseDouble([], null);

        self::assertSame([], (new ContentBuilderFormMetadataLoader($database))->loadAssociatedFormIds(7));
    }

    public function testLoadsThePublishedFormDefinitionWithAnIntegerBinding(): void
    {
        $database = new ContentBuilderMetadataDatabaseDouble(null, [
            'reference_id' => 19,
            'published_only' => 1,
        ]);

        self::assertSame(
            ['reference_id' => 19, 'published_only' => 1],
            (new ContentBuilderFormMetadataLoader($database))->loadForm(23)
        );
        self::assertSame(['id = :cbFormId', 'published = 1'], $database->query->where);
        self::assertSame([[':cbFormId', 23, 1]], $database->query->bindings);
    }

    public function testReturnsNullWhenTheFormIsNotFoundOrNotPublished(): void
    {
        $database = new ContentBuilderMetadataDatabaseDouble(null, null);

        self::assertNull((new ContentBuilderFormMetadataLoader($database))->loadForm(23));
    }
}

final class ContentBuilderMetadataDatabaseDouble implements DatabaseInterface
{
    public ContentBuilderMetadataQueryDouble $query;

    /** @param array<int, mixed>|null $associations @param array<string, mixed>|null $data */
    public function __construct(private readonly ?array $associations, private readonly ?array $data)
    {
    }

    public function createQuery(): object
    {
        return $this->query = new ContentBuilderMetadataQueryDouble();
    }

    public function quoteName(string|array $name): string|array
    {
        return $name;
    }

    public function quote(string $value): string
    {
        return "'" . $value . "'";
    }

    public function setQuery(object $query, int $offset = 0, int $limit = 0): void
    {
    }

    /** @return array<int, mixed> */
    public function loadColumn(): array
    {
        return $this->associations ?? [];
    }

    /** @return array<string, mixed>|null */
    public function loadAssoc(): ?array
    {
        return $this->data;
    }
}

final class ContentBuilderMetadataQueryDouble
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

    public function bind(string $key, mixed $value, mixed $type): self
    {
        $this->bindings[] = [$key, $value, $type];

        return $this;
    }
}
