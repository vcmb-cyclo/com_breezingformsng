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

final class FormsAdvancedEmailTabsTest extends TestCase
{
    public function testEmailOptionsAreSplitIntoAdministratorAndUserTabs(): void
    {
        $layout = file_get_contents(__DIR__ . '/../../administrator/components/com_breezingformsng/layouts/forms/advanced_options.php');

        self::assertIsString($layout);
        self::assertStringContainsString('id="<?= $tabId; ?>-email"', $layout);
        self::assertStringContainsString('data-bs-target="#pane-email-admin"', $layout);
        self::assertStringContainsString('data-bs-target="#pane-email-user"', $layout);
        self::assertStringContainsString('name="emailntf"', $layout);
        self::assertStringContainsString('name="mb_emailntf"', $layout);
    }
}
