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

use HTML_facileFormsProcessor;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Vcmb\Component\BreezingformsNG\Site\Service\Runtime\TraceRuntime;

require_once __DIR__ . '/../Rendering/QuickMode/joomla-text-stub.php';
require_once __DIR__ . '/../Rendering/QuickMode/joomla-cmsapplication-stub.php';
require_once __DIR__ . '/../Rendering/QuickMode/joomla-uri-stub.php';

if (!class_exists(HTML_facileFormsProcessor::class)) {
    require_once __DIR__ . '/../../../../components/com_breezingformsng/src/Support/processor_facade.php';
}

if (!function_exists('Vcmb\\Component\\BreezingformsNG\\Site\\Service\\Runtime\\nl')) {
    eval('namespace Vcmb\\Component\\BreezingformsNG\\Site\\Service\\Runtime; function nl(): string { return "\\r\\n"; }');
}

final class TraceRuntimeTest extends TestCase
{
    public function testTraceAndTraceLineRecordMessagesAndUpdateTheCurrentLine(): void
    {
        $processor = $this->processor(_FF_TRACEMODE_MESSAGE);
        $processor->traceStack = [['mode', 'p', 'piece', 1, 'p', 2, 0]];
        $runtime = new TraceRuntime($processor);

        $runtime->trace('hello <world>');
        $runtime->traceLine(17, 'line <message>');

        self::assertStringContainsString('hello &lt;world&gt;', $processor->traceBuffer);
        self::assertStringContainsString('17: line &lt;message&gt;', $processor->traceBuffer);
        self::assertSame(17, $processor->traceStack[0][3]);
    }

    public function testPieceTracePushesAndPopsItsFrame(): void
    {
        $processor = $this->processor(_FF_TRACEMODE_FIRST);
        $runtime = new TraceRuntime($processor);
        $mode = _FF_TRACEMODE_PIECE;

        $runtime->tracePiece($mode, 'before', 4, 'p', 8, 0);
        self::assertCount(1, $processor->traceStack);
        self::assertStringContainsString('+COM_BREEZINGFORMSNG_PROCESS_ENTER before', $processor->traceBuffer);

        self::assertSame('result', $runtime->traceExit(9, 'result'));
        self::assertCount(0, $processor->traceStack);
        self::assertStringContainsString('-COM_BREEZINGFORMSNG_PROCESS_LEAVE before', $processor->traceBuffer);
    }

    public function testFunctionTraceFormatsSupportedArgumentKinds(): void
    {
        $processor = $this->processor(_FF_TRACEMODE_FIRST);
        $runtime = new TraceRuntime($processor);
        $mode = _FF_TRACEMODE_FUNCTION;
        $args = [null, true, 12, "line\nvalue", [], (object) []];

        $runtime->traceFunction($mode, 'submit', 22, 'f', 3, 0, $args);

        self::assertStringContainsString(
            "+COM_BREEZINGFORMSNG_PROCESS_ENTER submit(null, true, 12, &#039;line value&#039;, "
                . 'COM_BREEZINGFORMSNG_PROCESS_ARRAY, COM_BREEZINGFORMSNG_PROCESS_OBJECT)',
            $processor->traceBuffer
        );
        self::assertCount(1, $processor->traceStack);
    }

    public function testEmptyExitReportsAWarningAndReturnsItsValue(): void
    {
        $processor = $this->processor(_FF_TRACEMODE_FIRST);

        self::assertSame(42, (new TraceRuntime($processor))->traceExit(30, 42));
        self::assertStringContainsString('COM_BREEZINGFORMSNG_PROCESS_WARNSTK', $processor->traceBuffer);
    }

    public function testEvalTraceHonorsModeAndEscapesTheExpression(): void
    {
        $processor = $this->processor(_FF_TRACEMODE_EVAL);
        $runtime = new TraceRuntime($processor);

        $runtime->traceEval('answer <x>');
        self::assertStringContainsString('eval(answer &lt;x&gt;)', $processor->traceBuffer);

        $buffer = $processor->traceBuffer;
        $processor->traceMode = 0;
        $runtime->traceEval('ignored');
        self::assertSame($buffer, $processor->traceBuffer);
    }

    public function testDumpTraceEmitsTheScriptAndClearsTheBuffer(): void
    {
        $processor = $this->processor(0);
        $processor->traceBuffer = 'trace message';
        $processor->dying = true;

        ob_start();
        try {
            (new TraceRuntime($processor))->dumpTrace();
            $output = ob_get_contents();
        } finally {
            ob_end_clean();
        }

        self::assertIsString($output);
        self::assertStringContainsString('<script type="text/javascript">', $output);
        self::assertStringContainsString('trace message', $output);
        self::assertNull($processor->traceBuffer);
    }

    public function testSuicideMarksTheProcessorOnlyOnce(): void
    {
        $processor = $this->processor(0);
        $runtime = new TraceRuntime($processor);
        $previousReporting = error_reporting();

        try {
            self::assertTrue($runtime->suicide());
            self::assertTrue($processor->dying);
            self::assertSame(0, error_reporting());
            self::assertFalse($runtime->suicide());
        } finally {
            error_reporting($previousReporting);
        }
    }

    public function testFacadeRetainsGlobalAdaptersWithoutTraceImplementation(): void
    {
        $source = file_get_contents(
            __DIR__ . '/../../../../components/com_breezingformsng/src/Support/processor_facade.php'
        );

        self::assertIsString($source);
        self::assertStringContainsString('(new TraceRuntime($ff_processor))->traceFunction(', $source);
        self::assertStringContainsString('(new TraceRuntime($ff_processor))->traceExit(', $source);
        self::assertStringContainsString('$this->traceRuntime()->dumpTrace();', $source);
        self::assertStringContainsString('$this->traceRuntime()->traceEval($name);', $source);
        self::assertStringContainsString('return $this->traceRuntime()->suicide();', $source);
        self::assertStringContainsString('return $this->traceRuntime()->bury();', $source);
        self::assertStringNotContainsString('COM_BREEZINGFORMSNG_PROCESS_ENTER', $source);
        self::assertStringNotContainsString('array_push($ff_processor->traceStack', $source);

        $codeToolsSource = file_get_contents(
            __DIR__ . '/../../../../components/com_breezingformsng/src/Service/Runtime/CodeToolsRuntime.php'
        );

        self::assertIsString($codeToolsSource);
        self::assertStringContainsString('$this->traceRuntime()->dumpTrace();', $codeToolsSource);
        self::assertStringContainsString('$this->traceRuntime()->traceEval($name);', $codeToolsSource);
        self::assertStringNotContainsString('echo \'<pre>\' . $this->processor->traceBuffer', $codeToolsSource);
    }

    private function processor(int $traceMode): HTML_facileFormsProcessor
    {
        $processor = (new ReflectionClass(HTML_facileFormsProcessor::class))->newInstanceWithoutConstructor();
        $processor->dying = false;
        $processor->traceMode = $traceMode;
        $processor->traceStack = [];
        $processor->traceBuffer = '';

        return $processor;
    }
}
