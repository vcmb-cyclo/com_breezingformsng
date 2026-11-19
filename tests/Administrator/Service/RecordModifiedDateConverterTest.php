<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator\Service;

use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Administrator\Service\RecordModifiedDateConverter;
use Vcmb\Component\BreezingformsNG\Administrator\Service\RecordModifiedDateStore;

final class RecordModifiedDateConverterTest extends TestCase
{
    public function testConvertsAllDatesToUtcAndMarksDone(): void
    {
        $store = new InMemoryDateStore([1 => '2026-01-15 10:00:00', 2 => '2026-07-15 10:00:00', 3 => '0000-00-00 00:00:00']);

        $count = (new RecordModifiedDateConverter($store))->convert(new \DateTimeZone('Europe/Paris'), true);

        self::assertSame(2, $count);
        self::assertSame([1 => '2026-01-15 09:00:00', 2 => '2026-07-15 08:00:00', 3 => '0000-00-00 00:00:00'], $store->dates);
        self::assertSame('done', $store->marker);
        self::assertSame(['start', 'commit'], $store->transactions);
    }

    public function testSecondRunDoesNotConvertTwice(): void
    {
        $store = new InMemoryDateStore([1 => '2026-01-15 10:00:00']);
        $converter = new RecordModifiedDateConverter($store);

        $converter->convert(new \DateTimeZone('Europe/Paris'), true);
        $count = $converter->convert(new \DateTimeZone('Europe/Paris'), true);

        self::assertSame(0, $count);
        self::assertSame([1 => '2026-01-15 09:00:00'], $store->dates);
    }

    public function testInterruptedRunRollsBackAndIsResumedEvenWhenSchemaIsAlreadyRecorded(): void
    {
        $store = new InMemoryDateStore([1 => '2026-01-15 10:00:00', 2 => '2026-01-16 10:00:00', 3 => '2026-01-17 10:00:00']);
        $store->failOnUpdateNumber = 2;
        $converter = new RecordModifiedDateConverter($store);

        try {
            $converter->convert(new \DateTimeZone('Europe/Paris'), true);
            self::fail('The failing update must propagate.');
        } catch (\RuntimeException $e) {
            self::assertSame('disk full', $e->getMessage());
        }

        // Nothing is half converted and the retry is still scheduled.
        self::assertSame([1 => '2026-01-15 10:00:00', 2 => '2026-01-16 10:00:00', 3 => '2026-01-17 10:00:00'], $store->dates);
        self::assertSame('pending', $store->marker);
        self::assertSame(['start', 'rollback'], $store->transactions);

        // The rerun sees the schema as already up to date (false) but the marker is pending.
        $store->failOnUpdateNumber = 0;
        $count = $converter->convert(new \DateTimeZone('Europe/Paris'), false);

        self::assertSame(3, $count);
        self::assertSame([1 => '2026-01-15 09:00:00', 2 => '2026-01-16 09:00:00', 3 => '2026-01-17 09:00:00'], $store->dates);
        self::assertSame('done', $store->marker);
    }

    public function testCurrentSchemaWithoutMarkerIsNotConverted(): void
    {
        $store = new InMemoryDateStore([1 => '2026-01-15 10:00:00']);

        $count = (new RecordModifiedDateConverter($store))->convert(new \DateTimeZone('Europe/Paris'), false);

        self::assertSame(0, $count);
        self::assertSame([1 => '2026-01-15 10:00:00'], $store->dates);
        self::assertSame('done', $store->marker);
        self::assertSame([], $store->transactions);
    }

    public function testUtcSiteTimezoneChangesNothingButCompletes(): void
    {
        $store = new InMemoryDateStore([1 => '2026-01-15 10:00:00']);

        $count = (new RecordModifiedDateConverter($store))->convert(new \DateTimeZone('UTC'), true);

        self::assertSame(0, $count);
        self::assertSame('done', $store->marker);
    }
}

final class InMemoryDateStore implements RecordModifiedDateStore
{
    public ?string $marker = null;
    /** @var list<string> */
    public array $transactions = [];
    public int $failOnUpdateNumber = 0;
    private int $updates = 0;
    /** @var array<int, string>|null */
    private ?array $snapshot = null;
    private ?string $markerSnapshot = null;

    /** @param array<int, string> $dates */
    public function __construct(public array $dates)
    {
    }

    public function getMarker(): ?string
    {
        return $this->marker;
    }

    public function setMarker(string $state): void
    {
        $this->marker = $state;
    }

    public function loadModifiedDates(): array
    {
        return $this->dates;
    }

    public function updateModified(int $id, string $modified): void
    {
        if (++$this->updates === $this->failOnUpdateNumber) {
            throw new \RuntimeException('disk full');
        }

        $this->dates[$id] = $modified;
    }

    public function transactionStart(): void
    {
        $this->transactions[] = 'start';
        $this->snapshot = $this->dates;
        $this->markerSnapshot = $this->marker;
        $this->updates = 0;
    }

    public function transactionCommit(): void
    {
        $this->transactions[] = 'commit';
    }

    public function transactionRollback(): void
    {
        $this->transactions[] = 'rollback';
        $this->dates = $this->snapshot ?? $this->dates;
        $this->marker = $this->markerSnapshot;
    }
}
