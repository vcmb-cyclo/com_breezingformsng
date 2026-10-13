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

namespace Vcmb\Component\BreezingformsNG\Tests\Site\Service;

use PHPUnit\Framework\TestCase;

final class FormRendererArchitectureTest extends TestCase
{
    public function testApplicationContextVariableIsInitializedBeforeItsBranches(): void
    {
        $source = file_get_contents(
            __DIR__ . '/../../../components/com_breezingformsng/src/Service/FormRenderer.php'
        );

        self::assertIsString($source);
        self::assertStringContainsString(
            '$ff_applic = (string) ($context[\'ff_applic\'] ?? \'\');',
            $source
        );
        self::assertStringNotContainsString('isset($ff_applic)', $source);
    }
}
