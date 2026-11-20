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

use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Administrator\Service\PackageListRows;

final class PackageListRowsTest extends TestCase
{
    /** @return array<string, list<array<string, mixed>>> */
    private function choices(): array
    {
        return [
            'forms' => [
                ['id' => 1, 'title' => 'A', 'package' => 'alpha', 'rules' => 2],
                ['id' => 2, 'title' => 'B', 'package' => 'beta', 'rules' => 0],
                ['id' => 3, 'title' => 'C', 'package' => '', 'rules' => 0],
            ],
            'scripts' => [['id' => 7, 'title' => 'S', 'package' => '']],
            'pieces' => [],
            'menus' => [['id' => 9, 'title' => 'M', 'package' => 'alpha']],
        ];
    }

    public function testUpdateListsTheMembersFirstThenTheFreeItemsOnly(): void
    {
        $rows = PackageListRows::forUpdate($this->choices(), 'alpha');

        self::assertSame([1, 3], array_column($rows['forms'], 'id'));
        self::assertTrue($rows['forms'][0]['saved']);
        self::assertTrue($rows['forms'][0]['checked']);
        self::assertFalse($rows['forms'][1]['saved']);
        self::assertFalse($rows['forms'][1]['checked']);
        self::assertSame(2, $rows['forms'][0]['rules']);
        self::assertSame('index.php?option=com_breezingformsng&task=quickmode.display&form=3', $rows['forms'][1]['url']);
        self::assertSame([], $rows['pieces']);
        self::assertSame([9], array_column($rows['menus'], 'id'));
    }

    public function testANewPackageHasNoMembersAndOffersTheFreeItems(): void
    {
        $rows = PackageListRows::forUpdate($this->choices(), '');

        self::assertSame([3], array_column($rows['forms'], 'id'));
        self::assertFalse($rows['forms'][0]['saved']);
    }

    public function testTheDraftOfARefusedSaveOverridesTheTickedState(): void
    {
        $rows = PackageListRows::forUpdate($this->choices(), 'alpha', ['forms' => [3]]);

        self::assertTrue($rows['forms'][0]['saved']);
        self::assertFalse($rows['forms'][0]['checked']);
        self::assertTrue($rows['forms'][1]['checked']);
        // A kind absent from the draft keeps the saved state.
        self::assertTrue($rows['menus'][0]['checked']);
    }

    public function testExportListsEveryItemUntickedWithItsPackage(): void
    {
        $rows = PackageListRows::forExport($this->choices());

        self::assertSame([1, 2, 3], array_column($rows['forms'], 'id'));
        self::assertSame(['alpha', 'beta', ''], array_column($rows['forms'], 'package'));
        self::assertSame(
            [
                'index.php?option=com_breezingformsng&task=quickmode.display&form=1&pkg=alpha',
                'index.php?option=com_breezingformsng&task=quickmode.display&form=2&pkg=beta',
                'index.php?option=com_breezingformsng&task=quickmode.display&form=3',
            ],
            array_column($rows['forms'], 'url')
        );
        foreach ($rows['forms'] as $row) {
            self::assertFalse($row['saved']);
            self::assertFalse($row['checked']);
        }
    }

    public function testEachItemLinksToItsOwnEditor(): void
    {
        self::assertSame(
            'index.php?option=com_breezingformsng&task=quickmode.display&form=1&pkg=alpha',
            PackageListRows::editUrl('forms', 1, 'alpha')
        );
        self::assertSame(
            'index.php?option=com_breezingformsng&task=quickmode.display&form=3',
            PackageListRows::editUrl('forms', 3, '')
        );
        self::assertSame('index.php?option=com_breezingformsng&task=script.edit&id=7', PackageListRows::editUrl('scripts', 7, ''));
        self::assertSame('index.php?option=com_breezingformsng&task=piece.edit&id=4', PackageListRows::editUrl('pieces', 4, ''));
        self::assertSame('index.php?option=com_breezingformsng&task=menu.edit&id=9', PackageListRows::editUrl('menus', 9, 'alpha'));
    }
}
