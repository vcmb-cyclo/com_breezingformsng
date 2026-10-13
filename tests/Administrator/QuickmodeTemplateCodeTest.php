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

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator;

use PHPUnit\Framework\TestCase;

final class QuickmodeTemplateCodeTest extends TestCase
{
    public function testTemplateCodeIsCastBeforeBase64Decoding(): void
    {
        $model = file_get_contents(__DIR__ . '/../../administrator/components/com_breezingformsng/src/Model/QuickmodeModel.php');

        self::assertIsString($model);
        self::assertStringContainsString("base64_decode((string) (\$list[0]->template_code ?? ''))", $model);
    }
}
