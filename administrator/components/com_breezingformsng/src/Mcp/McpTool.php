<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Vcmb\Component\BreezingformsNG\Administrator\Mcp;

\defined('_JEXEC') or die;

use Joomla\CMS\User\User;

/**
 * A tool the MCP endpoint offers to an AI client.
 *
 * Its name, description and schemas are read by the model: they are written in
 * English, as the protocol and the model expect, and are not shown to users.
 */
interface McpTool
{
    public function name(): string;

    public function title(): string;

    public function description(): string;

    /**
     * The JSON Schema of the arguments, in the subset ArgumentValidator checks.
     *
     * @return array<string, mixed>
     */
    public function inputSchema(): array;

    /** Whether the tool only reads: it then changes nothing on the site. */
    public function readOnly(): bool;

    /** Whether the user may use the tool: it is otherwise neither listed nor run. */
    public function allows(User $user): bool;

    /**
     * Runs the tool with arguments already checked against its schema.
     *
     * @param array<string, mixed> $arguments
     * @return array<string, mixed> The result, returned as structured content.
     * @throws ToolException for a failure the model can act on.
     */
    public function call(array $arguments, User $user): array;
}
