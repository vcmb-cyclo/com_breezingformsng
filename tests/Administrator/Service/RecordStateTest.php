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
use Vcmb\Component\BreezingformsNG\Administrator\Service\RecordState;

final class RecordStateTest extends TestCase
{
    public function testUsesJoomlaStateValues(): void
    {
        self::assertSame(1, RecordState::PUBLISHED);
        self::assertSame(2, RecordState::ARCHIVED);
        self::assertSame(-2, RecordState::TRASHED);
    }

    public function testRejectsStatesARecordCannotTake(): void
    {
        self::assertTrue(RecordState::isValid(-2));
        self::assertFalse(RecordState::isValid(0));
        self::assertFalse(RecordState::isValid(3));
    }

    public static function importProvider(): array
    {
        return [
            'state column'              => ['2', null, 2],
            'trashed state'             => ['-2', null, -2],
            'state wins over archived'  => ['1', '1', 1],
            'unknown state'             => ['0', null, 1],
            'empty state'               => ['', '1', 1],
            'former archived flag set'  => [null, '1', 2],
            'former archived flag off'  => [null, '0', 1],
            'neither column'            => [null, null, 1],
        ];
    }

    #[DataProvider('importProvider')]
    public function testImportReadsTheStateOrTheFormerArchivedFlag(?string $state, ?string $archived, int $expected): void
    {
        self::assertSame($expected, RecordState::fromImport($state, $archived));
    }

    public function testCsvImportReadsBothStatusColumns(): void
    {
        $model = file_get_contents(
            __DIR__ . '/../../../administrator/components/com_breezingformsng/src/Service/RecordImporter.php'
        );

        self::assertIsString($model);
        self::assertStringContainsString("'viewed', 'exported', 'state', 'archived',", $model);
        self::assertMatchesRegularExpression(
            "/RecordState::fromImport\\(\\s+\\\$valueAt\\(\\\$row, \\\$normalizedHeader, 'state', null\\),\\s+\\\$valueAt\\(\\\$row, \\\$normalizedHeader, 'archived', null\\)/",
            $model
        );
    }

    public function testTabularExportWritesTheStatusColumnsTheImportReads(): void
    {
        $admin = __DIR__ . '/../../../administrator/components/com_breezingformsng/src';
        $controller = file_get_contents($admin . '/Service/RecordExporter.php');
        $model = file_get_contents($admin . '/Service/RecordImporter.php');

        self::assertIsString($controller);
        self::assertIsString($model);
        self::assertStringContainsString("'double_opt_in', 'viewed', 'exported', 'state',", $controller);
        self::assertMatchesRegularExpression(
            '/\\(string\\) \\$record->viewed,\\s+\\(string\\) \\$record->exported,\\s+\\(string\\) \\$record->state,/',
            $controller
        );
        self::assertStringContainsString("'viewed', 'exported', 'state', 'archived',", $model);
    }

    public static function filterProvider(): array
    {
        return [
            'no filter'      => ['', '', [1]],
            'published'      => ['1', '1', [1]],
            'archived'       => [' 2 ', '2', [2]],
            'trashed'        => ['-2', '-2', [-2]],
            'every state'    => ['*', '*', null],
            'unpublished'    => ['0', '', [1]],
            'not a state'    => ['abc', '', [1]],
            'sql in a value' => ['1 OR 1=1', '', [1]],
        ];
    }

    #[DataProvider('filterProvider')]
    public function testNormalizesListFilters(string $filter, string $normalized, ?array $states): void
    {
        self::assertSame($normalized, RecordState::normalizeFilter($filter));
        self::assertSame($states, RecordState::filterStates($filter));
    }

    public function testLabelsStatesWithNativeJoomlaKeys(): void
    {
        self::assertSame('JPUBLISHED', RecordState::label(1));
        self::assertSame('JARCHIVED', RecordState::label(2));
        self::assertSame('JTRASHED', RecordState::label(-2));
        self::assertSame('JPUBLISHED', RecordState::label(0));
    }

    public function testASelectionOfTrashedRecordsCanBeDeleted(): void
    {
        self::assertNull(RecordState::deletionRefusal([4, 9], [4 => -2, 9 => -2]));
        self::assertNull(RecordState::deletionRefusal([], []));
    }

    public function testAMixedSelectionIsRefusedWhateverItsOrder(): void
    {
        $states = [4 => RecordState::TRASHED, 9 => RecordState::PUBLISHED];

        self::assertSame('JLIB_APPLICATION_ERROR_DELETE_NOT_PERMITTED', RecordState::deletionRefusal([4, 9], $states));
        self::assertSame('JLIB_APPLICATION_ERROR_DELETE_NOT_PERMITTED', RecordState::deletionRefusal([9, 4], $states));
        self::assertSame(
            'JLIB_APPLICATION_ERROR_DELETE_NOT_PERMITTED',
            RecordState::deletionRefusal([4, 9], [4 => RecordState::TRASHED, 9 => RecordState::ARCHIVED])
        );
    }

