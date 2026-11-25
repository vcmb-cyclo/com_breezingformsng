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

namespace Vcmb\Component\BreezingformsNG\Tests\Site\Service\Rendering\QuickMode;

use PHPUnit\Framework\TestCase;

final class ClassicRendererHintLineTest extends TestCase
{
    public function testTheHintIsAlsoWrittenAsAPlainLineThatOnlyTheThemesThatWantItShow(): void
    {
        $root = __DIR__ . '/../../../../..';
        $renderer = file_get_contents($root . '/components/com_breezingformsng/src/Service/Rendering/QuickMode/ClassicRenderer.php');
        $system = file_get_contents($root . '/components/com_breezingformsng/themes/quickmode/system.css');
        $theme = file_get_contents($root . '/media/breezingforms/themes/vcmb_j6/theme.css');

        self::assertIsString($renderer);
        self::assertIsString($system);
        self::assertIsString($theme);
        // Plain text, escaped: the tooltip keeps the markup of the hint, the line never does.
        self::assertStringContainsString('<small class="bfHint" id="bfHint', $renderer);
        self::assertStringContainsString('htmlspecialchars($hintLine, ENT_QUOTES, \'UTF-8\')', $renderer);
        self::assertStringContainsString('strip_tags($this->quickModeHintContentResolver()->resolve(', $renderer);
        // Hidden by default so that the other themes do not change; the J6 theme shows it instead of the icon.
        self::assertMatchesRegularExpression('/\.bfQuickMode \.bfHint \{\s*display: none;/', $system);
        self::assertMatchesRegularExpression('/\.bfQuickMode \.bfHint \{\s*display: block;/', $theme);
        self::assertMatchesRegularExpression('/\.bfQuickMode \.editlinktip \{ display: none; \}/', $theme);
    }
}
