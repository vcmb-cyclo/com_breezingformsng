<?php

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator;

require_once __DIR__ . '/../Support/joomla-list-model-stub.php';

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

        $next = $this->model($database, [
            'filter.form' => '7',
            'filter.search' => ' alice ',
            'list.ordering' => 'records.submitted',
            'list.direction' => 'desc',
        ])->getAdjacentRecordId(30, 'next');

        self::assertSame(20, $next);
        self::assertSame('records.submitted DESC, records.id DESC', $database->query->order);
        self::assertSame(
            [
                'records.form = :formId',
                'records.state IN (1)',
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
                [':formId', 7, 1],
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
        self::assertSame(['AND', 'AND', 'AND', 'OR'], $database->query->whereGlue);
    }

    public function testPreviousRecordFollowsAscendingListOrderAcrossForms(): void
    {
        $database = new RecordsNavigationDatabaseDouble([20, 30, 50]);

        self::assertSame(
            20,
            $this->model($database, ['list.ordering' => 'records.id', 'list.direction' => 'ASC'])
                ->getAdjacentRecordId(30, 'prev')
        );
        self::assertSame('records.id ASC', $database->query->order);
        self::assertSame(['records.state IN (1)'], $database->query->where);
    }

    public function testNavigationFollowsTheStateFilterOfTheList(): void
    {
        $trash = new RecordsNavigationDatabaseDouble([20, 30]);
        $this->model($trash, ['filter.state' => '-2'])->getAdjacentRecordId(30, 'prev');
        self::assertSame(['records.state IN (-2)'], $trash->query->where);

        $all = new RecordsNavigationDatabaseDouble([20, 30]);
        $this->model($all, ['filter.state' => '*'])->getAdjacentRecordId(30, 'prev');
        self::assertSame([], $all->query->where);
    }

    public function testAnUnknownSortColumnFallsBackToTheSubmissionDate(): void
    {
        $database = new RecordsNavigationDatabaseDouble([20, 30]);
        $this->model($database, ['list.ordering' => 'records.id; DROP TABLE x', 'list.direction' => 'up'])
            ->getAdjacentRecordId(30, 'prev');

        self::assertSame('records.submitted DESC, records.id DESC', $database->query->order);
    }

    public function testReturnsNoAdjacentRecordWhenCurrentRecordIsOutsideTheFilteredList(): void
    {
        $database = new RecordsNavigationDatabaseDouble([50, 20]);

        self::assertNull(
            $this->model($database, ['filter.form' => 7, 'filter.search' => 'alice'])->getAdjacentRecordId(30, 'next')
        );
    }

    public function testTheListStateStaysInTheSessionNotInTheLinks(): void
    {
        $admin = __DIR__ . '/../../administrator/components/com_breezingformsng';
        $listTemplate = file_get_contents($admin . '/tmpl/records/default.php');
        $editTemplate = file_get_contents($admin . '/tmpl/records/edit.php');

        self::assertIsString($listTemplate);
        self::assertIsString($editTemplate);
        self::assertStringContainsString('layout=edit&amp;record_id=<?= $recId; ?>', $listTemplate);
        self::assertStringNotContainsString('filter_order', $listTemplate . $editTemplate);
        self::assertStringNotContainsString('form_selection', $listTemplate . $editTemplate);
        self::assertStringContainsString('$recordUrl($this->prevRecordId)', $editTemplate);
        self::assertStringContainsString('$recordUrl($this->nextRecordId)', $editTemplate);
        self::assertStringContainsString('name="cid[]"', $editTemplate);
        self::assertStringContainsString('$record->browser', $editTemplate);
        self::assertStringContainsString('$record->paypal_testaccount', $editTemplate);
        self::assertStringContainsString('$record->paypal_download_tries', $editTemplate);
        self::assertStringContainsString('$record->opted', $editTemplate);
    }

    public function testTheListUsesTheNativeSearchToolsAndPagination(): void
    {
        $admin = __DIR__ . '/../../administrator/components/com_breezingformsng';
        $listTemplate = file_get_contents($admin . '/tmpl/records/default.php');
        $filterForm = simplexml_load_file($admin . '/forms/filter_records.xml');

        self::assertIsString($listTemplate);
        self::assertNotFalse($filterForm);
        self::assertStringContainsString('LayoutHelper::render(\'joomla.searchtools.default\', [\'view\' => $this])', $listTemplate);
        self::assertStringContainsString('$this->pagination->getListFooter()', $listTemplate);
        self::assertSame(
            ['search', 'form', 'state'],
            array_map('strval', $filterForm->xpath('/form/fields[@name="filter"]/field/@name'))
        );
        self::assertSame('*,1,2,-2', (string) $filterForm->xpath('//field[@name="state"]/@optionsFilter')[0]);
    }

    /** @param array<string, mixed> $state */
    private function model(RecordsNavigationDatabaseDouble $database, array $state = []): RecordsNavigationModelDouble
    {
        $model = new RecordsNavigationModelDouble($database);

        foreach ($state as $key => $value) {
            $model->setState($key, $value);
        }

        return $model;
    }
}
