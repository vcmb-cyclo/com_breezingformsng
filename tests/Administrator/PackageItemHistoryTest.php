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
}
