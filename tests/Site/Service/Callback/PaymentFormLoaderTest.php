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
use Vcmb\Component\BreezingformsNG\Site\Service\Callback\PaymentFormLoader;

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

final class PaymentFormLoaderTest extends TestCase
{
    public function testLoadsFormByIdAndDecodesTemplateAreas(): void
    {
        $form = (object) [
            'template_areas' => json_encode([
                ['elements' => [['internalType' => 'bfPayPal']]],
            ], JSON_THROW_ON_ERROR),
        ];
        $database = $this->database([$form]);
        $loader = new PaymentFormLoader($database);

        $loaded = $loader->load(4);

        self::assertSame($form, $loaded);
        self::assertSame([':formId' => '4'], $database->query->bindings);
        self::assertSame(
            [['elements' => [['internalType' => 'bfPayPal']]]],
            $loader->decodeAreas($loaded)
        );
    }

    public function testReturnsNullWhenTheFormDoesNotExist(): void
    {
        $loader = new PaymentFormLoader($this->database([]));

        self::assertNull($loader->load(4));
    }

    public function testReturnsNullForInvalidTemplateAreas(): void
    {
        $loader = new PaymentFormLoader($this->database([]));

        self::assertNull($loader->decodeAreas((object) ['template_areas' => '{invalid']));
    }
    /** @param list<object> $results */
    private function database(array $results): object
    {
        return new class ($results) implements DatabaseInterface {
            /** @var list<object> */
            private array $results;

            public object $query;

            /** @param list<object> $results */
            public function __construct(array $results)
            {
                $this->results = $results;
            }

            public function createQuery(): object
            {
                return $this->query = new class {
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
                return $this->results;
            }
        };
    }
}
