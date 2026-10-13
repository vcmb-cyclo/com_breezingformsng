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

namespace Vcmb\Component\BreezingformsNG\Tests\Site\Service\Rendering;

use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Site\Service\Rendering\QueryListPageScriptBuilder;

final class QueryListPageScriptBuilderTest extends TestCase
{
    public function testAssemblesRefreshNavigationAndPaginationInHistoricalOrder(): void
    {
        $script = (new QueryListPageScriptBuilder())->build(
            ['start' => 'Start', 'previous' => 'Previous', 'next' => 'Next', 'end' => 'End'],
            true,
            2,
            480,
            true,
            "\n"
        );

        self::assertStringStartsWith("function ff_dispQueryPage(id,page)\n{\n", $script);
        self::assertStringContainsString('var forced = false;', $script);
        self::assertStringContainsString('var qrows = ff_queryRows[id];', $script);
        self::assertStringContainsString("navi += ' Start';", $script);
        self::assertStringContainsString('ff_queryCurrPage[id] = page;', $script);
        self::assertStringContainsString('ff_selectAllQueryRows(id, false);', $script);
        self::assertStringContainsString('ff_resizepage(2, 480);', $script);
        self::assertStringEndsWith("    window.scrollTo(0,0);\n} // ff_dispQueryPage", $script);
    }

    public function testOmitsOptionalPaginationHooksWhenNotConfigured(): void
    {
        $script = (new QueryListPageScriptBuilder())->build(
            ['start' => 'S', 'previous' => 'P', 'next' => 'N', 'end' => 'E'],
            false,
            0,
            0,
            false,
            "\n"
        );

        self::assertStringNotContainsString('ff_selectAllQueryRows', $script);
        self::assertStringNotContainsString('ff_resizepage', $script);
        self::assertStringNotContainsString('parent.window.scrollTo', $script);
        self::assertStringContainsString('window.scrollTo(0,0);', $script);
    }

    public function testBuildsPagedRowRefreshLogic(): void
    {
        $script = (new QueryListPageScriptBuilder())->build(
            ['start' => 'S', 'previous' => 'P', 'next' => 'N', 'end' => 'E'],
            false,
            0,
            0,
            false,
            "\n"
        );

        self::assertStringContainsString('var qrows = ff_queryRows[id];', $script);
        self::assertStringContainsString('var lastpage = 1;', $script);
        self::assertStringContainsString('row.cells[cc++].innerHTML = qrow[c];', $script);
        self::assertStringContainsString("row.style.display = '';", $script);
        self::assertStringContainsString("row.style.display = 'none';", $script);
    }

    public function testBuildsNavigationForAllPages(): void
    {
        $script = (new QueryListPageScriptBuilder())->build(
            ['start' => 'Start', 'previous' => 'Previous', 'next' => 'Next', 'end' => 'End'],
            false,
            0,
            0,
            false,
            "\n"
        );

        self::assertStringContainsString('ff_dispQueryPage(\'+id+\',1)', $script);
        self::assertStringContainsString('Start', $script);
        self::assertStringContainsString('Previous', $script);
        self::assertStringContainsString('Next', $script);
        self::assertStringContainsString('End', $script);
        self::assertStringContainsString("navi += '<\\/a>';", $script);
    }

    public function testBuildsAllOptionalPaginationStatements(): void
    {
        $script = (new QueryListPageScriptBuilder())->build(
            ['start' => 'S', 'previous' => 'P', 'next' => 'N', 'end' => 'E'],
            true,
            2,
            480,
            true,
            "\n"
        );

        self::assertStringContainsString("    if (checkbox) ff_selectAllQueryRows(id, false);\n", $script);
        self::assertStringContainsString("    ff_resizepage(2, 480);\n", $script);
        self::assertStringContainsString("    parent.window.scrollTo(0,0);\n", $script);
    }
}
