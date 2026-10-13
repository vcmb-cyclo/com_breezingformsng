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
use Vcmb\Component\BreezingformsNG\Site\Service\Rendering\QuickMode\QuickModeHiddenFieldBuilder;

final class QuickModeHiddenFieldBuilderTest extends TestCase
{
    public function testBuildsHistoricalHiddenFieldMarkup(): void
    {
        self::assertSame(
            '<input class="ff_elem" type="hidden" name="ff_nm_source[]" value="newsletter-2026" '
                . "id=\"ff_elem45\"/>\n",
            QuickModeHiddenFieldBuilder::build([
                'bfName' => 'source',
                'value' => ' newsletter-2026 ',
                'dbId' => 45,
            ])
        );
    }

    public function testEscapesDynamicAttributes(): void
    {
        $markup = QuickModeHiddenFieldBuilder::build([
            'bfName' => 'source"><script>alert(1)</script>',
            'value' => 'safe',
            'dbId' => '45"><script>alert(1)</script>',
        ]);

        self::assertStringContainsString(
            'name="ff_nm_source&quot;&gt;&lt;script&gt;alert(1)&lt;/script&gt;[]"',
            $markup
        );
        self::assertStringContainsString('id="ff_elem45&quot;&gt;&lt;script&gt;alert(1)&lt;/script&gt;"', $markup);
        self::assertStringNotContainsString('<script>', $markup);
    }
}
