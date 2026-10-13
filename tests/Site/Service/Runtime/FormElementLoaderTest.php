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

namespace Vcmb\Component\BreezingformsNG\Tests\Site\Service\Runtime;

use Joomla\Database\DatabaseInterface;
use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Site\Service\Runtime\FormElementLoader;

if (!interface_exists(DatabaseInterface::class)) {
    eval('namespace Joomla\\Database; interface DatabaseInterface {}');
}

if (!class_exists('Joomla\\Database\\ParameterType')) {
    eval('namespace Joomla\\Database; final class ParameterType { public const INTEGER = 1; }');
}

final class FormElementLoaderTest extends TestCase
{
    public function testLoadsOnlyPublishedElementsInDisplayOrder(): void
    {
        $elements = [(object) ['id' => 12], (object) ['id' => 14]];
        $database = new FormElementLoaderDatabaseDouble($elements);

        self::assertSame($elements, (new FormElementLoader($database))->loadPublished(7));
        self::assertCount(1, $database->queries);
        self::assertSame(
            [
                'form = :formId',
                'published = 1',
            ],
            $database->queries[0]->where
        );
        self::assertSame(
            [':formId', 7, 1],
            $database->queries[0]->binding
        );
        self::assertSame(['page', 'ordering'], $database->queries[0]->ordering);
    }

    public function testReturnsAnEmptyListWhenNoPublishedElementsExist(): void
    {
        $database = new FormElementLoaderDatabaseDouble([]);

        self::assertSame([], (new FormElementLoader($database))->loadPublished(99));
    }

    public function testFacadeDelegatesElementLoadingToTheDedicatedService(): void
    {
        $source = file_get_contents(
            __DIR__ . '/../../../../components/com_breezingformsng/src/Support/processor_facade.php'
        );

        self::assertIsString($source);
        self::assertStringContainsString(
            '(new FormElementLoader($this->database))->loadPublished($formId)',
            $source
        );
        self::assertStringNotContainsString("#__facileforms_elements", $source);
    }
}

final class FormElementLoaderDatabaseDouble implements DatabaseInterface
{
    /** @var list<FormElementLoaderQueryDouble> */
    public array $queries = [];

    /** @param list<object> $elements */
    public function __construct(private readonly array $elements)
    {
    }

    public function getQuery(bool $new = false): FormElementLoaderQueryDouble
    {
        return $this->queries[] = new FormElementLoaderQueryDouble();
    }

    public function quoteName(string|array $name): string|array
    {
        return $name;
    }

    public function setQuery(object $query, int $offset = 0, int $limit = 0): void
    {
    }

    /** @return list<object> */
    public function loadObjectList(): array
    {
        return $this->elements;
    }
}

final class FormElementLoaderQueryDouble
{
    /** @var list<string> */
    public array $where = [];

    /** @var list<string> */
    public array $ordering = [];

    /** @var array{string, mixed, mixed}|null */
    public ?array $binding = null;

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

    /** @param list<string>|string $ordering */
    public function order(array|string $ordering): self
    {
        $this->ordering = (array) $ordering;

        return $this;
    }

    public function bind(string $key, mixed $value, mixed $type): self
    {
        $this->binding = [$key, $value, $type];

        return $this;
    }
}
