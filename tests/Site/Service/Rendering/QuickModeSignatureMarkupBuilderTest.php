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
use Vcmb\Component\BreezingformsNG\Site\Service\Rendering\QuickMode\QuickModeSignatureMarkupBuilder;

final class QuickModeSignatureMarkupBuilderTest extends TestCase
{
    public function testBuildsClassicSignatureCoreWithFieldMarker(): void
    {
        self::assertSame(
            '<div class="bfSignature" id="bfSignature57">'
                . '<div class="bfSignatureCanvasBorder"><canvas></canvas></div>'
                . "\n"
                . '<button class="btn btn-primary" '
                . 'onclick="bfSignatureReset(57);" class="bfSignatureResetButton button">'
                . "<span>Réinitialiser</span></button>\n"
                . "<span class='bfSignaturesignature'></span></div>",
            QuickModeSignatureMarkupBuilder::build(
                57,
                'signature',
                'class="btn btn-primary" onclick="bfSignatureReset(57);" class="bfSignatureResetButton button"',
                'Réinitialiser',
                true
            )
        );
    }

    public function testBuildsThemeSignatureCoreWithoutFieldMarker(): void
    {
        $markup = QuickModeSignatureMarkupBuilder::build(
            58,
            'signature',
            'onclick="bfSignatureReset(58);" class="bfSignatureResetButton button btn btn-primary"',
            'Reset',
            false
        );

        self::assertStringContainsString('<canvas></canvas>', $markup);
        self::assertStringContainsString('class="bfSignatureResetButton button btn btn-primary"', $markup);
        self::assertStringNotContainsString('bfSignaturesignature', $markup);
    }

    public function testEscapesFieldMarkerClass(): void
    {
        $markup = QuickModeSignatureMarkupBuilder::build(
            57,
            'signature"><script>alert(1)</script>',
            'class="button"',
            'Reset',
            true
        );

        self::assertStringNotContainsString('<script>', $markup);
        self::assertStringContainsString(
            'bfSignaturesignature&quot;&gt;&lt;script&gt;alert(1)&lt;/script&gt;',
            $markup
        );
    }
}