    public function testASelectionWithAMissingRecordIsRefusedWhateverItsOrder(): void
    {
        $states = [4 => RecordState::TRASHED, 9 => RecordState::TRASHED];

        self::assertSame('JLIB_APPLICATION_ERROR_NOT_EXIST', RecordState::deletionRefusal([4, 77, 9], $states));
        self::assertSame('JLIB_APPLICATION_ERROR_NOT_EXIST', RecordState::deletionRefusal([77, 4, 9], $states));
        self::assertSame('JLIB_APPLICATION_ERROR_NOT_EXIST', RecordState::deletionRefusal([4, 9, 77], $states));
    }

    public function testTheModelChecksTheSelectionBeforeDeletingAndUndoesAFailedDeletion(): void
    {
        $model = file_get_contents(
            __DIR__ . '/../../../administrator/components/com_breezingformsng/src/Model/RecordModel.php'
        );

        self::assertIsString($model);

        $start = strpos($model, '$db->transactionStart();');
        $lock = strpos($model, '$this->lockRecords($ids);');
        $read = strpos($model, '$records = $this->getFormsAndStates($ids);');
        $refusal = strpos($model, 'RecordState::deletionRefusal(');
        $delete = strpos($model, 'parent::delete($ids)');
        $leftover = strpos($model, 'if ($this->getFormsAndStates($ids) !== []) {');
        $cleanup = strpos($model, '$cleanup->remove($formId, $recordIds);');
        $commit = strpos($model, '$db->transactionCommit();');
        $rollback = strpos($model, '$db->transactionRollback();');

        foreach ([$start, $lock, $read, $refusal, $delete, $leftover, $cleanup, $commit, $rollback] as $position) {
            self::assertNotFalse($position);
        }

        // The selection is read and checked inside the transaction, and a
        // record still there after the deletion undoes it before any cleanup.
        // The records are locked before they are read: a record restored by
        // someone else meanwhile is then seen as restored, not as trashed.
        self::assertLessThan($lock, $start);
        self::assertLessThan($read, $lock);
        self::assertLessThan($refusal, $read);
        self::assertStringContainsString(
            "->set(\$db->quoteName('state') . ' = ' . \$db->quoteName('state'))",
            $model
        );
        self::assertLessThan($delete, $refusal);
        self::assertLessThan($leftover, $delete);
        self::assertLessThan($cleanup, $leftover);
        self::assertLessThan($commit, $cleanup);
        self::assertLessThan($rollback, $commit);
    }

    public function testTheExportsLiveOutsideTheController(): void
    {
        $admin = __DIR__ . '/../../../administrator/components/com_breezingformsng/src';
        $controller = file_get_contents($admin . '/Controller/RecordsController.php');
        $exporter = file_get_contents($admin . '/Service/RecordExporter.php');
        $model = file_get_contents($admin . '/Model/RecordsExportModel.php');

        self::assertIsString($controller);
        self::assertIsString($exporter);
        self::assertIsString($model);
        // The controller only checks the token, asks for the export and sends it.
        self::assertStringNotContainsString('setQuery', $controller);
        self::assertStringNotContainsString('getQuery', $controller);
        self::assertStringNotContainsString('createQuery', $controller);
        self::assertStringNotContainsString('FacileFormsExport', $controller);
        self::assertSame(4, substr_count($controller, "->markExported(\$export['ids']);"));
        // The queries are in the model, the file formats in the service.
        self::assertStringNotContainsString('setQuery', $exporter);
        self::assertStringContainsString('public function getRecords(array $ids, int $formSelection, string $stateFilter): array', $model);
        self::assertStringContainsString('public function getSubrecords(int $recordId): array', $exporter);
    }

    public function testTheCsvImportUsesANativeFormAndAService(): void
    {
        $admin = __DIR__ . '/../../../administrator/components/com_breezingformsng';
        $controller = file_get_contents($admin . '/src/Controller/RecordsimportController.php');
        $records = file_get_contents($admin . '/src/Controller/RecordsController.php');
        $model = file_get_contents($admin . '/src/Model/RecordModel.php');
        $form = simplexml_load_file($admin . '/forms/recordsimport.xml');

        self::assertIsString($controller);
        self::assertIsString($records);
        self::assertIsString($model);
        self::assertNotFalse($form);
        self::assertSame(
            ['form_id', 'csv_file', 'encoding'],
            array_map('strval', $form->xpath('/form/fieldset[@name="import"]/field/@name'))
        );
        self::assertSame('true', (string) $form->xpath('//field[@name="form_id"]/@required')[0]);
        self::assertSame('true', (string) $form->xpath('//field[@name="csv_file"]/@required')[0]);
        // The form is validated by Joomla before the file is read, and importing creates records.
        self::assertLessThan(strpos($controller, '->import('), strpos($controller, '$model->validate($form, $data)'));
        self::assertStringContainsString("authorise('core.create', 'com_breezingformsng')", $controller);
        self::assertStringContainsString('$this->checkToken();', $controller);
        self::assertStringNotContainsString('importCsv', $model);
        self::assertStringNotContainsString('csvImport', $records);
        self::assertFileDoesNotExist($admin . '/tmpl/records/csvimport.php');
    }
}
