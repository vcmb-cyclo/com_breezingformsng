<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Vcmb\Component\BreezingformsNG\Administrator\Mcp\Tool;

\defined('_JEXEC') or die;

use Joomla\CMS\User\User;
use Vcmb\Component\BreezingformsNG\Administrator\Mcp\McpFormWriter;
use Vcmb\Component\BreezingformsNG\Administrator\Mcp\McpTool;

/** Restores a trashed form as unpublished. */
final class RestoreFormTool implements McpTool
{
    public function __construct(private readonly McpFormWriter $writer)
    {
    }

    #[\Override]
    public function name(): string
    {
        return 'bfng_restore_form';
    }

    #[\Override]
    public function title(): string
    {
        return 'Restore form';
    }

    #[\Override]
    public function description(): string
    {
        return 'Restores a trashed BreezingForms NG form as unpublished. It does not publish the form.';
    }

    #[\Override]
    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => ['id' => ['type' => 'integer', 'minimum' => 1, 'description' => 'Id of the trashed form.']],
            'required' => ['id'],
            'additionalProperties' => false,
        ];
    }

    #[\Override]
    public function readOnly(): bool
    {
        return false;
    }

    #[\Override]
    public function allows(User $user): bool
    {
        return $user->authorise('core.manage', 'com_breezingformsng')
            && $user->authorise('core.edit.state', 'com_breezingformsng');
    }

    #[\Override]
    public function call(array $arguments, User $user): array
    {
        return $this->writer->restoreForm($arguments, $user);
    }
}
