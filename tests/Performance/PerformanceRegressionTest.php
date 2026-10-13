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

namespace Vcmb\Component\BreezingformsNG\Tests\Performance;

use PHPUnit\Framework\TestCase;

final class PerformanceRegressionTest extends TestCase
{
    private const ROOT = __DIR__ . '/../..';

    public function testAdminListQueriesDoNotLoadLargeUnusedColumns(): void
    {
        $forms = $this->read('administrator/components/com_breezingformsng/src/Model/FormsModel.php');
        $packages = $this->read('administrator/components/com_breezingformsng/src/Model/PackageListModel.php');

        self::assertStringNotContainsString("->select('*')", $forms);
        self::assertStringNotContainsString("->select('*')", $packages);
        self::assertStringNotContainsString("'code'", $packages);
    }

    public function testIntegratorItemsAreCachedPerRule(): void
    {
        $source = $this->read('components/com_breezingformsng/src/Service/Integration/IntegratorRuntime.php');

        self::assertStringContainsString('private array $itemsByRule = [];', $source);
        self::assertStringContainsString('array_key_exists($ruleId, $this->itemsByRule)', $source);
        self::assertStringContainsString('return $this->itemsByRule[$ruleId] = $out;', $source);
    }

    public function testExportsLoadSubrecordsInBoundedBatches(): void
    {
        $model = $this->read('administrator/components/com_breezingformsng/src/Model/RecordModel.php');
        $controller = $this->read('administrator/components/com_breezingformsng/src/Controller/RecordsController.php');

        self::assertStringContainsString('public function getSubrecordsByRecordIds(array $recordIds): array', $model);
        self::assertStringContainsString('whereIn($db->quoteName(\'subs.record\')', $model);
        self::assertStringContainsString('self::EXPORT_SUBRECORD_BATCH_SIZE', $controller);
        self::assertSame(2, substr_count($controller, 'getSubrecordsByRecordIds('));
    }

    public function testExportFlagUpdatesAreBounded(): void
    {
        $source = $this->read('administrator/components/com_breezingformsng/src/Model/RecordModel.php');

        self::assertStringContainsString('private const BULK_ID_BATCH_SIZE = 500;', $source);
        self::assertStringContainsString('array_chunk($ids, self::BULK_ID_BATCH_SIZE)', $source);
    }

    private function read(string $path): string
    {
        $source = file_get_contents(self::ROOT . '/' . $path);

        self::assertNotFalse($source, "Unable to read {$path}");

        return $source;
    }
}
