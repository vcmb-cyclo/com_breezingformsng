<?php

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
            __DIR__ . '/../../../administrator/components/com_breezingformsng/src/Model/RecordModel.php'
        );

        self::assertIsString($model);
        self::assertStringContainsString("'viewed', 'exported', 'state', 'archived',", $model);
        self::assertMatchesRegularExpression(
            "/RecordState::fromImport\\(\\s+\\\$valueAt\\(\\\$row, \\\$normalizedHeader, 'state', null\\),\\s+\\\$valueAt\\(\\\$row, \\\$normalizedHeader, 'archived', null\\)/",
            $model
        );
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
}
