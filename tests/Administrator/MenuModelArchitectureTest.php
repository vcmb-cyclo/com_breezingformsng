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

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator;

use PHPUnit\Framework\TestCase;

final class MenuModelArchitectureTest extends TestCase
{
    public function testMissingMenuUsesTheNativeNotFoundMessage(): void
    {
        $model = file_get_contents(__DIR__ . '/../../administrator/components/com_breezingformsng/src/Model/MenuModel.php');
        $view = file_get_contents(__DIR__ . '/../../administrator/components/com_breezingformsng/src/View/Menu/HtmlView.php');

        self::assertIsString($model);
        self::assertIsString($view);
        self::assertStringContainsString("\$this->setError(\$table->getError() ?: Text::_('JLIB_APPLICATION_ERROR_NOT_EXIST'));", $model);
        self::assertStringContainsString("throw new \\Exception(Text::_('JLIB_APPLICATION_ERROR_NOT_EXIST'), 404);", $view);
    }
}
