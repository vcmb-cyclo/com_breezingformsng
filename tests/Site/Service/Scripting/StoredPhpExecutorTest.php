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

namespace Vcmb\Component\BreezingformsNG\Tests\Site\Service\Scripting;

use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Site\Service\Scripting\StoredPhpExecutor;

final class StoredPhpExecutorTest extends TestCase
{
    public function testExecutesWithVariablesAndSuppliedObjectScope(): void
    {
        $scope = new class {
            public string $prefix = 'value';
        };

        $result = (new StoredPhpExecutor())->execute(
            $scope,
            'return $this->prefix . ":" . $value;',
            ['value' => 'processed']
        );

        self::assertSame('value:processed', $result);
    }

    public function testAVariablePassedByReferenceCanBeChanged(): void
    {
        $value = ' Mixed Case ';

        (new StoredPhpExecutor())->execute(new class {
        }, '$value = strtolower(trim($value));', ['value' => &$value]);

        self::assertSame('mixed case', $value);
    }

    public function testAVariablePassedByValueIsLeftAsItIs(): void
    {
        $value = 'kept';

        (new StoredPhpExecutor())->execute(new class {
        }, '$value = "changed";', ['value' => $value]);

        self::assertSame('kept', $value);
    }

    public function testEmptyCodeReturnsNull(): void
    {
        $scope = new class {
        };

        self::assertNull((new StoredPhpExecutor())->execute($scope, 'return null;'));
    }
}
