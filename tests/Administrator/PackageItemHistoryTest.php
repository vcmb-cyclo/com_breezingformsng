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

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Administrator\Model\PieceModel;

/**
 * The version stored before the standard library replaces the code of an item.
 */
final class PackageItemHistoryTest extends TestCase
{
    private function model(): PieceModel
    {
        require_once __DIR__ . '/fixtures/package-item-history-stubs.php';
        \Fixture\History::$events = [];
        \Fixture\History::$lastVersion = 3;
        \Fixture\History::$hasVersions = true;

        return new PieceModel();
    }

    #[RunInSeparateProcess]
    public function testTheCurrentCodeIsStoredWithItsNoteAndKeptForever(): void
    {
        $model = $this->model();
        \Joomla\CMS\Component\ComponentHelper::$historyEnabled = true;

        $model->keepVersionBeforeReplacement(7, 'Before');

        self::assertSame(['store:com_breezingformsng.piece.7', 'note', 'keep_forever'], \Fixture\History::$events);
    }

    #[RunInSeparateProcess]
    public function testTheCurrentCodeIsStoredEvenWhenTheHistoryIsDisabled(): void
    {
        $model = $this->model();
        \Joomla\CMS\Component\ComponentHelper::$historyEnabled = false;

        $model->keepVersionBeforeReplacement(7, 'Before');

        self::assertSame(['store:com_breezingformsng.piece.7', 'note', 'keep_forever'], \Fixture\History::$events);
    }

    #[RunInSeparateProcess]
    public function testTheStandardVersionIsRecordedOnlyWhenTheHistoryIsEnabled(): void
    {
        $model = $this->model();
        \Joomla\CMS\Component\ComponentHelper::$historyEnabled = true;
        $model->recordVersion(7, 'Standard');
        self::assertSame(['store:com_breezingformsng.piece.7', 'note'], \Fixture\History::$events);

        \Fixture\History::$events = [];
        \Joomla\CMS\Component\ComponentHelper::$historyEnabled = false;
        $model->recordVersion(7, 'Standard');
        self::assertSame([], \Fixture\History::$events);
    }

    /** @return iterable<string, array{array<string, mixed>, bool, string}> */
    public static function namesProvider(): iterable
    {
        yield 'an unchanged legacy name' => [['id' => 7, 'name' => 'contact-us'], true, 'saved:contact-us'];
        yield 'a name changed to a valid one' => [['id' => 7, 'name' => 'contact_us'], true, 'saved:contact_us'];
        yield 'a name changed to an invalid one' => [['id' => 7, 'name' => 'contact.us'], false, 'COM_BREEZINGFORMSNG_ERROR_ENTER_NAME_CHARACTERS'];
        yield 'a name emptied' => [['id' => 7, 'name' => ''], false, 'COM_BREEZINGFORMSNG_ERROR_ENTER_NAME'];
        yield 'a new item with an invalid name' => [['id' => 0, 'name' => 'contact-us'], false, 'COM_BREEZINGFORMSNG_ERROR_ENTER_NAME_CHARACTERS'];
        yield 'a new item with a trailing newline' => [['id' => 0, 'name' => "abc\n"], false, 'COM_BREEZINGFORMSNG_ERROR_ENTER_NAME_CHARACTERS'];
        yield 'a name changed to a trailing newline' => [['id' => 7, 'name' => "contact-us\n"], false, 'COM_BREEZINGFORMSNG_ERROR_ENTER_NAME_CHARACTERS'];
        yield 'a new valid item' => [['id' => 0, 'name' => 'contact'], true, 'saved:contact'];
    }

    /**
     * @param array<string, mixed> $data
     */
    #[DataProvider('namesProvider')]
    #[RunInSeparateProcess]
    public function testTheNameRuleAppliesToNewOrChangedNamesOnly(array $data, bool $saved, string $outcome): void
    {
        $model = $this->model();
        \Fixture\History::$events = [];

        self::assertSame($saved, $model->save($data));
        if ($saved) {
            self::assertContains($outcome, \Fixture\History::$events);
        } else {
            self::assertSame($outcome, $model->getError());
            self::assertNotContains('saved:' . $data['name'], \Fixture\History::$events);
        }
    }

    #[RunInSeparateProcess]
    public function testTheCopyOfAnItemWithALegacyNameKeepsItsName(): void
    {
        $model = $this->model();
        \Fixture\History::$events = [];

        self::assertSame(1, $model->copy([7]));
        self::assertContains('saved:contact-us', \Fixture\History::$events);
        // The allowance ends with the copy: a new item with that name is refused again.
        self::assertFalse($model->save(['id' => 0, 'name' => 'contact-us']));
    }
}
