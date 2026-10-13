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
use Vcmb\Component\BreezingformsNG\Site\Service\Rendering\QuickMode\QuickModeFileInputBuilder;

final class QuickModeFileInputBuilderTest extends TestCase
{
    public function testBuildsHistoricalFileInputMarkup(): void
    {
        self::assertSame(
            "<input class=\"ff_elem\" tabindex=\"2\" type=\"file\" name=\"ff_nm_attachment[]\" id=\"ff_elem61\"/>\n",
            QuickModeFileInputBuilder::build('tabindex="2" ', 'file', 'attachment', 61)
        );
    }

    public function testEscapesTypeAndFieldName(): void
    {
        $markup = QuickModeFileInputBuilder::build(
            '',
            'file"><script>alert(1)</script>',
            'attachment"><script>alert(1)</script>',
            61
        );

        self::assertStringNotContainsString('<script>', $markup);
        self::assertStringContainsString('type="file&quot;&gt;&lt;script&gt;alert(1)&lt;/script&gt;"', $markup);
        self::assertStringContainsString(
            'name="ff_nm_attachment&quot;&gt;&lt;script&gt;alert(1)&lt;/script&gt;[]"',
            $markup
        );
    }
}
