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

namespace Vcmb\Component\BreezingformsNG\Tests\Site\Service\Runtime;

use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Site\Service\Runtime\TraceModeFormatter;

final class TraceModeFormatterTest extends TestCase
{
    protected function setUp(): void
    {
        defined('_FF_TRACEMODE_EVAL') || define('_FF_TRACEMODE_EVAL', 8);
        defined('_FF_TRACEMODE_PIECE') || define('_FF_TRACEMODE_PIECE', 16);
        defined('_FF_TRACEMODE_FUNCTION') || define('_FF_TRACEMODE_FUNCTION', 32);
        defined('_FF_TRACEMODE_MESSAGE') || define('_FF_TRACEMODE_MESSAGE', 64);
        defined('_FF_TRACEMODE_LOCAL') || define('_FF_TRACEMODE_LOCAL', 128);
        defined('_FF_TRACEMODE_DIRECT') || define('_FF_TRACEMODE_DIRECT', 256);
        defined('_FF_TRACEMODE_APPEND') || define('_FF_TRACEMODE_APPEND', 512);
        defined('_FF_TRACEMODE_DISABLE') || define('_FF_TRACEMODE_DISABLE', 1024);
        defined('_FF_TRACEMODE_FIRST') || define('_FF_TRACEMODE_FIRST', 2048);
        defined('_FF_TRACEMODE_PRIORITY') || define('_FF_TRACEMODE_PRIORITY', 7);
        defined('_FF_TRACEMODE_TOPIC') || define('_FF_TRACEMODE_TOPIC', 120);
    }

    public function testReturnsNonIntegerModesUnchanged(): void
    {
        self::assertSame('normal', (new TraceModeFormatter())->format('normal'));
        self::assertNull((new TraceModeFormatter())->format(null));
    }

    public function testFormatsDisabledFirstDirectMode(): void
    {
        $mode = _FF_TRACEMODE_FIRST | _FF_TRACEMODE_DIRECT | _FF_TRACEMODE_DISABLE;

        self::assertSame('(first direct disable)', (new TraceModeFormatter())->format($mode));
    }

    public function testFormatsPriorityScopeAndTopics(): void
    {
        $mode = _FF_TRACEMODE_APPEND
            | 3
            | _FF_TRACEMODE_LOCAL
            | _FF_TRACEMODE_EVAL
            | _FF_TRACEMODE_FUNCTION;

        self::assertSame('(append high local eval function)', (new TraceModeFormatter())->format($mode));
    }

    public function testFormatsNoTopicsAndAllTopics(): void
    {
        $formatter = new TraceModeFormatter();

        self::assertSame('(popup minimum global none)', $formatter->format(0));
        self::assertSame('(popup minimum global all)', $formatter->format(_FF_TRACEMODE_TOPIC));
    }
}
