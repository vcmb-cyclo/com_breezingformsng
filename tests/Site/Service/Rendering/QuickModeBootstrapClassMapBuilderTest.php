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
use Vcmb\Component\BreezingformsNG\Site\Service\Rendering\QuickMode\QuickModeBootstrapClassMapBuilder;

final class QuickModeBootstrapClassMapBuilderTest extends TestCase
{
    public function testProvidesTheCompleteBootstrapFiveMapping(): void
    {
        $classes = QuickModeBootstrapClassMapBuilder::build();

        self::assertCount(54, $classes);
        self::assertSame('col-md-12', $classes['span12']);
        self::assertSame('fa-solid fa-circle-question', $classes['icon-question-sign']);
        self::assertSame('', $classes['controls']);
        self::assertSame('input-group', $classes['input-append']);
        self::assertSame('form-select', $classes['form-select']);
    }
}
