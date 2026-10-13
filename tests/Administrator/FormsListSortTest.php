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

final class FormsListSortTest extends TestCase
{
    public function testFormsListRegistersItsSortScriptBeforeTheTemplateIsRendered(): void
    {
        $source = file_get_contents(
            __DIR__ . '/../../administrator/components/com_breezingformsng/src/View/Forms/HtmlView.php'
        );

        self::assertIsString($source);
        self::assertStringContainsString("registerAndUseScript(\n                'com_breezingformsng.admin-sort'", $source);
        self::assertStringContainsString("'media/com_breezingformsng/js/admin/admin-sort.js'", $source);
    }
}
