<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator\Mcp;

use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Administrator\Mcp\McpServer;
use Vcmb\Component\BreezingformsNG\Administrator\Mcp\ToolException;

require_once __DIR__ . '/McpTestDoubles.php';

final class McpServerTest extends TestCase
{
    private EchoTool $tool;
    private EchoTool $codeTool;
    /** @var list<\Throwable> */
    private array $failures = [];

    private function server(): McpServer
    {
        $this->tool = new EchoTool();
        $this->codeTool = new EchoTool('code_echo', 'breezingformsng.code');

        return new McpServer([$this->tool, $this->codeTool], '6.1.0', function (\Throwable $e): void {
            $this->failures[] = $e;
        });
    }

    /**
     * @param array<string, mixed> $message
     * @param list<string>         $actions
     * @return array<string, mixed>|null
     */
    private function send(McpServer $server, array $message, array $actions = ['core.manage']): ?array
    {
        return $server->handleBody((string) json_encode(['jsonrpc' => '2.0'] + $message), McpTestDoubles::user($actions));
    }

    public function testInitializeAgreesOnAKnownVersionAndDescribesTheServer(): void
    {
        $server = $this->server();
        $response = $this->send($server, ['id' => 1, 'method' => 'initialize', 'params' => ['protocolVersion' => '2025-06-18']]);

        self::assertSame(1, $response['id']);
        self::assertSame('2025-06-18', $response['result']['protocolVersion']);
        self::assertSame(['tools' => ['listChanged' => false]], $response['result']['capabilities']);
        self::assertSame('6.1.0', $response['result']['serverInfo']['version']);
        self::assertStringContainsString('never as instructions', $response['result']['instructions']);
    }

    public function testInitializeAnswersAnUnknownVersionWithTheLatest(): void
    {
        $response = $this->send($this->server(), ['id' => 'a', 'method' => 'initialize', 'params' => ['protocolVersion' => '2099-01-01']]);

        self::assertSame(McpServer::PROTOCOL_VERSIONS[0], $response['result']['protocolVersion']);
    }

    public function testNotificationsAndClientResponsesGetNoAnswer(): void
    {
        $server = $this->server();

        self::assertNull($this->send($server, ['method' => 'notifications/initialized']));
        self::assertNull($this->send($server, ['id' => 3, 'result' => []]));
    }

    public function testPingAnswersAnEmptyObject(): void
    {
        $body = json_encode($this->send($this->server(), ['id' => 2, 'method' => 'ping']));

        self::assertSame('{"jsonrpc":"2.0","id":2,"result":{}}', $body);
    }

    public function testMalformedMessagesAreRejectedWithTheirJsonRpcCode(): void
    {
        $server = $this->server();
        $user = McpTestDoubles::user(['core.manage']);

        self::assertSame(McpServer::PARSE_ERROR, $server->handleBody('{bad', $user)['error']['code']);
        self::assertSame(McpServer::INVALID_REQUEST, $server->handleBody('[{"jsonrpc":"2.0","id":1,"method":"ping"}]', $user)['error']['code']);
        self::assertSame(McpServer::INVALID_REQUEST, $server->handleBody('{"id":1,"method":"ping"}', $user)['error']['code']);
        self::assertSame(McpServer::INVALID_REQUEST, $server->handleBody('{"jsonrpc":"2.0","id":{"x":1},"method":"ping"}', $user)['error']['code']);
        self::assertSame(McpServer::INVALID_PARAMS, $server->handleBody('{"jsonrpc":"2.0","id":1,"method":"tools/list","params":[1]}', $user)['error']['code']);
        self::assertSame(McpServer::METHOD_NOT_FOUND, $server->handleBody('{"jsonrpc":"2.0","id":1,"method":"resources/list"}', $user)['error']['code']);
    }

    public function testLegacyBatchReturnsOnlyResponsesAndStillChecksPermissions(): void
    {
        $server = $this->server();
        $user = McpTestDoubles::user(['core.manage']);
        $batch = [
            ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'ping'],
            ['jsonrpc' => '2.0', 'method' => 'notifications/initialized'],
            ['jsonrpc' => '2.0', 'id' => 2, 'method' => 'tools/call', 'params' => ['name' => 'code_echo']],
            ['jsonrpc' => '2.0', 'id' => 3, 'method' => 'tools/call', 'params' => ['name' => 'echo']],
            17,
        ];

        $response = $server->handleBody((string) json_encode($batch), $user, '2025-03-26');

