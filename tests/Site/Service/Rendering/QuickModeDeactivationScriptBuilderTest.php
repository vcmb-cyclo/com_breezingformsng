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
use Vcmb\Component\BreezingformsNG\Site\Service\Rendering\QuickMode\QuickModeDeactivationScriptBuilder;

final class QuickModeDeactivationScriptBuilderTest extends TestCase
{
    public function testBuildsHistoricalDeactivatedSectionScript(): void
    {
        self::assertSame(
            "<script type=\"text/javascript\">bfRegisterDeactivatedSection(\"billing\");</script>\n",
            (new QuickModeDeactivationScriptBuilder())->section('billing')
        );
    }

    public function testEscapesSectionNameAsJsonString(): void
    {
        $script = (new QuickModeDeactivationScriptBuilder())->section('billing"\nsection');

        self::assertStringContainsString('billing\\"', $script);
        self::assertStringContainsString('section', $script);
    }

    public function testBuildsHistoricalDeactivatedFieldScript(): void
    {
        self::assertSame(
            "<script type=\"text/javascript\">bfRegisterDeactivatedField(\"email\");</script>\n",
            (new QuickModeDeactivationScriptBuilder())->field('email')
        );
    }
}
