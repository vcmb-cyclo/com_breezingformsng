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

/** Updates bounded form metadata and display settings. */
final class UpdateFormTool implements McpTool
{
    public function __construct(private readonly McpFormWriter $writer)
    {
    }

    #[\Override]
    public function name(): string
    {
        return 'bfng_update_form';
    }

    #[\Override]
    public function title(): string
    {
        return 'Update form metadata';
    }

    #[\Override]
    public function description(): string
    {
        return 'Updates one existing BreezingForms NG form by id. Give one or more form metadata or settings changes. '
            . 'It can configure publication, dimensions and administrative email notifications. Publishing requires core.edit.state. It does not change fields, scripts or third-party integrations.';
    }

    #[\Override]
    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'integer', 'minimum' => 1, 'description' => 'Id of the form to update.'],
                'name' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 255, 'description' => 'New internal form name.'],
                'title' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 255, 'description' => 'New form title.'],
                'description' => ['type' => 'string', 'maxLength' => 10000, 'description' => 'New description; an empty string clears it.'],
                'published' => ['type' => 'boolean', 'description' => 'Publish or unpublish the form. Requires the Joomla core.edit.state permission.'],
                'width' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 2000, 'description' => 'Form width: 1 to 100 when widthmode is 1 (percent), otherwise 100 to 2000 pixels.'],
                'widthmode' => ['type' => 'integer', 'enum' => [0, 1], 'description' => '0 for pixels; 1 for percent. When changing modes, give a compatible width in the same call.'],
                'height' => ['type' => 'integer', 'minimum' => 100, 'maximum' => 5000, 'description' => 'Form height in pixels.'],
                'heightmode' => ['type' => 'integer', 'enum' => [0, 1], 'description' => '0 for fixed height; 1 for automatic height.'],
                'autoheight' => ['type' => 'boolean', 'description' => 'Whether the form grows to fit its content.'],
                'email_notifications' => ['type' => 'boolean', 'description' => 'Whether submissions send the standard administrator notification email.'],
                'notification_recipient' => ['type' => 'string', 'maxLength' => 254, 'description' => 'Single email address for administrator submission notifications. An empty string clears it; notifications cannot remain enabled without a valid address.'],
            ],
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
            && $user->authorise('core.edit', 'com_breezingformsng');
    }

    #[\Override]
    public function call(array $arguments, User $user): array
    {
        return $this->writer->update($arguments, $user);
    }
}
