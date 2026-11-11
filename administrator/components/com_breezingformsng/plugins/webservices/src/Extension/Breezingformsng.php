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

namespace Vcmb\Plugin\WebServices\Breezingformsng\Extension;

use Joomla\CMS\Event\Application\BeforeApiRouteEvent;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\SubscriberInterface;
use Joomla\Router\Route;
use Vcmb\Component\BreezingformsNG\Administrator\Mcp\McpAvailability;

\defined('_JEXEC') or die;

/**
 * Web services of BreezingForms NG: the MCP endpoint, through which an AI
 * client (Claude Code, Codex...) uses the site with the Joomla API token of a
 * user, and that user's permissions. It is routed only when MCP is enabled in
 * the options of BreezingForms NG.
 */
final class Breezingformsng extends CMSPlugin implements SubscriberInterface
{
    public const MCP_ROUTE = 'v1/breezingformsng/mcp';

    /**
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return ['onBeforeApiRoute' => 'onBeforeApiRoute'];
    }

    public function onBeforeApiRoute(BeforeApiRouteEvent $event): void
    {
        // With MCP off in the options of BreezingForms NG, the endpoint does not exist.
        if (!McpAvailability::isEnabled()) {
            return;
        }

        // Public for the API application: the MCP controller authenticates each request itself.
        $defaults = ['component' => 'com_breezingformsng', 'format' => ['application/json'], 'public' => true];

        $event->getRouter()->addRoutes([
            new Route(['POST'], self::MCP_ROUTE, 'mcp.handle', [], $defaults),
            // A client may ask for a stream of server messages: it is told there is none.
            new Route(['GET', 'DELETE'], self::MCP_ROUTE, 'mcp.handle', [], ['format' => ['application/json', 'text/event-stream']] + $defaults),
        ]);
    }
}
