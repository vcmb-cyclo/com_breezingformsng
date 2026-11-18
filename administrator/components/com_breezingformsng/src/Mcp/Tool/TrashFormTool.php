<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Vcmb\Component\BreezingformsNG\Administrator\Mcp\Tool;

\defined('_JEXEC') or die;

use Joomla\CMS\User\User;
use Vcmb\Component\BreezingformsNG\Administrator\Mcp\DestructiveMcpTool;
use Vcmb\Component\BreezingformsNG\Administrator\Mcp\McpFormWriter;

/** Reversibly moves a form to Joomla's trash. */
final class TrashFormTool implements DestructiveMcpTool
{
    public function __construct(private readonly McpFormWriter $writer)
    {
    }

    public function name(): string
    {
        return 'bfng_trash_form';
    }

    public function title(): string
    {
        return 'Move form to trash';
    }

    public function description(): string
    {
        return 'Moves a form to Joomla trash without permanently deleting it or its submissions. '
            . 'A trashed form can be restored with bfng_restore_form.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => ['id' => ['type' => 'integer', 'minimum' => 1, 'description' => 'Id of the form to trash.']],
            'required' => ['id'],
            'additionalProperties' => false,
        ];
    }

    public function readOnly(): bool
    {
        return false;
    }

    public function allows(User $user): bool
    {
        return $user->authorise('core.manage', 'com_breezingformsng')
            && $user->authorise('core.delete', 'com_breezingformsng')
            && $user->authorise('core.edit.state', 'com_breezingformsng');
    }

    public function call(array $arguments, User $user): array
    {
        return $this->writer->trashForm($arguments, $user);
    }
}
