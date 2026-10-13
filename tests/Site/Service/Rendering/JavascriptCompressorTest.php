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
use Vcmb\Component\BreezingformsNG\Site\Service\Rendering\JavascriptCompressor;

final class JavascriptCompressorTest extends TestCase
{
    public function testRemovesCommentsAndPreservesCommentMarkersInStrings(): void
    {
        $javascript = "var url = 'https://example.test/a//b'; // removed\n"
            . "var block = \"/* kept */\"; /* removed */ var value = 2;";

        self::assertSame(
            "var url='https://example.test/a//b';var block=\"/* kept */\";var value=2;\n",
            (new JavascriptCompressor())->compress($javascript, 80, "\n")
        );
    }

    public function testPreservesEscapedQuotesAndSeparatesIdentifiers(): void
    {
        $javascript = "var text = 'a\\' b'; var answer = foo + bar;";

        self::assertSame(
            "var text='a\\' b';var answer=foo+bar;\n",
            (new JavascriptCompressor())->compress($javascript, 80, "\n")
        );
    }

    public function testUsesConfiguredLineEndingForLongExpressions(): void
    {
        $javascript = 'var values = [one, two, three, four];';

        self::assertSame(
            "var values=[one,two,three,four];\r\n",
            (new JavascriptCompressor())->compress($javascript, 15, "\r\n")
        );
    }
}
