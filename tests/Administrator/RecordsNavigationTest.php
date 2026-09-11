<?php

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator;

require_once __DIR__ . '/../Support/joomla-base-database-model-stub.php';

use Joomla\Database\DatabaseInterface;
use Joomla\Database\QueryInterface;
use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Tests\Administrator\Support\RecordsNavigationDatabaseDouble;
use Vcmb\Component\BreezingformsNG\Tests\Administrator\Support\RecordsNavigationModelDouble;

if (!interface_exists(DatabaseInterface::class)) {
    eval('namespace Joomla\\Database; interface DatabaseInterface {}');
}

if (!interface_exists(QueryInterface::class)) {
    eval('namespace Joomla\\Database; interface QueryInterface {}');
}

if (!class_exists('Joomla\\Database\\ParameterType')) {
    eval(
        'namespace Joomla\\Database; final class ParameterType '
        . '{ public const INTEGER = 1; public const STRING = 2; }'
    );
}

final class RecordsNavigationTest extends TestCase
{
    public function testNextRecordUsesTheCurrentFormSearchAndSortState(): void
    {
        $database = new RecordsNavigationDatabaseDouble([50, 30, 20]);

        $next = $this->model($database)->getAdjacentRecordId(
            30,
            7,
            'alice',
            'records.submitted',
            'desc',
            'next'
        );

        self::assertSame(20, $next);
        self::assertSame('records.submitted DESC, records.id DESC', $database->query->order);
        self::assertSame(
            [
                'records.form = :formSelection',
                'records.id = :searchExact',
                'records.ip LIKE :searchLike1',
                'records.username LIKE :searchLike2',
                'records.user_full_name LIKE :searchLike3',
                'forms.title LIKE :searchLike4',
                'forms.name LIKE :searchLike5',
                'records.paypal_tx_id LIKE :searchLike6',
            ],
            $database->query->where
        );
        self::assertSame(
            [
                [':formSelection', 7, 1],
                [':searchExact', 'alice', 2],
                [':searchLike1', '%alice%', 2],
                [':searchLike2', '%alice%', 2],
                [':searchLike3', '%alice%', 2],
                [':searchLike4', '%alice%', 2],
                [':searchLike5', '%alice%', 2],
                [':searchLike6', '%alice%', 2],
            ],
            $database->query->bindings
        );
        self::assertSame(['AND', 'AND', 'OR'], $database->query->whereGlue);
    }

    public function testPreviousRecordFollowsAscendingListOrderAcrossForms(): void
    {
        $database = new RecordsNavigationDatabaseDouble([20, 30, 50]);

        self::assertSame(
            20,
            $this->model($database)->getAdjacentRecordId(
                30,
                0,
                '',
                'records.id',
                'asc',
                'prev'
            )
        );
        self::assertSame('records.id ASC', $database->query->order);
        self::assertSame([], $database->query->where);
    }

    public function testReturnsNoAdjacentRecordWhenCurrentRecordIsOutsideTheFilteredList(): void
    {
        $database = new RecordsNavigationDatabaseDouble([50, 20]);

        self::assertNull(
            $this->model($database)->getAdjacentRecordId(
                30,
                7,
                'alice',
                'records.submitted',
                'desc',
                'next'
            )
        );
    }

    public function testRecordLinksCarryTheListStateIntoTheEditView(): void
    {
        $listTemplate = file_get_contents(
            __DIR__ . '/../../administrator/components/com_breezingformsng/tmpl/records/default.php'
        );
        $editTemplate = file_get_contents(
            __DIR__ . '/../../administrator/components/com_breezingformsng/tmpl/records/edit.php'
        );

        self::assertIsString($listTemplate);
        self::assertIsString($editTemplate);
        self::assertStringContainsString('http_build_query($query, \'\', \'&\', PHP_QUERY_RFC3986)', $listTemplate);
        self::assertStringContainsString('$editUrl($recId)', $listTemplate);
        self::assertStringContainsString('name="searchterm"', $editTemplate);
        self::assertStringContainsString('name="filter_order"', $editTemplate);
        self::assertStringContainsString('name="filter_order_Dir"', $editTemplate);
        self::assertStringContainsString('name="limitstart"', $editTemplate);
        self::assertStringContainsString('$recordUrl($this->prevRecordId)', $editTemplate);
        self::assertStringContainsString('$recordUrl($this->nextRecordId)', $editTemplate);
        self::assertStringContainsString('$record->browser', $editTemplate);
        self::assertStringContainsString('$record->paypal_testaccount', $editTemplate);
        self::assertStringContainsString('$record->paypal_download_tries', $editTemplate);
        self::assertStringContainsString('$record->opted', $editTemplate);
    }

    private function model(RecordsNavigationDatabaseDouble $database): RecordsNavigationModelDouble
    {
        return new RecordsNavigationModelDouble($database);
    }
}
