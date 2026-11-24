<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator\Mcp;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Administrator\Mcp\DestructiveMcpTool;
use Vcmb\Component\BreezingformsNG\Administrator\Mcp\McpServer;
use Vcmb\Component\BreezingformsNG\Administrator\Mcp\McpTool;
use Vcmb\Component\BreezingformsNG\Administrator\Mcp\Tool\CreateFormTool;
use Vcmb\Component\BreezingformsNG\Administrator\Mcp\Tool\ManageFormFieldsTool;
use Vcmb\Component\BreezingformsNG\Administrator\Mcp\Tool\RestoreFormTool;
use Vcmb\Component\BreezingformsNG\Administrator\Mcp\Tool\TrashFormTool;
use Vcmb\Component\BreezingformsNG\Administrator\Mcp\Tool\UpdateFormTool;
use Vcmb\Component\BreezingformsNG\Administrator\Service\FormCodeGuard;

require_once __DIR__ . '/McpWriterDoubles.php';

/**
 * The MCP write tools as a client meets them: who is offered each one, how they
 * are flagged, and what the server does with arguments that carry code.
 */
#[AllowMockObjectsWithoutExpectations]
final class McpWriteToolsTest extends TestCase
{
    use BuildsFormWriter;

    protected function setUp(): void
    {
        $this->buildWriter();
    }

    /** @return array<string, McpTool> */
    private function tools(): array
    {
        $tools = [
            new CreateFormTool($this->writer),
            new UpdateFormTool($this->writer),
            new ManageFormFieldsTool($this->writer),
            new TrashFormTool($this->writer),
            new RestoreFormTool($this->writer),
        ];

        return array_combine(array_map(static fn (McpTool $t): string => $t->name(), $tools), $tools);
    }

    private function server(): McpServer
    {
        return new McpServer(array_values($this->tools()), '6.1.1');
    }

    /**
     * @param array<string, mixed> $params
     * @param list<string>         $rights
     * @return array<string, mixed>
     */
    private function call(string $method, array $params, array $rights): array
    {
        return (array) $this->server()->handleBody(
            (string) json_encode(['jsonrpc' => '2.0', 'id' => 1, 'method' => $method, 'params' => $params]),
            McpTestDoubles::user($rights)
        );
    }

    /** @return iterable<string, array{string, list<string>, list<string>}> */
    public static function rightsProvider(): iterable
    {
        // tool, rights that are enough, one right removed at a time must fail.
        yield 'create' => ['bfng_create_form', ['core.manage', 'core.create'], ['core.manage', 'core.create']];
        yield 'update' => ['bfng_update_form', ['core.manage', 'core.edit'], ['core.manage', 'core.edit']];
        yield 'manage fields' => ['bfng_manage_form_fields', ['core.manage', 'core.edit'], ['core.manage', 'core.edit']];
        yield 'trash' => ['bfng_trash_form', ['core.manage', 'core.delete', 'core.edit.state'], ['core.manage', 'core.delete', 'core.edit.state']];
        yield 'restore' => ['bfng_restore_form', ['core.manage', 'core.edit.state'], ['core.manage', 'core.edit.state']];
    }

    /**
     * @param list<string> $enough
     * @param list<string> $each
     */
    #[DataProvider('rightsProvider')]
    public function testEveryWriteToolNeedsEachOfItsRights(string $name, array $enough, array $each): void
    {
        $tool = $this->tools()[$name];

        self::assertTrue($tool->allows(McpTestDoubles::user($enough)));
        self::assertFalse($tool->allows(McpTestDoubles::user([])));
        foreach ($each as $missing) {
            self::assertFalse(
                $tool->allows(McpTestDoubles::user(array_values(array_diff($enough, [$missing])))),
                $name . ' must not be allowed without ' . $missing
            );
        }
    }

    public function testNoWriteToolIsReadOnly(): void
    {
        foreach ($this->tools() as $name => $tool) {
            self::assertFalse($tool->readOnly(), $name);
        }
    }

    public function testOnlyTheToolsThatCanRemoveDataAreDestructive(): void
    {
        $destructive = array_keys(array_filter($this->tools(), static fn (McpTool $t): bool => $t instanceof DestructiveMcpTool));
        sort($destructive);

        self::assertSame(['bfng_manage_form_fields', 'bfng_trash_form'], $destructive);
    }

