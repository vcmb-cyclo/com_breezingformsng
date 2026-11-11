<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Vcmb\Component\BreezingformsNG\Administrator\Mcp;

\defined('_JEXEC') or die;

use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseInterface;
use Vcmb\Component\BreezingformsNG\Administrator\Mcp\Tool\GetFormTool;
use Vcmb\Component\BreezingformsNG\Administrator\Mcp\Tool\ListFormsTool;

/** Builds the MCP server of BFNG with all its tools. */
final class McpServerFactory
{
    public static function create(DatabaseInterface $db): McpServer
    {
        $catalog = new FormCatalog($db);

        return new McpServer(
            [new ListFormsTool($catalog), new GetFormTool($catalog)],
            self::version($db),
            // The class and place of the failure only: its message may quote form data.
            static function (\Throwable $e): void {
                Log::add(sprintf('MCP tool failure: %s at %s:%d', $e::class, $e->getFile(), $e->getLine()), Log::ERROR, 'com_breezingformsng');
            }
        );
    }

    private static function version(DatabaseInterface $db): string
    {
        $query = $db->createQuery()
            ->select($db->quoteName('manifest_cache'))
            ->from($db->quoteName('#__extensions'))
            ->where($db->quoteName('element') . ' = ' . $db->quote('com_breezingformsng'))
            ->where($db->quoteName('type') . ' = ' . $db->quote('component'));
        $manifest = json_decode((string) $db->setQuery($query, 0, 1)->loadResult(), true);

        return is_array($manifest) && is_string($manifest['version'] ?? null) ? $manifest['version'] : '';
    }
}
