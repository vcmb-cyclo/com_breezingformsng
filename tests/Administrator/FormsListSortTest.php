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
    public function testFormsListSortsThroughTheNativeSearchTools(): void
    {
        $admin = __DIR__ . '/../../administrator/components/com_breezingformsng';
        $view = file_get_contents($admin . '/src/View/Forms/HtmlView.php');
        $template = file_get_contents($admin . '/tmpl/forms/default.php');

        self::assertIsString($view);
        self::assertIsString($template);
        self::assertStringNotContainsString('admin-sort', $view);
        self::assertStringContainsString("LayoutHelper::render('joomla.searchtools.default', ['view' => \$this])", $template);
        self::assertStringContainsString("HTMLHelper::_('searchtools.sort', 'COM_BREEZINGFORMSNG_FORMS_TITLE', 'title'", $template);
        self::assertStringNotContainsString('filter_order', $template);
    }
}
