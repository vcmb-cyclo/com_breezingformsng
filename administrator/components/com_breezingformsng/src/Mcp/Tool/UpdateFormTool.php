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

/** Updates only a form's basic metadata, retaining its publication state and structure. */
final class UpdateFormTool implements McpTool
{
    public function __construct(private readonly McpFormWriter $writer)
    {
    }

    public function name(): string
    {
        return 'bfng_update_form';
    }

    public function title(): string
    {
        return 'Update form metadata';
    }

    public function description(): string
    {
        return 'Updates one existing BreezingForms NG form by id. Give one or more of name, title and description. '
            . 'It can also configure width, height and automatic height. It does not change publication, fields, scripts, '
            . 'integrations or notification settings.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'integer', 'minimum' => 1, 'description' => 'Id of the form to update.'],
                'name' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 255, 'description' => 'New internal form name.'],
                'title' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 255, 'description' => 'New form title.'],
                'description' => ['type' => 'string', 'maxLength' => 10000, 'description' => 'New description; an empty string clears it.'],
                'width' => ['type' => 'integer', 'minimum' => 100, 'maximum' => 2000, 'description' => 'Form width in pixels.'],
                'height' => ['type' => 'integer', 'minimum' => 100, 'maximum' => 5000, 'description' => 'Form height in pixels.'],
                'autoheight' => ['type' => 'boolean', 'description' => 'Whether the form grows to fit its content.'],
            ],
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
            && $user->authorise('core.edit', 'com_breezingformsng');
    }

    public function call(array $arguments, User $user): array
    {
        return $this->writer->update($arguments, $user);
    }
}
