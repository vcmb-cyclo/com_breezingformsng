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
use Vcmb\Component\BreezingformsNG\Site\Service\Rendering\ClassicQueryListCellBuilder;

final class ClassicQueryListCellBuilderTest extends TestCase
{
    public function testTreatsQueryValuesAsTextAndEscapesSelectionAttributes(): void
    {
        $column = (object) [
            'thspan' => 1, 'align' => 0, 'valign' => 0, 'wrap' => 0,
            'class2' => '', 'class3' => '', 'width' => 0, 'widthmd' => false,
        ];
        $value = '"><img src=x onerror=alert(1)>&';
        $skip = 0;
        $builder = new ClassicQueryListCellBuilder();
        $resolve = static fn (string $class): string => $class;
        $text = $builder->build($column, $value, 1, 0, 70, 'items', 0, true, $skip, $resolve);
        $selection = $builder->build($column, $value, 0, 0, 70, 'items', 1, true, $skip, $resolve);

        self::assertStringContainsString('&quot;&gt;&lt;img src=x onerror=alert(1)&gt;&amp;', $text);
        self::assertStringContainsString('value="&quot;&gt;&lt;img src=x onerror=alert(1)&gt;&amp;"', $selection);
        self::assertStringNotContainsString('<img', $text . $selection);
    }

    public function testBuildsSelectableCellWithAttributesAndWidth(): void
    {
        $column = (object) [
            'thspan' => 2,
            'align' => 2,
            'valign' => 1,
            'wrap' => 1,
            'class2' => 'odd',
            'class3' => 'even',
            'width' => 30,
            'widthmd' => false,
        ];
        $skip = 0;
        $html = (new ClassicQueryListCellBuilder())->build($column, '42', 0, 3, 70, 'items', 1, true, $skip, static fn (string $class): string => 'resolved-' . $class);

        self::assertStringContainsString('valign="top"', $html);
        self::assertStringContainsString('nowrap="nowrap"', $html);
        self::assertStringContainsString('class="resolved-odd"', $html);
        self::assertStringContainsString('style="text-align:center;width:30px;"', $html);
        self::assertStringContainsString('type="checkbox"', $html);
        self::assertStringContainsString('ff_cb70_3', $html);
    }

    public function testBuildsRadioCellAndConsumesMergedColumnSkip(): void
    {
        $column = (object) ['thspan' => 3, 'align' => 0, 'valign' => 0, 'wrap' => 0, 'class2' => '', 'class3' => '', 'width' => 0, 'widthmd' => false];
        $skip = 0;
        $builder = new ClassicQueryListCellBuilder();

        self::assertStringContainsString('type="radio"', $builder->build($column, 'x', 0, 0, 71, 'items', 2, false, $skip, static fn (string $class): string => $class));
        self::assertSame(1, $skip);
        self::assertNotSame('', $builder->build($column, 'y', 1, 1, 71, 'items', 0, false, $skip, static fn (string $class): string => $class));
        self::assertSame(0, $skip);
    }

    public function testNormalizesDynamicColumnSpanBeforeUpdatingSkip(): void
    {
        $column = (object) [
            'thspan' => '3',
            'align' => 0,
            'valign' => 0,
            'wrap' => 0,
            'class2' => '',
            'class3' => '',
            'width' => 0,
            'widthmd' => false,
        ];
        $skip = 0;

        (new ClassicQueryListCellBuilder())->build(
            $column,
            'value',
            0,
            0,
            72,
            'items',
            0,
            true,
            $skip,
            static fn (string $class): string => $class
        );

        self::assertSame(1, $skip);
    }
}
