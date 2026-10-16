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

final class ClassicRendererReadyScriptTest extends TestCase
{
    /**
     * Each addInlineScript() call becomes its own <script>, in the order of
     * the calls: the first one runs before the later one that defines the
     * JQuery alias, so it must use jQuery itself.
     */
    public function testTheFirstInlineScriptDoesNotUseTheJQueryAliasBeforeItIsDefined(): void
    {
        $source = file_get_contents(
            __DIR__ . '/../../../../../components/com_breezingformsng/src/Service/Rendering/QuickMode/ClassicRenderer.php'
        );

        self::assertIsString($source);

        $headers = substr($source, (int) strpos($source, 'public function headers()'));
        $aliasDefinition = strpos($headers, "'var JQuery = jQuery;'");
        $readyScript = strpos($headers, 'jQuery(document).ready(function(){');

        self::assertNotFalse($aliasDefinition);
        self::assertNotFalse($readyScript);
        self::assertLessThan($aliasDefinition, $readyScript);
        self::assertStringNotContainsString('JQuery(', substr($headers, 0, $aliasDefinition));
    }
}
