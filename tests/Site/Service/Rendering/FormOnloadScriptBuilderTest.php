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
use Vcmb\Component\BreezingformsNG\Site\Service\Rendering\FormOnloadScriptBuilder;

final class FormOnloadScriptBuilderTest extends TestCase
{
    public function testInitialBuildsInitializationAndOptionalPresentationHooks(): void
    {
        self::assertSame(
            "onload = function()\n{\n"
            . "    ff_initialize('formentry');\n"
            . "    ff_initialize('pageentry');\n"
            . "    ff_resizepage(2, 480);\n"
            . "    ff_showgrid();\n"
            . "    if (ff_processor && ff_processor.traceBuffer) ff_traceWindow();\n"
            . "} // onload",
            (new FormOnloadScriptBuilder())->initial(2, 480, true, "\n")
        );
    }

    public function testInitialOmitsDisabledPresentationHooks(): void
    {
        $script = (new FormOnloadScriptBuilder())->initial(0, 0, false, "\n");

        self::assertStringNotContainsString('ff_resizepage', $script);
        self::assertStringNotContainsString('ff_showgrid', $script);
        self::assertStringContainsString("ff_initialize('pageentry');", $script);
    }

    public function testSubmittedBuildsCallbackWithEscapedMessageAndHooks(): void
    {
        self::assertSame(
            "onload = function()\n{\n"
            . "    ff_resizepage(1, 360);\n"
            . "    ff_showgrid();\n"
            . "    ff_contact_submitted(5,\"\\u003Csaved\\u003E\");\n"
            . '} // onload',
            (new FormOnloadScriptBuilder())->submitted(
                'ff_contact_submitted',
                1,
                360,
                true,
                5,
                '<saved>',
                "\n"
            )
        );
    }

    public function testSubmittedReturnsNullWithoutCallbackOrHooks(): void
    {
        self::assertNull((new FormOnloadScriptBuilder())->submitted('', 0, 0, false, 1, '', "\n"));
    }
}
