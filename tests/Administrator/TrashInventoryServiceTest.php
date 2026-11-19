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

    public function testTheTrashScreenHasOneTablePerTypeWithTheNamesAndItsOwnEmptyButton(): void
    {
        $template = (string) file_get_contents(self::BASE . 'tmpl/about/trash.php');
        $tools = (string) file_get_contents(self::BASE . 'tmpl/packages/default.php');

        self::assertStringContainsString('foreach ($this->trashedItems as $type)', $template);
        self::assertStringContainsString("id=\"bf-trash-<?php echo \$type['view']; ?>\"", $template);
        self::assertStringContainsString("htmlspecialchars(\$item['title'] !== ''", $template);
        self::assertStringContainsString("Joomla.submitform('about.emptyItemsTrash')", $template);
        self::assertStringContainsString("filter[published]=-2", $template);
        // The number of trashed records is a link too.
        self::assertStringContainsString('class="badge bg-danger text-decoration-none"', $template);
        self::assertStringContainsString("'badge' => \$view->trashedTotal", $tools);
    }

    public function testEmptyingATypeOnlyTouchesItsTrashedItemsThroughTheirModels(): void
    {
        $controller = (string) file_get_contents(self::BASE . 'src/Controller/AboutController.php');
        $start = (int) strpos($controller, 'public function emptyItemsTrash');
        $action = substr($controller, $start, (int) strpos($controller, 'public function deleteOrphanElements') - $start);

        self::assertStringContainsString('$this->checkToken();', $action);
        self::assertStringContainsString("in_array(\$type, ['forms', 'scripts', 'pieces', 'integrator'], true)", $action);
        self::assertStringContainsString('getTrashedIds($type)', $action);
        self::assertStringContainsString("'core.delete'", $action);
        self::assertStringContainsString('AreaAccess::assert($user, AreaAccess::CODE)', $action);
        self::assertStringContainsString('->deleteRules($ids)', $action);
        self::assertStringContainsString('$model->delete($pks)', $action);
    }
}
