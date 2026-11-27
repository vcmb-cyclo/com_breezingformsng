<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Integration;

use Joomla\Database\DatabaseInterface;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Site\Service\Export\DatabaseTransaction;
use Vcmb\Component\BreezingformsNG\Site\Service\Scripting\Repository;

/**
 * The submission transaction and the stored code cache, on the real Joomla
 * database interface.
 */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class DatabaseTransactionTest extends TestCase
{
    protected function setUp(): void
    {
        $libraries = getenv('BFNG_JOOMLA_LIBRARIES');
        if (!$libraries || !is_file($libraries . '/vendor/autoload.php')) {
            self::markTestSkipped('Set BFNG_JOOMLA_LIBRARIES to a Joomla 6 libraries directory.');
        }
        require $libraries . '/vendor/autoload.php';
    }

    public function testASuccessfulWorkIsCommitted(): void
    {
        $database = $this->createMock(DatabaseInterface::class);
        $database->expects(self::once())->method('transactionStart')->with(true);
        $database->expects(self::once())->method('transactionCommit')->with(true);
        $database->expects(self::never())->method('transactionRollback');

        self::assertTrue((new DatabaseTransaction($database))->run(static fn (): bool => true));
    }

    public function testARefusedWorkIsRolledBack(): void
    {
        $database = $this->createMock(DatabaseInterface::class);
        $database->expects(self::never())->method('transactionCommit');
        $database->expects(self::once())->method('transactionRollback')->with(true);

        self::assertFalse((new DatabaseTransaction($database))->run(static fn (): bool => false));
    }

    public function testAFailingWorkIsRolledBackAndTheErrorKept(): void
    {
        $database = $this->createMock(DatabaseInterface::class);
        $database->expects(self::never())->method('transactionCommit');
        $database->expects(self::once())->method('transactionRollback')->with(true);

        $this->expectExceptionMessage('write failed');

        (new DatabaseTransaction($database))->run(static function (): bool {
            throw new \RuntimeException('write failed');
        });
    }

    public function testStoredCodeIsReadOncePerRequest(): void
    {
        $database = $this->createMock(DatabaseInterface::class);
        $database->method('createQuery')->willReturnCallback(
            fn () => $this->queryDouble()
        );
        $database->method('quoteName')->willReturnArgument(0);
        // One read per distinct entry, a missing one included.
        $database->expects(self::exactly(3))->method('loadAssoc')->willReturnOnConsecutiveCalls(
            ['id' => 1, 'name' => 'a', 'code' => 'script'],
            null,
            ['id' => 1, 'name' => 'a', 'code' => 'piece']
        );
        $repository = new Repository($database);

        self::assertSame('script', $repository->findPublishedScriptById(1)?->code);
        self::assertSame('script', $repository->findPublishedScriptById(1)?->code);
        self::assertNull($repository->findPublishedPieceByName('absent'));
        self::assertNull($repository->findPublishedPieceByName('absent'));
        self::assertSame('piece', $repository->findPublishedPieceById(1)?->code);
        self::assertSame('piece', $repository->findPublishedPieceById(1)?->code);
    }

    private function queryDouble(): \Joomla\Database\QueryInterface
    {
        $query = $this->createStub(\Joomla\Database\DatabaseQuery::class);
        foreach (['select', 'from', 'where', 'order', 'bind'] as $method) {
            $query->method($method)->willReturnSelf();
        }

        return $query;
    }
}
