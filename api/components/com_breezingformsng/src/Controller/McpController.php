<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Vcmb\Component\BreezingformsNG\Api\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseInterface;
use Vcmb\Component\BreezingformsNG\Administrator\Mcp\McpServer;
use Vcmb\Component\BreezingformsNG\Administrator\Mcp\McpServerFactory;

/**
 * The MCP endpoint (Streamable HTTP transport, JSON responses, no session):
 * POST api/index.php/v1/breezingformsng/mcp.
 *
 * The route is public for the Joomla API application, so that this endpoint
 * authenticates the request itself: through the same API authentication
 * plugins (the Joomla API token) and the same "API login" permission, but
 * answering a failure with 401, as MCP clients expect, where the API
 * application would answer a JSON client with an error 500.
 */
class McpController extends BaseController
{
    /** The largest request accepted: a tool call is far smaller. */
    private const MAX_BODY = 1_048_576;

    public function handle(): void
    {
        $app = $this->app;
        if (!$app instanceof CMSApplication) {
            throw new \RuntimeException('The MCP endpoint runs in the API application.');
        }
        $server = $this->input->server;

        if (!$app->login(['username' => ''], ['silent' => true, 'action' => 'core.login.api'])) {
            $app->setHeader('WWW-Authenticate', 'Bearer', true);
            $this->respond(401, McpServer::error(null, McpServer::INVALID_REQUEST, 'A valid Joomla API token is required.'));

            return;
        }

        $user = $app->getIdentity();
        if ($user === null || $user->guest || !$user->authorise('core.manage', 'com_breezingformsng')) {
            $this->respond(403, McpServer::error(null, McpServer::INVALID_REQUEST, 'You are not allowed to use BreezingForms NG.'));

            return;
        }

        // Against DNS rebinding: a browser page of another site cannot use it.
        $origin = $server->getString('HTTP_ORIGIN', '');
        if ($origin !== '' && strcasecmp((string) parse_url($origin, PHP_URL_HOST), Uri::getInstance()->getHost()) !== 0) {
            $this->respond(403, McpServer::error(null, McpServer::INVALID_REQUEST, 'This origin is not allowed.'));

            return;
        }

        if ($this->input->getMethod() !== 'POST') {
            // No stream of messages from the server: every answer comes with its request.
            $app->setHeader('Allow', 'POST', true);
            $this->respond(405, McpServer::error(null, McpServer::INVALID_REQUEST, 'Only POST is supported.'));

            return;
        }

        $protocol = $server->getString('HTTP_MCP_PROTOCOL_VERSION', '');
        if ($protocol !== '' && !McpServer::supports($protocol)) {
            $this->respond(400, McpServer::error(null, McpServer::INVALID_REQUEST, 'Unsupported protocol version.'));

            return;
        }

        $body = (string) file_get_contents('php://input', false, null, 0, self::MAX_BODY + 1);
        if (strlen($body) > self::MAX_BODY) {
            $this->respond(413, McpServer::error(null, McpServer::INVALID_REQUEST, 'The request is too large.'));

            return;
        }

        $response = McpServerFactory::create(Factory::getContainer()->get(DatabaseInterface::class))->handleBody($body, $user);
        $this->respond($response === null ? 202 : 200, $response);
    }

    /**
     * @param array<string, mixed>|null $message
     */
    private function respond(int $status, ?array $message): void
    {
        $app = $this->app;
        if (!$app instanceof CMSApplication) {
            return;
        }
        $app->setHeader('status', (string) $status, true);
        $app->setHeader('Cache-Control', 'no-store', true);
        if ($message !== null) {
            echo json_encode($message, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        }
    }
}
