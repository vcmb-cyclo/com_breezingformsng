<?php

/**
 * BreezingForms NG - A Joomla Forms Application
 *
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 *
 * SPDX-License-Identifier: GPL-2.0-or-later
 **/

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Site\Service\Rendering;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;
use Vcmb\Component\BreezingformsNG\Site\Service\Rendering\Debug\FieldOrder;

final class FieldOrderTest extends TestCase
{
    /** @return array<string, array{string, string, bool, list<string>}> */
    public static function moves(): array
    {
        return [
            'up' => ['C', 'A', false, ['C', 'A', 'B', 'D']],
            'down' => ['A', 'C', true, ['B', 'C', 'A', 'D']],
            'across a nested section' => ['C', 'B', false, ['A', 'C', 'B', 'D']],
        ];
    }

    /** @param list<string> $expected */
    #[DataProvider('moves')]
    public function testAFieldMovesAmongTheFieldsOfItsSection(
        string $field,
        string $target,
        bool $after,
        array $expected
    ): void {
        $tree = $this->tree();

        self::assertTrue(FieldOrder::move($tree, $field, $target, $after));
        self::assertSame($expected, array_values(array_diff(FieldOrder::names($tree), ['N'])));
    }

    public function testImpossibleOrEmptyMovesLeaveTheTree(): void
    {
        // Another section, unknown fields, the field itself and its present place.
        foreach ([['A', 'N'], ['A', 'D'], ['X', 'A'], ['A', 'X'], ['A', 'A'], ['A', 'B']] as [$field, $target]) {
            $tree = $this->tree();

            self::assertFalse(FieldOrder::move($tree, $field, $target, false), $field . ' ' . $target);
            self::assertEquals($this->tree(), $tree);
        }
    }

    public function testSeveralMovesAreAppliedInTheirOrder(): void
    {
        $moves = FieldOrder::moves(
            '[{"field":"C","target":"A","position":"before"},{"field":"A","target":"B","position":"after"}]'
        );
        $tree = $this->tree();

        self::assertNotNull($moves);
        self::assertTrue(FieldOrder::moveAll($tree, $moves));
        self::assertSame(['C', 'B', 'A', 'D'], array_values(array_diff(FieldOrder::names($tree), ['N'])));

        // One impossible move refuses them all.
        $moves[] = ['field' => 'A', 'target' => 'D', 'after' => true];
        $tree = $this->tree();
        self::assertFalse(FieldOrder::moveAll($tree, $moves));
        // Edits alone come without any move.
        self::assertSame([], FieldOrder::moves('[]'));
        self::assertTrue(FieldOrder::moveAll($tree, []));
    }

    public function testUnreadableMovesAreRefused(): void
    {
        $move = '{"field":"A","target":"B","position":"after"}';

        foreach (
            [
                '', '{"a":1}', '"A"', '[1]', '[{"field":"A","target":"B"}]',
                '[{"field":["A"],"target":"B","position":"after"}]',
                '[{"field":"A","target":"B","position":"under"}]',
                '[' . implode(',', array_fill(0, 201, $move)) . ']',
            ] as $json
        ) {
            self::assertNull(FieldOrder::moves($json), substr($json, 0, 60));
        }
    }

    public function testEmptyObjectsStayObjects(): void
    {
        $tree = $this->tree();
        FieldOrder::move($tree, 'B', 'A', false);

        self::assertStringContainsString('"data":{}', (string) json_encode($tree));
    }

    public function testPositionsFollowTheNewOrder(): void
    {
        self::assertSame(
            ['C' => 4, 'A' => 5, 'B' => 9],
            FieldOrder::reassign(['A' => 4, 'B' => 5, 'C' => 9], ['A', 'B', 'C'], ['C', 'A', 'B'])
        );

        $tree = $this->tree();
        $former = FieldOrder::names($tree);
        FieldOrder::move($tree, 'B', 'A', false);
        FieldOrder::renumber($tree, $former);
        $numbers = [];

        foreach ($tree->children[0]->children as $node) {
            $numbers[$node->properties->bfName ?? '-'] = $node->properties->orderNumber ?? null;
        }

        // B takes the number of A; a field without a number keeps none.
        self::assertSame(['B' => 1, 'A' => 2, '-' => null, 'C' => -1], $numbers);
    }

    public function testTheElementListFollowsAndKeepsItsOtherEntries(): void
    {
        $json = '[{"elements":[{"name":"bfFakeName","orderNumber":0},{"name":"A","orderNumber":1,"id":10},'
            . '{"name":"B","orderNumber":2,"id":11,"options":{}}],"elementCount":2}]';

        self::assertSame(
            '[{"elements":[{"name":"bfFakeName","orderNumber":0},{"name":"B","orderNumber":1,"id":11,"options":{}},'
            . '{"name":"A","orderNumber":2,"id":10}],"elementCount":2}]',
            FieldOrder::areas($json, ['A', 'B'], ['B', 'A'])
        );
        self::assertSame('', FieldOrder::areas('', ['A'], ['A']));
    }

    /** A page holding A, B, a section with N, then C; a second page holding D. */
    private function tree(): stdClass
    {
        $field = static fn (string $name, int $number): array => [
            'data' => new stdClass(),
            'properties' => ['type' => 'element', 'bfName' => $name, 'orderNumber' => $number],
        ];
        $holder = static fn (string $type, array $children): array => [
            'properties' => ['type' => $type],
            'children' => $children,
        ];

        return json_decode((string) json_encode($holder('root', [
            $holder('page', [$field('A', 1), $field('B', 2), $holder('section', [$field('N', -1)]), $field('C', -1)]),
            $holder('page', [$field('D', 1)]),
        ])), false);
    }
}
