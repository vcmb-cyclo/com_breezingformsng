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

namespace Vcmb\Component\BreezingformsNG\Tests\Site\Service\Rendering\QuickMode;

use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Site\Service\Rendering\QuickMode\ClassicChoiceGroupBuilder;
use Vcmb\Component\BreezingformsNG\Site\Service\Rendering\QuickMode\QuickModeGroupOptionBuilder;

final class ClassicChoiceGroupBuilderTest extends TestCase
{
    public function testBuildsWrappedRadioGroupWithLabelsAndCheckedOption(): void
    {
        $html = (new ClassicChoiceGroupBuilder(new QuickModeGroupOptionBuilder()))->build(
            'radio',
            12,
            'size',
            "1;Small;S\n0;Large;L",
            true,
            'left',
            'tabindex="1" ',
            ' onclick="choose();" ',
            false
        );

        self::assertStringContainsString('<span class="bfElementGroup" id="bfElementGroup12">', $html);
        self::assertStringContainsString('checked="checked"', $html);
        self::assertStringContainsString('value="S"', $html);
        self::assertStringContainsString('id="bfGroupLabel12_1"', $html);
        self::assertStringContainsString('</span>', $html);
        self::assertStringContainsString('<br/>', $html);
    }

    public function testBuildsNoWrapCheckboxGroupAndSkipsMalformedRows(): void
    {
        $html = (new ClassicChoiceGroupBuilder(new QuickModeGroupOptionBuilder()))->build(
            'checkbox',
            13,
            'features',
            "1;Enabled;yes\nmalformed\n0;Disabled;no",
            false,
            'right',
            '',
            '',
            true
        );

        self::assertStringContainsString('class="bfElementGroupNoWrap"', $html);
        self::assertStringContainsString('disabled="disabled"', $html);
        self::assertSame(2, substr_count($html, 'type="checkbox"'));
        self::assertStringContainsString('>Enabled</label>', $html);
        self::assertStringContainsString('>Disabled</label>', $html);
        self::assertStringNotContainsString('malformed', $html);
        self::assertStringNotContainsString('<br/>', $html);
    }

    public function testReturnsEmptyMarkupForAnEmptyGroup(): void
    {
        self::assertSame(
            '',
            (new ClassicChoiceGroupBuilder(new QuickModeGroupOptionBuilder()))->build(
                'radio',
                14,
                'empty',
                '',
                false,
                'left',
                '',
                '',
                false
            )
        );
    }
}
