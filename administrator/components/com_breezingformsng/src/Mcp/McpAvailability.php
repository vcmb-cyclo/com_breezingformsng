<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Vcmb\Component\BreezingformsNG\Administrator\Mcp;

\defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Plugin\PluginHelper;

/**
 * Whether the MCP endpoint exists: the "Enable MCP" option of BreezingForms NG,
 * off by default. Off, the endpoint is neither routed nor answered.
 */
final class McpAvailability
{
    public const OPTION = 'mcp_enabled';

    public static function isEnabled(): bool
    {
        return ComponentHelper::isEnabled('com_breezingformsng')
            && (int) ComponentHelper::getParams('com_breezingformsng')->get(self::OPTION, 0) === 1;
    }

    /** Whether the plugin that routes the endpoint is enabled. */
    public static function isRouted(): bool
    {
        return PluginHelper::isEnabled('webservices', 'breezingformsng');
    }
}
