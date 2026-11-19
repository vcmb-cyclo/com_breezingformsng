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

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator;

use PHPUnit\Framework\TestCase;

/**
 * QuickmodeModel::save2() only updates the elements of the form being saved: an element id of
 * another form is treated as a new element and the other form's row is left untouched.
 */
final class QuickmodeElementOwnershipTest extends TestCase
{
    /**
     * @param list<array{0: int, 1: string}> $saved
     * @return array<int, array<string, mixed>>
     */
    private function save(array $saved): array
    {
        $process = proc_open(
            [PHP_BINARY, __DIR__ . '/fixtures/quickmode-element-ownership.php', json_encode($saved, JSON_THROW_ON_ERROR)],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes
        );
        self::assertIsResource($process);
        $output = stream_get_contents($pipes[1]);
        $error = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        self::assertSame(0, proc_close($process), $error);

        return json_decode($output, true, 512, JSON_THROW_ON_ERROR);
    }

    public function testAnElementOfTheFormIsUpdatedInPlace(): void
    {
        $elements = $this->save([[1, 'a'], [2, 'b']]);

        self::assertSame('Saved a', $elements[1]['title']);
        self::assertSame('Saved b', $elements[2]['title']);
        self::assertSame(7, (int) $elements[1]['form']);
    }

    public function testAnElementIdOfAnotherFormIsNotUpdatedNorMoved(): void
    {
        $elements = $this->save([[1, 'a'], [2, 'b'], [50, 'stolen']]);

        self::assertSame(['id' => 50, 'form' => 9, 'name' => 'c', 'title' => 'Foreign C'], $elements[50]);
    }

    public function testAnElementIdOfAnotherFormBecomesANewElementOfTheForm(): void
    {
        $elements = $this->save([[1, 'a'], [2, 'b'], [50, 'stolen']]);

        $created = array_values(array_filter($elements, static fn (array $row): bool => $row['name'] === 'stolen'));
        self::assertCount(1, $created);
        self::assertSame(7, (int) $created[0]['form']);
        self::assertGreaterThan(50, $created[0]['id']);
        self::assertCount(4, $elements);
    }

    public function testAnElementIdOfAnotherFormWithTheNameOfAnOwnElementUpdatesTheOwnElement(): void
    {
        $elements = $this->save([[50, 'a'], [2, 'b']]);

        self::assertSame('Saved a', $elements[1]['title']);
        self::assertSame('Foreign C', $elements[50]['title']);
        self::assertSame(9, (int) $elements[50]['form']);
        self::assertCount(3, $elements);
    }
}
