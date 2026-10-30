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

final class QuickmodeOptionsTabVisibilityTest extends TestCase
{
    public function testOnlyTheFormRootShowsTheOptionsTab(): void
    {
        $script = file_get_contents(__DIR__ . '/../../media/com_breezingformsng/js/admin/quickmode-app.js');

        self::assertIsString($script);
        self::assertStringContainsString(
            "appScope.selectedTreeElement = node;\n"
            . "                        appScope.toggleOptionsTab(appScope.getNodeClass(node) === 'bfQuickModeRootClass');",
            $script
        );
        self::assertStringContainsString("tab.parentElement.classList.toggle('d-none', !visible);", $script);
    }
}
