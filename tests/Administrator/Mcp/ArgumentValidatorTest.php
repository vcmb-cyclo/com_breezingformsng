<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator\Mcp;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Administrator\Mcp\ArgumentValidator;

final class ArgumentValidatorTest extends TestCase
{
    private const SCHEMA = [
        'type' => 'object',
        'properties' => [
            'id' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 100],
            'name' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 5],
            'state' => ['type' => 'string', 'enum' => ['all', 'published'], 'default' => 'all'],
            'flag' => ['type' => 'boolean', 'default' => false],
            'ids' => ['type' => 'array', 'maxItems' => 2, 'items' => ['type' => 'integer', 'minimum' => 1]],
        ],
        'required' => ['id'],
        'additionalProperties' => false,
    ];

    public function testValidArgumentsAreReturnedWithTheDefaults(): void
    {
        self::assertSame(
            ['id' => 5, 'name' => 'abc', 'state' => 'all', 'flag' => false, 'ids' => [1, 2]],
            ArgumentValidator::validate(['id' => 5.0, 'name' => 'abc', 'ids' => [1, 2]], self::SCHEMA)
        );
    }

    public function testNoArgumentsAreAnEmptyObject(): void
    {
        self::assertSame(['flag' => false], ArgumentValidator::validate(null, ['type' => 'object', 'properties' => ['flag' => ['type' => 'boolean', 'default' => false]]]));
    }

    /**
     * @return iterable<string, array{mixed, string}>
     */
    public static function invalidArguments(): iterable
    {
        yield 'not an object' => [[1, 2], 'The arguments must be an object.'];
        yield 'unknown' => [['id' => 1, 'sql' => 'x'], 'Unknown argument "sql".'];
        yield 'missing' => [['name' => 'a'], 'The argument "id" is required.'];
        yield 'string for integer' => [['id' => '1'], 'The argument "id" must be of type integer.'];
        yield 'fraction' => [['id' => 1.5], 'The argument "id" must be of type integer.'];
        yield 'too small' => [['id' => 0], 'The argument "id" must be at least 1.'];
        yield 'too large' => [['id' => 101], 'The argument "id" must be at most 100.'];
        yield 'empty string' => [['id' => 1, 'name' => ''], 'The argument "name" must have at least 1 characters.'];
        yield 'long string' => [['id' => 1, 'name' => 'abcdef'], 'The argument "name" must have at most 5 characters.'];
        yield 'not utf-8' => [['id' => 1, 'name' => "\xC3"], 'The argument "name" must be UTF-8 text.'];
        yield 'enum' => [['id' => 1, 'state' => 'trashed'], 'The argument "state" must be one of: all, published.'];
        yield 'boolean' => [['id' => 1, 'flag' => 1], 'The argument "flag" must be of type boolean.'];
        yield 'object for array' => [['id' => 1, 'ids' => ['a' => 1]], 'The argument "ids" must be of type array.'];
        yield 'too many items' => [['id' => 1, 'ids' => [1, 2, 3]], 'The argument "ids" must have at most 2 items.'];
        yield 'bad item' => [['id' => 1, 'ids' => [0]], 'The argument "ids[0]" must be at least 1.'];
    }

    #[DataProvider('invalidArguments')]
    public function testInvalidArgumentsAreNamed(mixed $arguments, string $message): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        ArgumentValidator::validate($arguments, self::SCHEMA);
    }
}
