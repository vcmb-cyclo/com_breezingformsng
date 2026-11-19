<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Scripts;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/scripts/CoverageRatchet.php';

/** The coverage ratchet of scripts/check-coverage.php, on synthetic reports: no coverage is measured here. */
final class CoverageRatchetTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/bfng-ratchet-' . bin2hex(random_bytes(6));
        mkdir($this->directory);
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->directory . '/*') ?: []);
        rmdir($this->directory);
    }

    /** @return iterable<string, array{float, bool, bool}> */
    public static function measures(): iterable
    {
        yield 'equal to the baseline' => [30.46, false, false];
        yield 'within the tolerance' => [29.97, false, false];
        yield 'exactly at the tolerance' => [29.96, false, false];
        yield 'below the tolerance' => [29.95, true, false];
        yield 'far below' => [10.0, true, false];
        yield 'slightly above' => [31.0, false, false];
        yield 'exactly 1 point above' => [31.46, false, false];
        yield 'more than 1 point above' => [31.47, false, true];
        yield 'far above' => [60.0, false, true];
    }

    #[DataProvider('measures')]
    public function testItFailsBelowTheToleranceAndSuggestsRaisingTheBaseline(float $percentage, bool $failed, bool $raise): void
    {
        $result = \CoverageRatchet::evaluate($percentage, 30.46);

        self::assertSame($failed, $result['failed']);
        self::assertSame($raise, $result['notice'] !== '');
        self::assertStringContainsString('30.46', $result['message']);
        self::assertStringContainsString('29.96', $result['message']);

        if ($raise) {
            self::assertStringContainsString('coverage-baseline.txt', $result['notice']);
        }
    }

    public function testTheCommittedBaselineIsThePercentageMeasuredOn2026_11_18(): void
    {
        self::assertSame(30.46, \CoverageRatchet::readBaseline(dirname(__DIR__, 2) . '/coverage-baseline.txt'));
    }

    public function testTheBaselineSkipsCommentsAndBlankLines(): void
    {
        $file = $this->directory . '/baseline.txt';
        file_put_contents($file, "# a comment\n\n  42.5  \n99\n");

        self::assertSame(42.5, \CoverageRatchet::readBaseline($file));
    }

    /** @return iterable<string, array{string}> */
    public static function badBaselines(): iterable
    {
        yield 'empty' => [''];
        yield 'only comments' => ["# nothing\n"];
        yield 'not a number' => ["thirty\n"];
        yield 'negative' => ["-1\n"];
        yield 'above 100' => ["101\n"];
    }

    #[DataProvider('badBaselines')]
    public function testAnUnusableBaselineIsRefused(string $content): void
    {
        $file = $this->directory . '/baseline.txt';
        file_put_contents($file, $content);

        $this->expectException(\RuntimeException::class);
        \CoverageRatchet::readBaseline($file);
    }

    public function testAMissingBaselineIsRefused(): void
    {
        $this->expectException(\RuntimeException::class);
        \CoverageRatchet::readBaseline($this->directory . '/missing.txt');
    }

    /** @return array{int, string, string} */
    private function runScript(int $covered, int $statements, string ...$arguments): array
    {
        $report = $this->directory . '/clover.xml';
        file_put_contents($report, sprintf('<coverage><project><metrics statements="%d" coveredstatements="%d"/></project></coverage>', $statements, $covered));
        $process = proc_open(
            [PHP_BINARY, dirname(__DIR__, 2) . '/scripts/check-coverage.php', $report, ...$arguments],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            null,
            ['PATH' => (string) getenv('PATH')]
        );
        self::assertIsResource($process);
        $output = (string) stream_get_contents($pipes[1]);
        $error = (string) stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        return [proc_close($process), $output, $error];
    }

    public function testTheScriptFailsWhenTheCoverageDropsBelowTheBaselineMinusTheTolerance(): void
    {
        $baseline = $this->directory . '/baseline.txt';
        file_put_contents($baseline, "30.46\n");

        // 29.9 %: above the fixed minimum of 20, below 30.46 - 0.5.
        [$status, , $error] = $this->runScript(299, 1000, '20', $baseline);
        self::assertSame(1, $status);
        self::assertStringContainsString('baseline', $error);

        // 30.0 %: inside the tolerance.
        [$status, $output] = $this->runScript(300, 1000, '20', $baseline);
        self::assertSame(0, $status);
        self::assertStringContainsString('Coverage baseline: 30.46%', $output);
        self::assertStringNotContainsString('::notice::', $output);
    }

    public function testTheScriptSuggestsRaisingTheBaseline(): void
    {
        $baseline = $this->directory . '/baseline.txt';
        file_put_contents($baseline, "30.46\n");

        [$status, $output] = $this->runScript(330, 1000, '20', $baseline);

        self::assertSame(0, $status);
        self::assertStringContainsString('::notice::', $output);
        self::assertStringContainsString('consider raising', $output);
    }

    public function testTheScriptKeepsTheFixedMinimumWithoutABaseline(): void
    {
        [$status] = $this->runScript(250, 1000, '30');
        self::assertSame(1, $status);

        [$status, $output] = $this->runScript(310, 1000, '30');
        self::assertSame(0, $status);
        self::assertStringNotContainsString('baseline', $output);
    }

    public function testTheScriptFailsOnAMissingBaselineFile(): void
    {
        [$status, , $error] = $this->runScript(500, 1000, '30', $this->directory . '/missing.txt');

        self::assertSame(1, $status);
        self::assertStringContainsString('baseline not found', $error);
    }
}
