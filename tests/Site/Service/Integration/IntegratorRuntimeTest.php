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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Vcmb\Component\BreezingformsNG\Site\Service\Integration\IntegratorRuntime;

if (!interface_exists(DatabaseInterface::class)) {
    eval('namespace Joomla\\Database; interface DatabaseInterface {}');
}

if (!class_exists('Joomla\\Database\\ParameterType')) {
    eval('namespace Joomla\\Database; final class ParameterType { public const INTEGER = 1; }');
}

if (!defined('JPATH_SITE')) {
    define('JPATH_SITE', __DIR__ . '/../../../../');
}

if (!defined('JPATH_ADMINISTRATOR')) {
    define('JPATH_ADMINISTRATOR', __DIR__ . '/../../../../administrator');
}

// Defines the submitted data constants.
if (!class_exists('HTML_facileFormsProcessor')) {
    require_once __DIR__ . '/../../../../components/com_breezingformsng/src/Support/processor_facade.php';
}

if (!class_exists('Joomla\\CMS\\Language\\Text')) {
    eval(
        'namespace Joomla\\CMS\\Language;'
        . ' final class Text {'
        . ' public static function _(string $key): string { return $key; }'
        . ' }'
    );
}

if (!class_exists('Joomla\\CMS\\Log\\Log')) {
    eval(
        'namespace Joomla\\CMS\\Log;'
        . ' final class Log {'
        . ' public const WARNING = 16;'
        . ' public static array $entries = [];'
        . ' public static function add(string $message, int $priority, string $category): void'
        . ' { self::$entries[] = [$message, $priority, $category]; }'
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

    public function testASkippedRuleIsLogged(): void
    {
        $source = file_get_contents(
            __DIR__ . '/../../../../components/com_breezingformsng/src/Service/Integration/IntegratorRuntime.php'
        );

        self::assertIsString($source);
        self::assertStringContainsString(
            "' skipped: a stored criterion has an invalid operator or connector.'",
            $source
        );
        self::assertStringContainsString("Log::WARNING,\n                'com_breezingformsng'", $source);
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

    public function testLikeOperatorsMatchWildcardsOfTheValueLiterally(): void
    {
        $runtime = $this->runtimeWithQuotingDatabase();
        $query = new class {
            /** @var array<string, mixed> */
            public array $bound = [];

            public function bind(string $key, mixed $value): void
            {
                $this->bound[$key] = $value;
            }
        };
        $bindIndex = 0;
        $criteria = ['form' => [], 'joomla' => [], 'fixed' => [
            (object) ['andor' => '', 'reference_column' => 'a', 'operator' => '%...%', 'fixed_value' => '50%_off!'],
            (object) ['andor' => 'AND', 'reference_column' => 'b', 'operator' => '%...', 'fixed_value' => '%'],
            (object) ['andor' => 'OR', 'reference_column' => 'c', 'operator' => '...%', 'fixed_value' => "o'_"],
            (object) ['andor' => 'AND', 'reference_column' => 'd', 'operator' => '=', 'fixed_value' => '100%'],
        ]];

        $sql = (new ReflectionClass($runtime))->getMethod('buildCriteriaClauses')
            ->invokeArgs($runtime, [$criteria, $query, &$bindIndex]);

        self::assertSame(
            "`a` LIKE :criteriaValue0 ESCAPE '!' AND `b` LIKE :criteriaValue1 ESCAPE '!'"
            . " OR `c` LIKE :criteriaValue2 ESCAPE '!' AND `d` = :criteriaValue3",
            $sql
        );
        self::assertSame('%50!%!_off!!%', $query->bound[':criteriaValue0']);
        self::assertSame('%!%', $query->bound[':criteriaValue1']);
        self::assertSame("o'!_%", $query->bound[':criteriaValue2']);
        self::assertSame('100%', $query->bound[':criteriaValue3']);
    }

    public function testAFailingUpdateRuleIsLoggedWithoutSubmittedData(): void
    {
        $source = (string) file_get_contents(
            __DIR__ . '/../../../../components/com_breezingformsng/src/Service/Integration/IntegratorRuntime.php'
        );

        self::assertStringContainsString("' failed while updating or inserting the reference row.'", $source);
        self::assertStringNotContainsString('catch (\\Throwable $e) {' . "\n        }", $source);
    }

    /**
     * @return iterable<string, array{int, bool, bool, int}>
     */
    public static function updateOutcomes(): iterable
    {
        foreach (['with a criterion' => true, 'without criteria' => false] as $label => $criterion) {
            yield 'a changed row is not inserted again, ' . $label => [1, true, $criterion, 0];
            yield 'an unchanged row is not inserted again, ' . $label => [0, true, $criterion, 0];
            yield 'a missing row is inserted, ' . $label => [0, false, $criterion, 1];
        }
    }

    #[DataProvider('updateOutcomes')]
    public function testAnUpdateRuleInsertsOnlyWhenNoRowMatches(
        int $affected,
        bool $exists,
        bool $criterion,
        int $inserts
    ): void {
        $database = new class ($affected, $exists, $criterion) implements DatabaseInterface {
            /** @var list<object> */
            public array $executed = [];
            private object $query;

            public function __construct(
                private readonly int $affected,
                private readonly bool $exists,
                private readonly bool $criterion
            ) {
            }

            public function createQuery(): object
            {
                return new class {
                    public string $type = 'select';
                    public string $table = '';
                    /** @var list<string> */
                    public array $conditions = [];

                    public function insert(string $table): self
                    {
                        $this->type = 'insert';

                        return $this;
                    }

                    public function update(string $table): self
                    {
                        $this->type = 'update';

                        return $this;
                    }

                    public function from(string $table): self
                    {
                        $this->table = $table;

                        return $this;
                    }

                    public function where(string $condition): self
                    {
                        $this->conditions[] = $condition;

                        return $this;
                    }

                    public function __call(string $name, array $arguments): self
                    {
                        return $this;
                    }

                    public function bind(string $key, mixed $value, mixed $type = null): self
                    {
                        return $this;
                    }
                };
            }

            public function quoteName(string $name, ?string $alias = null): string
            {
                return $name;
            }

            public function setQuery(object $query): self
            {
                $this->query = $query;

                return $this;
            }

            public function execute(): bool
            {
                $this->executed[] = $this->query;

                return true;
            }

            public function getAffectedRows(): int
            {
                return $this->affected;
            }

            public function loadResult(): ?string
            {
                $this->executed[] = $this->query;

                return $this->exists ? '1' : null;
            }

            /** @return list<object> */
            public function loadObjectList(): array
            {
                return $this->criterion && str_contains($this->query->table, 'integrator_criteria_fixed')
                    ? [(object) [
                        'andor' => 'AND', 'reference_column' => 'locked', 'operator' => '=', 'fixed_value' => '0',
                    ]]
                    : [];
            }
        };

        $runtime = (new ReflectionClass(IntegratorRuntime::class))->newInstanceWithoutConstructor();
        $reflection = new ReflectionClass($runtime);
        $reflection->getProperty('db')->setValue($runtime, $database);
        $rule = (object) ['id' => 1, 'type' => 'update', 'reference_table' => 'contacts', 'finalize_code' => ''];
        $reflection->getMethod('commitUpdate')->invoke($runtime, ['rule' => $rule, 'items' => [
            ['item' => (object) ['code' => '', 'reference_column' => 'email'], 'data' => [4 => 'a@example.invalid']],
        ]]);

        $types = array_column($database->executed, 'type');
        self::assertSame($inserts, count(array_keys($types, 'insert', true)));
        self::assertSame($affected > 0 ? 1 : 2 + $inserts, count($types));

        if ($affected === 0) {
            // The row is looked up with the criteria of the update.
            self::assertSame($database->executed[0]->conditions, $database->executed[1]->conditions);
            self::assertCount($criterion ? 1 : 0, $database->executed[1]->conditions);
        }
    }

    public function testTheCodeOfAnItemChangesTheValueItWrites(): void
    {
        $runtime = (new ReflectionClass(IntegratorRuntime::class))->newInstanceWithoutConstructor();

        self::assertSame(
            'JEAN@EXAMPLE.INVALID',
            $runtime->handleCode('jean@example.invalid', '$value = strtoupper($value);')
        );
        self::assertSame('kept', $runtime->handleCode('kept', ''));
    }
}
