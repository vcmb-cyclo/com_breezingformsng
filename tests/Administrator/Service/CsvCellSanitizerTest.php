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

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator\Service;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Administrator\Service\CsvCellSanitizer;

final class CsvCellSanitizerTest extends TestCase
{
    /** @return array<string, array{string, string}> */
    public static function cellProvider(): array
    {
        return [
            'formula equals' => ['=1+1', "'=1+1"],
            'formula plus' => ['+SUM(A1:A2)', "'+SUM(A1:A2)"],
            'formula minus' => ['-SUM(A1:A2)', "'-SUM(A1:A2)"],
            'formula at' => ['@SUM(A1:A2)', "'@SUM(A1:A2)"],
            'formula after whitespace' => [" \t=1+1", "' \t=1+1"],
            'leading tab' => ["\ttext", "'\ttext"],
            'plain text' => ['ordinary text', 'ordinary text'],
            'negative number remains numeric' => ['-12.5', '-12.5'],
            'signed exponent remains numeric' => ['+1.2E-3', '+1.2E-3'],
            'zero remains unchanged' => ['0', '0'],
        ];
    }

    #[DataProvider('cellProvider')]
    public function testNeutralizesFormulaCellsAndPreservesOrdinaryValues(string $value, string $expected): void
    {
        self::assertSame($expected, CsvCellSanitizer::neutralizeFormula($value));
    }

    public function testBothCsvExportPathsUseTheSanitizer(): void
    {
        $root = dirname(__DIR__, 3);
        $recordExporterPath = $root . '/administrator/components/com_breezingformsng/src/Service/RecordExporter.php';
        $notificationExporterPath = $root . '/components/com_breezingformsng/src/Service/Export/ExportEngine.php';
        $recordExporter = file_get_contents($recordExporterPath);
        $notificationExporter = file_get_contents($notificationExporterPath);

        self::assertIsString($recordExporter);
        self::assertIsString($notificationExporter);
        self::assertStringContainsString('CsvCellSanitizer::neutralizeFormula', $recordExporter);
        self::assertSame(3, substr_count($notificationExporter, 'CsvCellSanitizer::neutralizeFormula'));
    }
}
