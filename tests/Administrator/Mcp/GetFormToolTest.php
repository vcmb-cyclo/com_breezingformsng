<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator\Mcp;

use Joomla\Database\DatabaseInterface;
use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Administrator\Mcp\FormCatalog;
use Vcmb\Component\BreezingformsNG\Administrator\Mcp\Tool\GetFormTool;
use Vcmb\Component\BreezingformsNG\Administrator\Mcp\Tool\ListFormsTool;
use Vcmb\Component\BreezingformsNG\Administrator\Mcp\ToolException;

require_once __DIR__ . '/McpTestDoubles.php';

final class GetFormToolTest extends TestCase
{
    private function tool(): GetFormTool
    {
        // The checks below fail before the catalog reads anything.
        return new GetFormTool(new FormCatalog($this->createStub(DatabaseInterface::class)));
    }

    public function testTheFormToolsNeedAccessToTheComponent(): void
    {
        $catalog = new FormCatalog($this->createStub(DatabaseInterface::class));

        self::assertFalse((new GetFormTool($catalog))->allows(McpTestDoubles::user([])));
        self::assertFalse((new ListFormsTool($catalog))->allows(McpTestDoubles::user([])));
        self::assertTrue((new GetFormTool($catalog))->allows(McpTestDoubles::user(['core.manage'])));
        self::assertTrue((new ListFormsTool($catalog))->readOnly());
    }

    public function testEitherTheIdOrTheNameIsGiven(): void
    {
        $this->expectException(ToolException::class);
        $this->expectExceptionMessage('Give either the id or the name of the form.');

        $this->tool()->call(['id' => 1, 'name' => 'Contact', 'include_code' => false], McpTestDoubles::user(['core.manage']));
    }

    public function testNeitherTheIdNorTheNameIsRefused(): void
    {
        $this->expectException(ToolException::class);

        $this->tool()->call(['include_code' => false], McpTestDoubles::user(['core.manage']));
    }

    public function testTheCodeNeedsThePermissionToEditCode(): void
    {
        $this->expectException(ToolException::class);
        $this->expectExceptionMessage('You are not allowed to read the code of forms');

        $this->tool()->call(['id' => 1, 'include_code' => true], McpTestDoubles::user(['core.manage']));
    }

    public function testLongTextsAreCutAndSaidToBe(): void
    {
        self::assertSame('short', FormCatalog::text('short'));
        self::assertSame('', FormCatalog::text(null));
        $cut = FormCatalog::text(str_repeat('é', FormCatalog::MAX_TEXT + 1));
        self::assertSame(str_repeat('é', FormCatalog::MAX_TEXT) . ' [truncated]', $cut);
    }
}
