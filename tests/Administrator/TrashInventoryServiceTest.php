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

/** The Tools trash screen and tile count every kind of item that can be trashed. */
final class TrashInventoryServiceTest extends TestCase
{
    private const BASE = __DIR__ . '/../../administrator/components/com_breezingformsng/';

    public function testItCoversFormsScriptsPiecesAndIntegratorRules(): void
    {
        $service = (string) file_get_contents(self::BASE . 'src/Service/TrashInventoryService.php');

        foreach (['forms', 'scripts', 'pieces', 'integrator_rules'] as $table) {
            self::assertStringContainsString("'#__facileforms_" . $table . "'", $service);
        }
        self::assertStringContainsString('TRASHED = -2', $service);
        self::assertStringContainsString(':trashed', $service);
    }

    public function testTheTrashScreenListsThemWithALinkToTheTrashedList(): void
    {
        $template = (string) file_get_contents(self::BASE . 'tmpl/about/trash.php');
        $tools = (string) file_get_contents(self::BASE . 'tmpl/packages/default.php');

        self::assertStringContainsString("filter[published]=-2", $template);
        self::assertStringContainsString('$this->trashedItems', $template);
        // The counts are links too: the number of trashed records opens that trash.
        self::assertGreaterThanOrEqual(2, substr_count($template, 'class="badge bg-danger text-decoration-none"'));
        self::assertStringContainsString("'badge' => \$this->trashedTotal", $tools);
    }
}
