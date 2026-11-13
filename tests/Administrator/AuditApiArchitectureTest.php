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

/**
 * The audit and its repairs through the Joomla API: routes that stay private,
 * and the right each action asks for.
 */
final class AuditApiArchitectureTest extends TestCase
{
    private const ROOT = __DIR__ . '/../..';

    public function testTheAuditRoutesAreNotPublic(): void
    {
        $plugin = file_get_contents(self::ROOT . '/administrator/components/com_breezingformsng/plugins/webservices/src/Extension/Breezingformsng.php');

        self::assertIsString($plugin);
        self::assertSame(4, substr_count($plugin, "'audit."));
        self::assertMatchesRegularExpression("/\\\$audit = \\['component' => 'com_breezingformsng', 'format' => \\['application\\/json'\\]\\];/", $plugin);
        $auditPart = substr($plugin, (int) strpos($plugin, 'function addAuditRoutes'));
        self::assertStringNotContainsString("'public'", $auditPart);
    }

    public function testEveryActionAsksForItsRight(): void
    {
        $controller = file_get_contents(self::ROOT . '/api/components/com_breezingformsng/src/Controller/AuditController.php');

        self::assertIsString($controller);
        // The report: the right of the audit of the About screen.
        self::assertStringContainsString("authorise('core.manage', 'com_breezingformsng')", $controller);
        // Both deletions: the right to repair, checked before anything is deleted.
        self::assertSame(2, substr_count($controller, 'if (!$this->canRepair()) {'));
        self::assertStringContainsString('AreaAccess::canRepair(', $controller);
        // The report never leaves the filter used for an AI client.
        self::assertStringContainsString('AuditReportFilter::filter(', $controller);
    }
}
