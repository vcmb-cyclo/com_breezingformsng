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

namespace Vcmb\Component\BreezingformsNG\Tests\Site\Service\Integration;

use Joomla\Database\DatabaseInterface;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Vcmb\Component\BreezingformsNG\Site\Service\Integration\IntegratorRuntime;

if (!interface_exists(DatabaseInterface::class)) {
    eval('namespace Joomla\\Database; interface DatabaseInterface {}');
}

if (!class_exists('Joomla\\Database\\ParameterType')) {
    eval('namespace Joomla\\Database; final class ParameterType { public const INTEGER = 1; }');
}

if (!class_exists('Joomla\\CMS\\Language\\Text')) {
    eval(
        'namespace Joomla\\CMS\\Language;'
        . ' final class Text {'
        . ' public static function _(string $key): string { return $key; }'
        . ' }'
    );
}

final class IntegratorRuntimeTest extends TestCase
{
    public function testGetCriteriaReturnsAnEmptyListAndGenericMessageWhenTheQueryFails(): void
    {
        $query = new class {
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
                return $this;
            }

            public function join(string $type, string $table): self
            {
                return $this;
            }

            public function group(string $columns): self
            {
                return $this;
            }

            public function order(string $columns): self
            {
                return $this;
            }

            public function bind(string $key, mixed $value, mixed $type = null): self
            {
                return $this;
            }

            public function loadObjectList(): array
            {
                throw new \Exception('query failed');
            }
        };

        $database = new class ($query) implements DatabaseInterface {
            public function __construct(private readonly object $query)
            {
            }

            public function createQuery(): object
            {
                return $this->query;
            }

            public function quoteName(string $name, ?string $alias = null): string
            {
                return $alias === null ? $name : $name . ' AS ' . $alias;
            }

            public function setQuery(object $query): object
            {
                return $query;
            }
        };

        $runtime = (new ReflectionClass(IntegratorRuntime::class))->newInstanceWithoutConstructor();
        $property = (new ReflectionClass($runtime))->getProperty('db');
        $property->setValue($runtime, $database);

        ob_start();
        try {
            $result = $runtime->getCriteria(11);
        } finally {
            $output = ob_get_clean();
        }

        self::assertSame([], $result);
        self::assertSame('JERROR_AN_ERROR_HAS_OCCURRED', $output);
        self::assertStringNotContainsString('query failed', $output);
    }

    public function testCriteriaBuilderRejectsAnUnlistedSqlOperator(): void
    {
        $this->assertCriteriaRejected('= 1 OR 1=1 --', 'AND');
    }

    public function testCriteriaBuilderRejectsAnUnlistedBooleanConnector(): void
    {
        // The connector of the first criterion joins nothing: only the second one is read.
        $this->assertCriteriaRejected('=', 'OR 1=1 --', true);
    }

    public function testTheConnectorOfTheFirstCriterionIsNotRead(): void
    {
        $runtime = $this->runtimeWithQuotingDatabase();
        $query = $this->bindingQuery();
        $bindIndex = 0;
        $method = (new ReflectionClass($runtime))->getMethod('buildCriteriaClauses');
        $criteria = ['form' => [], 'joomla' => [], 'fixed' => [
            (object) ['andor' => '', 'reference_column' => 'name', 'operator' => '=', 'fixed_value' => 'a'],
            (object) ['andor' => 'OR', 'reference_column' => 'city', 'operator' => '=', 'fixed_value' => 'b'],
        ]];

        self::assertSame(
            '`name` = :criteriaValue0 OR `city` = :criteriaValue1',
            $method->invokeArgs($runtime, [$criteria, $query, &$bindIndex])
        );
    }

    private function runtimeWithQuotingDatabase(): IntegratorRuntime
    {
        $runtime = (new ReflectionClass(IntegratorRuntime::class))->newInstanceWithoutConstructor();
        (new ReflectionClass($runtime))->getProperty('db')->setValue($runtime, new class implements DatabaseInterface {
            public function quoteName(string $name): string
            {
                return '`' . $name . '`';
            }
        });

        return $runtime;
    }

    private function bindingQuery(): object
    {
        return new class {
            public function bind(string $key, mixed $value): void
            {
            }
        };
    }

    private function assertCriteriaRejected(string $operator, string $connector, bool $second = false): void
    {
        $runtime = $this->runtimeWithQuotingDatabase();

        $criteria = [
            'form' => [],
            'joomla' => [],
            'fixed' => [(object) [
                'andor' => $connector,
                'reference_column' => 'name',
                'operator' => $operator,
                'fixed_value' => 'value',
            ]],
        ];

        if ($second) {
            array_unshift($criteria['fixed'], (object) [
                'andor' => 'AND',
                'reference_column' => 'id',
                'operator' => '=',
                'fixed_value' => '1',
            ]);
        }
        $bindIndex = 0;
        $method = (new ReflectionClass($runtime))->getMethod('buildCriteriaClauses');

        try {
            $method->invokeArgs($runtime, [$criteria, $this->bindingQuery(), &$bindIndex]);
            self::fail('Invalid SQL syntax must be rejected before query construction.');
        } catch (\UnexpectedValueException $exception) {
            self::assertSame('Invalid Integrator criteria.', $exception->getMessage());
        }
    }

    public function testTheCodeOfAnItemChangesTheValueItWrites(): void
    {
        $runtime = (new ReflectionClass(IntegratorRuntime::class))->newInstanceWithoutConstructor();

        self::assertSame('JEAN@EXAMPLE.INVALID', $runtime->handleCode('jean@example.invalid', '$value = strtoupper($value);'));
        self::assertSame('kept', $runtime->handleCode('kept', ''));
    }
}
