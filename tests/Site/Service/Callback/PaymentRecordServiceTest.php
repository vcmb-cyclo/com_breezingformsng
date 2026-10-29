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

namespace Vcmb\Component\BreezingformsNG\Tests\Site\Service\Callback;

use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Site\Service\Callback\PaymentRecordService;

if (!interface_exists(DatabaseInterface::class)) {
    eval('namespace Joomla\\Database; interface DatabaseInterface {}');
}

if (!class_exists(ParameterType::class)) {
    eval(
        'namespace Joomla\\Database;'
        . 'final class ParameterType {'
        . ' public const INTEGER = 1;'
        . ' public const STRING = 2;'
        . '}'
    );
}

final class PaymentRecordServiceTest extends TestCase
{
    public function testNotificationRetryRequiresTheSameRecordFormAndTransaction(): void
    {
        $database = new class implements DatabaseInterface {
            /** @var array<string, string> */
            public array $bindings = [];

            public function createQuery(): self
            {
                return $this;
            }

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

            public function bind(string $key, mixed $value, mixed $type): self
            {
                $this->bindings[$key] = (string) $value;

                return $this;
            }

            public function quoteName(string|array $name): string|array
            {
                return $name;
            }

            public function setQuery(object $query, int $offset = 0, int $limit = 0): self
            {
                return $this;
            }

            public function loadResult(): int
            {
                return 1;
            }
        };

        self::assertTrue((new PaymentRecordService($database))->hasTransaction(12, 7, 'Stripe: pi_123'));
        self::assertSame([
            ':recordId' => '12',
            ':formId' => '7',
            ':transaction' => 'Stripe: pi_123',
        ], $database->bindings);
    }

    public function testFindsUnpaidRecordAndStoresCommonPaymentFields(): void
    {
        $database = new class implements DatabaseInterface {
            /** @var list<object> */
            public array $queries = [];

            public int $executions = 0;

            public function createQuery(): object
            {
                return $this->queries[] = new class {
                    /** @var array<string, string> */
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
                        return $this;
                    }

                    public function setLimit(int $limit): self
                    {
                        return $this;
                    }

                    public function update(string $table): self
                    {
                        return $this;
                    }

                    public function set(string $condition): self
                    {
                        return $this;
                    }

                    public function bind(string $key, mixed $value, mixed $type): self
                    {
                        $this->bindings[$key] = (string) $value;

                        return $this;
                    }
                };
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
                return [(object) ['paypal_tx_id' => '']];
            }

            public function execute(): bool
            {
                $this->executions++;

                return true;
            }
        };
        $service = new PaymentRecordService($database);

        self::assertNotNull($service->findUnpaid(12));
        $service->storeTransaction(12, 'Stripe: pi_123', '2026-09-01 12:00:00', 0);

        self::assertSame(1, $database->executions);
        self::assertSame(
            [
                ':paymentTransaction' => 'Stripe: pi_123',
                ':paymentDate' => '2026-09-01 12:00:00',
                ':testAccount' => '0',
                ':recordId' => '12',
            ],
            $database->queries[1]->bindings
        );
    }

    public function testStoresATransactionOnlyOnARecordWithoutOne(): void
    {
        foreach ([1 => true, 0 => false] as $affectedRows => $expected) {
            $database = new class ($affectedRows) implements DatabaseInterface {
                public ?object $query = null;

                public function __construct(private readonly int $affectedRows)
                {
                }

                public function createQuery(): object
                {
                    return $this->query = new class {
                        /** @var list<string> */
                        public array $conditions = [];

                        public function update(string $table): self
                        {
                            return $this;
                        }

                        public function set(string $condition): self
                        {
                            return $this;
                        }

                        public function where(string $condition): self
                        {
                            $this->conditions[] = $condition;

                            return $this;
                        }

                        public function bind(string $key, mixed $value, mixed $type): self
                        {
                            return $this;
                        }
                    };
                }

                public function quoteName(string|array $name): string|array
                {
                    return $name;
                }

                public function setQuery(object $query, int $offset = 0, int $limit = 0): void
                {
                }

                public function execute(): bool
                {
                    return true;
                }

                public function getAffectedRows(): int
                {
                    return $this->affectedRows;
                }
            };

            $stored = (new PaymentRecordService($database))
                ->storeTransactionOnce(12, 'Stripe: pi_123', '2026-10-29 10:00:00', 1);

            self::assertSame($expected, $stored);
            self::assertContains("paypal_tx_id = ''", $database->query->conditions ?? []);
        }
    }
}
