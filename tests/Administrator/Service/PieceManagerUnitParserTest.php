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

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator\Service;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Joomla\Database\DatabaseInterface;
use ReflectionClass;
use ReflectionMethod;
use Vcmb\Component\BreezingformsNG\Administrator\Service\PieceManager;

if (!interface_exists(DatabaseInterface::class)) {
    eval('namespace Joomla\\Database; interface DatabaseInterface {}');
}

final class PieceManagerUnitParserTest extends TestCase
{
    #[DataProvider('pieceValueProvider')]
    public function testParsesStoredUnitTestValues(string $value, mixed $expected): void
    {
        self::assertSame($expected, $this->invokeStatic('parseTestValue', $value));
    }

    public static function pieceValueProvider(): iterable
    {
        yield 'null' => ['null', null];
        yield 'true' => ['true', true];
        yield 'false' => ['false', false];
        yield 'integer' => ['5', 5];
        yield 'decimal' => ['5.25', 5.25];
        yield 'quoted string' => ['"hello\\nworld"', "hello\nworld"];
        yield 'json object' => ['{"enabled":true,"count":2}', ['enabled' => true, 'count' => 2]];
        yield 'json array' => ['["first",2]', ['first', 2]];
        yield 'plain string' => ['submitted', 'submitted'];
    }

    public function testValuesEqualPreservesStrictScalarSemantics(): void
    {
        self::assertTrue($this->invokeStatic('valuesEqual', 5, 5));
        self::assertFalse($this->invokeStatic('valuesEqual', 5, false));
        self::assertTrue($this->invokeStatic('valuesEqual', ['a' => 1], ['a' => 1]));
        self::assertFalse($this->invokeStatic('valuesEqual', ['a' => 1], ['a' => 2]));
    }

    public function testPieceExecutionProvidesTheLegacyProcessorGlobal(): void
    {
        $row = (object) [
            'code' => '<?php function ff_dying() { global $ff_processor; return $ff_processor->dying; }',
        ];
        $database = self::createStub(DatabaseInterface::class);
        $previousProcessor = $GLOBALS['ff_processor'] ?? null;
        $hadProcessor = array_key_exists('ff_processor', $GLOBALS);

        try {
            $GLOBALS['ff_processor'] = (object) ['dying' => true];
            $execution = $this->invokeStatic('executePieceCode', $row, 'ff_dying', [], $database);
        } finally {
            if ($hadProcessor) {
                $GLOBALS['ff_processor'] = $previousProcessor;
            } else {
                unset($GLOBALS['ff_processor']);
            }
        }

        self::assertSame(false, $execution['result']);
        self::assertSame('', $execution['error']);
    }

    private function invokeStatic(string $methodName, mixed ...$arguments): mixed
    {
        $method = new ReflectionMethod(PieceManager::class, $methodName);
        $method->setAccessible(true);

        return $method->invoke(null, ...$arguments);
    }
}
