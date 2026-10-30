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
        ])->getNavigation(30)['next'];

        self::assertSame(20, $next);
        self::assertSame('records.submitted DESC, records.id DESC', $database->query->order);
        self::assertSame(
            [
                'records.form = :formId',
                'records.state IN (1)',
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

    public function testANumberSearchesTheIdBoundAsAnIntegerOrTheTexts(): void
    {
        $database = new RecordsNavigationDatabaseDouble([50, 30, 20]);

        $this->model($database, ['filter.search' => '42'])->getNavigation(30);

        self::assertContains('records.id = :searchId', $database->query->where);
        self::assertContains([':searchId', 42, 1], $database->query->bindings);
        self::assertContains('records.ip LIKE :searchLike1', $database->query->where);
    }

    public function testAnIdPrefixSearchesTheIdOnly(): void
    {
        $database = new RecordsNavigationDatabaseDouble([50, 30, 20]);

        $this->model($database, ['filter.search' => 'ID: 42'])->getNavigation(30);

        self::assertSame(['records.state IN (1)', 'records.id = :searchId'], $database->query->where);
        self::assertSame([[':searchId', 42, 1]], $database->query->bindings);
    }

    public function testTextAfterANumberIsNotTakenForAnId(): void
    {
        $database = new RecordsNavigationDatabaseDouble([50, 30, 20]);

        $this->model($database, ['filter.search' => '5abc'])->getNavigation(30);

        self::assertNotContains('records.id = :searchId', $database->query->where);
    }

    public function testPreviousRecordFollowsAscendingListOrderAcrossForms(): void
    {
        $database = new RecordsNavigationDatabaseDouble([20, 30, 50]);

        self::assertSame(
            20,
            $this->model($database, ['list.ordering' => 'records.id', 'list.direction' => 'ASC'])
                ->getNavigation(30)['prev']
        );
        self::assertSame('records.id ASC', $database->query->order);
        self::assertSame(['records.state IN (1)'], $database->query->where);
    }

    public function testNavigationFollowsTheStateFilterOfTheList(): void
    {
        $trash = new RecordsNavigationDatabaseDouble([20, 30]);
        $this->model($trash, ['filter.state' => '-2'])->getNavigation(30)['prev'];
        self::assertSame(['records.state IN (-2)'], $trash->query->where);

        $all = new RecordsNavigationDatabaseDouble([20, 30]);
        $this->model($all, ['filter.state' => '*'])->getNavigation(30)['prev'];
        self::assertSame([], $all->query->where);
    }

    public function testAnUnknownSortColumnFallsBackToTheSubmissionDate(): void
    {
        $database = new RecordsNavigationDatabaseDouble([20, 30]);
        $this->model($database, ['list.ordering' => 'records.id; DROP TABLE x', 'list.direction' => 'up'])
            ->getNavigation(30)['prev'];

        self::assertSame('records.submitted DESC, records.id DESC', $database->query->order);
    }

    public function testReturnsNoAdjacentRecordWhenCurrentRecordIsOutsideTheFilteredList(): void
    {
        $database = new RecordsNavigationDatabaseDouble([50, 20]);

        self::assertNull(
            $this->model($database, ['filter.form' => 7, 'filter.search' => 'alice'])->getNavigation(30)['next']
        );
    }

    public function testNavigationGivesThePositionOfTheRecordInTheFilteredList(): void
    {
        $database = new RecordsNavigationDatabaseDouble([50, 30, 20]);

        self::assertSame(
            ['prev' => 50, 'next' => 20, 'position' => 2, 'total' => 3],
            $this->model($database)->getNavigation(30)
        );
        self::assertSame(
            ['prev' => null, 'next' => null, 'position' => null, 'total' => 3],
            $this->model(new RecordsNavigationDatabaseDouble([50, 30, 20]))->getNavigation(99)
        );
    }

    public function testTheListStateStaysInTheSessionNotInTheLinks(): void
    {
        $admin = __DIR__ . '/../../administrator/components/com_breezingformsng';
        $listTemplate = file_get_contents($admin . '/tmpl/records/default.php');
        $editTemplate = file_get_contents($admin . '/tmpl/record/edit.php');

        self::assertIsString($listTemplate);
        self::assertIsString($editTemplate);
        self::assertStringContainsString('task=record.edit&amp;id=<?= $recId; ?>', $listTemplate);
        self::assertStringContainsString('$this->pagination?->getPaginationPages()', $editTemplate);
        self::assertStringContainsString('$this->form->renderFieldset(\'values\')', $editTemplate);
        self::assertStringNotContainsString('filter_order', $listTemplate . $editTemplate);
        self::assertStringNotContainsString('form_selection', $listTemplate . $editTemplate);
        self::assertStringContainsString('$pageLink($pages[\'previous\']', $editTemplate);
        self::assertStringContainsString('$pageLink($pages[\'next\']', $editTemplate);
        self::assertStringContainsString('$paginationNav(\'ms-auto\')', $editTemplate);
        self::assertStringContainsString('$paginationNav(\'mt-3\')', $editTemplate);
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
        self::assertSame(
            ['', '1', '2', '-2', '*'],
            array_map('strval', $filterForm->xpath('//field[@name="state"]/option/@value'))
        );
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

    public function testViewedAndExportedColumnsStartHidden(): void
    {
        $component = __DIR__ . '/../../administrator/components/com_breezingformsng';
        $layout = (string) file_get_contents($component . '/tmpl/records/default.php');
        $view = (string) file_get_contents($component . '/src/View/Records/HtmlView.php');

        self::assertStringContainsString('data-name="bfng-records"', $layout);
        self::assertSame(4, substr_count($layout, 'class="d-none text-center"'));
        self::assertLessThan(
            strpos($view, "useScript('table.columns')"),
            strpos($view, "'com_breezingformsng.records-columns'")
        );
    }
}
