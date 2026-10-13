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
use Joomla\CMS\Language\Text;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Vcmb\Component\BreezingformsNG\Site\Service\Runtime\ErrorHandlerRuntime;

require_once __DIR__ . '/../Rendering/QuickMode/joomla-text-stub.php';

if (!class_exists(HTML_facileFormsProcessor::class)) {
    require_once __DIR__ . '/../../../../components/com_breezingformsng/src/Support/processor_facade.php';
}

final class ErrorHandlerRuntimeTest extends TestCase
{
    public function testRecordsWarningDetailsInTheTraceBuffer(): void
    {
        $processor = $this->processor();

        (new ErrorHandlerRuntime($processor, 'https://example.test'))->handle(
            E_WARNING,
            'warning <message>',
            '/tmp/form.php',
            12
        );

        self::assertStringContainsString('E_WARNING', $processor->traceBuffer);
        self::assertStringContainsString('warning &lt;message&gt;', $processor->traceBuffer);
        self::assertStringContainsString('/tmp/form.php', $processor->traceBuffer);
        self::assertStringContainsString('12', $processor->traceBuffer);
        self::assertStringContainsString(Text::_('COM_BREEZINGFORMSNG_PROCESS_ERRMSG'), $processor->traceBuffer);
    }

    public function testIgnoresStrictDeprecationLevels(): void
    {
        $processor = $this->processor();
        $processor->traceBuffer = 'unchanged';
        $runtime = new ErrorHandlerRuntime($processor, 'https://example.test');

        $runtime->handle(2048, 'strict deprecation', '/tmp/form.php', 20);
        self::assertSame('unchanged', $processor->traceBuffer);

        $runtime->handle(16384, 'language deprecation', '/tmp/form.php', 21);
        self::assertSame('unchanged', $processor->traceBuffer);
    }

    public function testDoesNothingWhenTheProcessorIsDying(): void
    {
        $processor = $this->processor();
        $processor->dying = true;
        $processor->traceBuffer = 'unchanged';

        (new ErrorHandlerRuntime($processor, 'https://example.test'))->handle(
            E_WARNING,
            'ignored warning',
            '/tmp/form.php',
            30
        );

        self::assertSame('unchanged', $processor->traceBuffer);
    }

    public function testFacadeKeepsOnlyTheGlobalHandlerAdapter(): void
    {
        $source = file_get_contents(
            __DIR__ . '/../../../../components/com_breezingformsng/src/Support/processor_facade.php'
        );

        self::assertIsString($source);
        self::assertStringContainsString('new ErrorHandlerRuntime(', $source);
        self::assertStringNotContainsString('$database = $ff_processor->database', $source);
        self::assertStringNotContainsString('COM_BREEZINGFORMSNG_PROCESS_EXCAUGHT', $source);
    }

    private function processor(): HTML_facileFormsProcessor
    {
        $processor = (new ReflectionClass(HTML_facileFormsProcessor::class))->newInstanceWithoutConstructor();
        $processor->dying = false;
        $processor->traceMode = 0;
        $processor->traceStack = [];
        $processor->traceBuffer = '';

        return $processor;
    }
}