    public function testTheToolListFlagsDestructiveToolsAndHidesWhatTheUserMayNotUse(): void
    {
        $all = ['core.manage', 'core.create', 'core.edit', 'core.delete', 'core.edit.state'];
        $tools = $this->call('tools/list', [], $all)['result']['tools'];
        $byName = array_column($tools, 'annotations', 'name');

        self::assertCount(5, $tools);
        self::assertTrue($byName['bfng_trash_form']['destructiveHint']);
        self::assertTrue($byName['bfng_manage_form_fields']['destructiveHint']);
        self::assertFalse($byName['bfng_update_form']['destructiveHint']);
        self::assertFalse($byName['bfng_create_form']['destructiveHint']);
        self::assertFalse($byName['bfng_restore_form']['destructiveHint']);
        self::assertContains(false, array_column($byName, 'readOnlyHint'));
        self::assertNotContains(true, array_column($byName, 'readOnlyHint'));

        $limited = $this->call('tools/list', [], ['core.manage', 'core.edit'])['result']['tools'];
        self::assertSame(['bfng_update_form', 'bfng_manage_form_fields'], array_column($limited, 'name'));
    }

    public function testATrashCallWithoutTheRightsIsAnUnknownToolAndTouchesNothing(): void
    {
        $this->options->expects(self::never())->method('getForm');
        $this->forms->expects(self::never())->method('publish');

        $response = $this->call('tools/call', ['name' => 'bfng_trash_form', 'arguments' => ['id' => 7]], ['core.manage', 'core.edit']);

        self::assertSame(McpServer::INVALID_PARAMS, $response['error']['code']);
        self::assertStringContainsString('Unknown tool', $response['error']['message']);
    }

    public function testATrashCallWithTheRightsTrashesTheForm(): void
    {
        $this->options->method('getForm')->willReturn(self::storedForm());
        $this->forms->expects(self::once())->method('publish')->willReturn(true);

        $response = $this->call(
            'tools/call',
            ['name' => 'bfng_trash_form', 'arguments' => ['id' => 7]],
            ['core.manage', 'core.delete', 'core.edit.state']
        );

        self::assertFalse($response['result']['isError']);
        self::assertSame(['id' => 7, 'state' => 'trashed'], $response['result']['structuredContent']);
    }

    public function testAToolFailureReachesTheClientAsAReadableToolError(): void
    {
        $this->options->method('getForm')->willReturn(null);

        $response = $this->call('tools/call', ['name' => 'bfng_restore_form', 'arguments' => ['id' => 7]], ['core.manage', 'core.edit.state']);

        self::assertTrue($response['result']['isError']);
        self::assertSame('No trashed form has this id.', $response['result']['content'][0]['text']);
    }

    /** @return iterable<string, array{string, array<string, mixed>}> */
    public static function invalidArgumentsProvider(): iterable
    {
        yield 'trash without id' => ['bfng_trash_form', []];
        yield 'trash with id 0' => ['bfng_trash_form', ['id' => 0]];
        yield 'trash with a text id' => ['bfng_trash_form', ['id' => '7 OR 1=1']];
        yield 'restore with an unknown argument' => ['bfng_restore_form', ['id' => 7, 'published' => true]];
        yield 'create without a title' => ['bfng_create_form', ['name' => 'a']];
        yield 'update with a width out of range' => ['bfng_update_form', ['id' => 7, 'width' => 5000]];
    }

    /** @param array<string, mixed> $arguments */
    #[DataProvider('invalidArgumentsProvider')]
    public function testArgumentsAreCheckedBeforeAnyModelIsReached(string $tool, array $arguments): void
    {
        $this->options->expects(self::never())->method('getForm');
        $this->options->expects(self::never())->method('saveForm');
        $this->forms->expects(self::never())->method('publish');
        $this->quickmode->expects(self::never())->method('save');

        $response = $this->call(
            'tools/call',
            ['name' => $tool, 'arguments' => $arguments],
            ['core.manage', 'core.create', 'core.edit', 'core.delete', 'core.edit.state']
        );

        self::assertTrue($response['result']['isError']);
    }

    /** @return iterable<string, array{string}> */
    public static function codeColumnProvider(): iterable
    {
        foreach (FormCodeGuard::CODE_COLUMNS as $column) {
            yield $column => [$column];
        }
    }

    #[DataProvider('codeColumnProvider')]
    public function testNoMcpWriteToolAcceptsACodeColumn(string $column): void
    {
        $this->options->expects(self::never())->method('saveForm');
        $this->quickmode->expects(self::never())->method('save');
        $rights = ['core.manage', 'core.create', 'core.edit', 'core.delete', 'core.edit.state', 'breezingformsng.code'];

        foreach ([
            'bfng_create_form' => ['name' => 'a', 'title' => 'A', $column => 'system("id");'],
            'bfng_update_form' => ['id' => 7, 'title' => 'A', $column => 'system("id");'],
            'bfng_manage_form_fields' => ['id' => 7, 'add_pages' => 1, $column => 'system("id");'],
        ] as $tool => $arguments) {
            $response = $this->call('tools/call', ['name' => $tool, 'arguments' => $arguments], $rights);

            self::assertTrue($response['result']['isError'], $tool . ' accepted ' . $column);
        }
    }
}
