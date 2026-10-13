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
use Vcmb\Component\BreezingformsNG\Administrator\Service\ScriptSignatureParser;

final class ScriptManagerSignatureTest extends TestCase
{
    #[DataProvider('signatureProvider')]
    public function testExtractsJavaScriptCallbackSignatures(string $code, array $expected): void
    {
        self::assertSame($expected, (new ScriptSignatureParser())->parse($code, 'fallback'));
    }

    public static function signatureProvider(): iterable
    {
        yield 'function declaration' => [
            'function validate(value, count = 2) {}',
            ['validate', ['value', 'count'], ['', '2']],
        ];
        yield 'function expression' => [
            'const submit = function (event) {};',
            ['submit', ['event'], ['']],
        ];
        yield 'arrow expression with rest parameter' => [
            'const render = (...items) => items;',
            ['render', ['items'], ['']],
        ];
        yield 'fallback when no signature exists' => [
            'return value;',
            ['fallback', [], []],
        ];
        yield 'invalid parameter names are ignored' => [
            'function submit(123invalid, valid) {}',
            ['submit', ['valid'], ['']],
        ];
    }
}
