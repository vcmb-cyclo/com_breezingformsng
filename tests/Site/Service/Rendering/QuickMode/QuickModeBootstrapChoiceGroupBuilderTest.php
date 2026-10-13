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
use Vcmb\Component\BreezingformsNG\Site\Service\Rendering\QuickMode\QuickModeBootstrapChoiceGroupBuilder;
use Vcmb\Component\BreezingformsNG\Site\Service\Rendering\QuickMode\QuickModeGroupOptionBuilder;

final class QuickModeBootstrapChoiceGroupBuilderTest extends TestCase
{
    public function testBuildsWrappedRadioGroupWithTranslatedOptions(): void
    {
        $html = $this->builder()->build(
            'radio',
            [
                'bfName' => 'size',
                'dbId' => 12,
                'wrap' => true,
                'group' => '1;Small;S',
                'group_translation_en-GB' => '1;Petit;S',
            ],
            '_en-GB',
            '<label>Size</label>',
            '',
            '',
            '',
            'bfRadioGroupWrap'
        );

        self::assertStringContainsString('class="bfRadioGroupWrap"', $html);
        self::assertStringContainsString('>Petit</label>', $html);
        self::assertStringContainsString('checked="checked"', $html);
        self::assertStringContainsString('class="radio"', $html);
    }

    public function testBuildsUnwrappedReadonlyCheckboxesAndSkipsMalformedRows(): void
    {
        $html = $this->builder()->build(
            'checkbox',
            [
                'bfName' => 'features',
                'dbId' => 13,
                'wrap' => false,
                'group' => "1;Enabled;yes\nbad\n0;Disabled;no",
            ],
            'en-GB',
            '',
            '',
            '',
            ' disabled="disabled" '
        );

        self::assertStringContainsString('class="form-check inline"', $html);
        self::assertSame(2, substr_count($html, 'type="checkbox"'));
        self::assertSame(2, substr_count($html, 'disabled="disabled"'));
        self::assertStringNotContainsString('bad', $html);
        self::assertStringContainsString('class="checkbox"', $html);
    }

    public function testReturnsEmptyMarkupForAnEmptyGroup(): void
    {
        self::assertSame(
            '',
            $this->builder()->build('radio', ['group' => ''], 'en-GB', '', '', '', '')
        );
    }

    private function builder(): QuickModeBootstrapChoiceGroupBuilder
    {
        return new QuickModeBootstrapChoiceGroupBuilder(
            new QuickModeGroupOptionBuilder(),
            static fn(string $class): string => $class
        );
    }
}
