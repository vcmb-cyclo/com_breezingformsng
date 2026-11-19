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

namespace Vcmb\Component\BreezingformsNG\Tests\Site\Service\Support;

use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Site\Service\Support\PageHeading;

final class PageHeadingTest extends TestCase
{
    public function testEscapesAFormTitleContainingMarkup(): void
    {
        $html = PageHeading::html('', '', '<script>alert("x")</script> & \'q\'');

        self::assertSame(
            '<h1>&lt;script&gt;alert(&quot;x&quot;)&lt;/script&gt; &amp; &#039;q&#039;</h1>',
            $html
        );
    }

    public function testEscapesMenuTitleAndPageHeadingAndKeepsPrecedence(): void
    {
        self::assertSame('<h1>&lt;b&gt;Heading&lt;/b&gt;</h1>', PageHeading::html('Menu', '<b>Heading</b>', 'Form'));
        self::assertSame('<h1>&lt;i&gt;Menu&lt;/i&gt;</h1>', PageHeading::html('<i>Menu</i>', '', 'Form'));
        self::assertSame('<h1>Form</h1>', PageHeading::html('', 'Ignored', 'Form'));
    }

    public function testPlainTitlesAreNotDoubleEscapedAndInvalidUtf8IsSubstituted(): void
    {
        self::assertSame('<h1>Contact us</h1>', PageHeading::html('', '', 'Contact us'));
        self::assertStringNotContainsString("\xC3\x28", PageHeading::html('', '', "bad\xC3\x28"));
    }
}
