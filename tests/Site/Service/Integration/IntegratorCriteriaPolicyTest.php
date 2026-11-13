<?php

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Site\Service\Integration;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Site\Service\Integration\IntegratorCriteriaPolicy;

final class IntegratorCriteriaPolicyTest extends TestCase
{
    /** @return array<string, array{string, bool}> */
    public static function operatorProvider(): array
    {
        return [
            'equals' => ['=', true],
            'not equals' => ['<>', true],
            'greater or equal' => ['>=', true],
            'contains' => ['%...%', true],
            'starts with' => ['%...', true],
            'ends with' => ['...%', true],
            'SQL fragment' => ['= 1 OR 1=1 --', false],
            'unknown operator' => ['REGEXP', false],
        ];
    }

    #[DataProvider('operatorProvider')]
    public function testAllowsOnlyConfiguredOperators(string $operator, bool $expected): void
    {
        self::assertSame($expected, IntegratorCriteriaPolicy::allowsOperator($operator));
    }

    public function testAllowsOnlyBooleanConnectors(): void
    {
        self::assertTrue(IntegratorCriteriaPolicy::allowsConnector('AND'));
        self::assertTrue(IntegratorCriteriaPolicy::allowsConnector('OR'));
        self::assertFalse(IntegratorCriteriaPolicy::allowsConnector('OR 1=1 --'));
    }
}
