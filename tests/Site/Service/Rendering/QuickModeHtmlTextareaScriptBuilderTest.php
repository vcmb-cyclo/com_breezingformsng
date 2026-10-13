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
use Vcmb\Component\BreezingformsNG\Site\Service\Rendering\QuickMode\QuickModeHtmlTextareaScriptBuilder;

final class QuickModeHtmlTextareaScriptBuilderTest extends TestCase
{
    public function testBuildsRegistrationWithRawJavascriptExpression(): void
    {
        self::assertSame(
            '<script type="text/javascript">bfRegisterHtmlTextarea("body", function () { return editorValue(); });</script>',
            (new QuickModeHtmlTextareaScriptBuilder())->build('body', 'editorValue()')
        );
    }

    public function testSupportsEncodedEditorContentAndOptionalNewline(): void
    {
        self::assertSame(
            "<script type=\"text/javascript\">bfRegisterHtmlTextarea(\"body\", function () { return \"saved\"; });</script>\n",
            (new QuickModeHtmlTextareaScriptBuilder())->build('body', '"saved"', "\n")
        );
    }
}
