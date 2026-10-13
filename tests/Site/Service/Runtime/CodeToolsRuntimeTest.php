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
use Vcmb\Component\BreezingformsNG\Site\Service\Runtime\CodeToolsRuntime;

if (!defined('JPATH_ADMINISTRATOR')) {
    define('JPATH_ADMINISTRATOR', __DIR__ . '/../../../../administrator');
}

if (!defined('JPATH_SITE')) {
    define('JPATH_SITE', __DIR__ . '/../../../../');
}

require_once __DIR__ . '/../Rendering/QuickMode/joomla-cmsapplication-stub.php';
require_once __DIR__ . '/../Rendering/QuickMode/joomla-uri-stub.php';

if (!class_exists(HTML_facileFormsProcessor::class)) {
    require_once __DIR__ . '/../../../../components/com_breezingformsng/src/Support/processor_facade.php';
}

final class CodeToolsRuntimeTest extends TestCase
{
    public function testDelegatesStringAndJavascriptOperations(): void
    {
        $runtime = new CodeToolsRuntime($this->processor());
        $code = "  alert('ok');\n";

        self::assertTrue($runtime->trim($code));
        self::assertSame("alert('ok');", $code);
        self::assertSame(1, $runtime->nonblank($code));
        $whitespace = " \t\n";
        self::assertSame(0, $runtime->nonblank($whitespace));
        self::assertSame('true', $runtime->expJsValue(true));
        self::assertStringContainsString('answer = 42;', $runtime->expJsVar('answer', 42));
    }

    public function testDelegatesClassAndTraceModeResolution(): void
    {
        $runtime = new CodeToolsRuntime($this->processor(1, '_suffix'));

        self::assertSame('Bootstrap_suffix', $runtime->getClassName('Classic; Bootstrap'));
        self::assertSame('(direct minimum global none)', $runtime->dispTraceMode(_FF_TRACEMODE_DIRECT));
        self::assertSame('unchanged', $runtime->dispTraceMode('unchanged'));
    }

    public function testMeasureTimeConvertsBothMicrotimePartsToFloat(): void
    {
        $source = file_get_contents(
            __DIR__ . '/../../../../components/com_breezingformsng/src/Service/Submission/SubmissionEngine.php'
        );

        self::assertIsString($source);
        self::assertStringContainsString(
            '((float) $time[0] + (float) $time[1]) / 1000',
            $source
        );
    }

    public function testRejectsEmptyOrDyingEvalCode(): void
    {
        $processor = $this->processor();
        $runtime = new CodeToolsRuntime($processor);
        $empty = " \t\n";

        self::assertFalse($runtime->prepareEvalCode($empty, '', '', 0, 0));

        $processor->dying = true;
        $code = 'return 1;';
        self::assertFalse($runtime->prepareEvalCode($code, '', '', 0, 0));
        self::assertSame('return 1;', $code);
    }

    public function testAppliesReplacementTagsWhenTracingIsDisabled(): void
    {
        $processor = $this->processor();
        $processor->traceMode = _FF_TRACEMODE_DISABLE;
        $processor->findtags = ['{name}'];
        $processor->replacetags = ['answer'];
        $runtime = new CodeToolsRuntime($processor);
        $code = 'echo {name};';

        self::assertTrue($runtime->prepareEvalCode($code, '', '', 0, 0));
        self::assertStringContainsString('echo answer;', $code);
        self::assertStringContainsString("/*'/*", $code);
    }

    public function testAppliesTraceDirectiveBeforePatchingCode(): void
    {
        $processor = $this->processor();
        $runtime = new CodeToolsRuntime($processor);
        $code = "//+trace direct low global none\nreturn 1;";

        self::assertTrue($runtime->prepareEvalCode($code, 'answer', 'e', 7, 1));
        self::assertStringContainsString('_ff_tracePiece(', $code);
        self::assertStringContainsString('_ff_traceExit(', $code);
        self::assertSame(0, $processor->traceMode);
    }

    public function testTraceDisableDirectiveWithoutFirstStillPatchesHistorically(): void
    {
        $processor = $this->processor();
        $runtime = new CodeToolsRuntime($processor);
        $code = "//+trace disable\nreturn 1;";

        self::assertTrue($runtime->prepareEvalCode($code, '', '', 0, 0));
        self::assertStringContainsString('_ff_tracePiece(', $code);
        self::assertStringContainsString('return 1;', $code);
        self::assertStringContainsString('/*', $code);
    }

    public function testPatchesFunctionAndReturnTracePoints(): void
    {
        $runtime = new CodeToolsRuntime($this->processor());
        $patched = $runtime->patchCode(2, 'function answer() { return 42; }', "O'Reilly", 'e', 7, 1);

        self::assertStringContainsString("_ff_tracePiece(2,'O", $patched);
        self::assertStringContainsString('_ff_traceFunction(2,__FUNCTION__,1,', $patched);
        self::assertStringContainsString('_ff_traceExit(', $patched);
    }

    public function testFindsLexicalTokensAndAdvancesOffsets(): void
    {
        $runtime = new CodeToolsRuntime($this->processor());
        $code = 'function answer();';
        $position = 0;
        $offset = -1;

        self::assertSame('function', $runtime->findToken($code, $position, $offset));
        self::assertSame(0, $offset);
        self::assertSame(8, $position);
        self::assertSame('(', $runtime->findToken($code, $position, $offset));
        self::assertSame(15, $offset);
    }

    public function testFindsRealTokensOutsideCommentsAndTracksLines(): void
    {
        $runtime = new CodeToolsRuntime($this->processor());
        $code = "function answer() { // ignored\n return 42; }";
        $position = 0;
        $offset = -1;
        $line = 1;

        self::assertSame('function', $runtime->findRealToken($code, $position, $offset, $line));
        self::assertSame('(', $runtime->findRealToken($code, $position, $offset, $line));
        self::assertSame(')', $runtime->findRealToken($code, $position, $offset, $line));
        self::assertSame('{', $runtime->findRealToken($code, $position, $offset, $line));
        self::assertSame('return', $runtime->findRealToken($code, $position, $offset, $line));
        self::assertSame(2, $line);
    }

    public function testFindRealTokenSkipsBlockCommentsBeforeParsingCode(): void
    {
        $runtime = new CodeToolsRuntime($this->processor());
        $code = '/* ignored { return; */ function answer();';
        $position = 0;
        $offset = -1;
        $line = 1;

        self::assertSame('function', $runtime->findRealToken($code, $position, $offset, $line));
        self::assertSame('(', $runtime->findRealToken($code, $position, $offset, $line));
    }

    private function processor(int $template = 0, string $suffix = ''): HTML_facileFormsProcessor
    {
        $processor = (new ReflectionClass(HTML_facileFormsProcessor::class))->newInstanceWithoutConstructor();
        $processor->template = $template;
        $processor->suffix = $suffix;
        $processor->dying = false;
        $processor->traceMode = 0;
        $processor->traceBuffer = '';
        $processor->findtags = [];
        $processor->replacetags = [];

        return $processor;
    }
}