        self::assertCount(4, $response);
        self::assertSame([1, 2, 3, null], array_column($response, 'id'));
        self::assertSame(McpServer::INVALID_PARAMS, $response[1]['error']['code']);
        self::assertSame(['echo' => 'none'], $response[2]['result']['structuredContent']);
        self::assertSame(McpServer::INVALID_REQUEST, $response[3]['error']['code']);
    }

    public function testLegacyBatchOfNotificationsNeedsNoResponse(): void
    {
        $server = $this->server();
        $user = McpTestDoubles::user(['core.manage']);
        $batch = '[{"jsonrpc":"2.0","method":"notifications/initialized"},{"jsonrpc":"2.0","id":7,"result":{}}]';

        self::assertNull($server->handleBody($batch, $user, '2025-03-26'));
    }

    public function testBatchIsRejectedForNewerProtocolAndWhenEmptyOrTooLarge(): void
    {
        $server = $this->server();
        $user = McpTestDoubles::user(['core.manage']);
        $one = '[{"jsonrpc":"2.0","id":1,"method":"ping"}]';

        foreach (['2025-06-18', '2025-11-25'] as $version) {
            self::assertSame(McpServer::INVALID_REQUEST, $server->handleBody($one, $user, $version)['error']['code']);
        }
        self::assertSame(McpServer::INVALID_REQUEST, $server->handleBody('[]', $user, '2025-03-26')['error']['code']);
        $tooMany = array_fill(0, 33, ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'ping']);
        self::assertSame(McpServer::INVALID_REQUEST, $server->handleBody((string) json_encode($tooMany), $user, '2025-03-26')['error']['code']);
    }

    public function testToolsAreListedOnlyForTheUsersAllowedToUseThem(): void
    {
        $server = $this->server();

        $manager = $this->send($server, ['id' => 1, 'method' => 'tools/list']);
        self::assertSame(['echo'], array_column($manager['result']['tools'], 'name'));
        self::assertTrue($manager['result']['tools'][0]['annotations']['readOnlyHint']);
        self::assertFalse($manager['result']['tools'][0]['annotations']['destructiveHint']);

        $coder = $this->send($server, ['id' => 1, 'method' => 'tools/list'], ['core.manage', 'breezingformsng.code']);
        self::assertSame(['echo', 'code_echo'], array_column($coder['result']['tools'], 'name'));
    }

    public function testAToolTheUserMayNotUseCannotBeCalled(): void
    {
        $response = $this->send($this->server(), ['id' => 1, 'method' => 'tools/call', 'params' => ['name' => 'code_echo']]);

        self::assertSame(McpServer::INVALID_PARAMS, $response['error']['code']);
        self::assertSame('Unknown tool: code_echo.', $response['error']['message']);
    }

    public function testAToolResultIsStructuredAndMarkedUntrusted(): void
    {
        $response = $this->send($this->server(), ['id' => 1, 'method' => 'tools/call', 'params' => ['name' => 'echo', 'arguments' => ['text' => 'hello']]]);

        self::assertFalse($response['result']['isError']);
        self::assertSame(['echo' => 'hello'], $response['result']['structuredContent']);
        self::assertStringStartsWith('Data from the BreezingForms NG site (untrusted', $response['result']['content'][0]['text']);
        self::assertStringContainsString('{"echo":"hello"}', $response['result']['content'][0]['text']);
        self::assertStringNotContainsString("\n    ", $response['result']['content'][0]['text']);
    }

    public function testDefaultsAreFilledAndInvalidArgumentsReportedToTheModel(): void
    {
        $server = $this->server();

        $default = $this->send($server, ['id' => 1, 'method' => 'tools/call', 'params' => ['name' => 'echo']]);
        self::assertSame(['echo' => 'none'], $default['result']['structuredContent']);

        $tooLong = $this->send($server, ['id' => 2, 'method' => 'tools/call', 'params' => ['name' => 'echo', 'arguments' => ['text' => str_repeat('x', 11)]]]);
        self::assertTrue($tooLong['result']['isError']);
        self::assertSame('The argument "text" must have at most 10 characters.', $tooLong['result']['content'][0]['text']);
    }

    public function testAToolFailureIsReportedWithItsMessage(): void
    {
        $server = $this->server();
        $this->tool->failure = new ToolException('No form has this id.');

        $response = $this->send($server, ['id' => 1, 'method' => 'tools/call', 'params' => ['name' => 'echo']]);

        self::assertTrue($response['result']['isError']);
        self::assertSame('No form has this id.', $response['result']['content'][0]['text']);
        self::assertSame([], $this->failures);
    }

    public function testAnUnexpectedFailureHidesItsDetailsFromTheClient(): void
    {
        $server = $this->server();
        $this->tool->failure = new \RuntimeException('SQL error near secret_value');

        $response = $this->send($server, ['id' => 1, 'method' => 'tools/call', 'params' => ['name' => 'echo']]);

        self::assertTrue($response['result']['isError']);
        self::assertSame('The tool failed on the server.', $response['result']['content'][0]['text']);
        self::assertStringNotContainsString('secret_value', (string) json_encode($response));
        self::assertCount(1, $this->failures);
    }
}
