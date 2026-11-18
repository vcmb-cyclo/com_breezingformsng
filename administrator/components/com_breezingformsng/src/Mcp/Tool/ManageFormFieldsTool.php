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

/** Adds, edits and removes supported fields on compatible QuickMode forms. */
final class ManageFormFieldsTool implements DestructiveMcpTool
{
    public function __construct(private readonly McpFormWriter $writer)
    {
    }

    public function name(): string
    {
        return 'bfng_manage_form_fields';
    }

    public function title(): string
    {
        return 'Manage form fields';
    }

    public function description(): string
    {
        return 'Adds, updates or removes text, textarea, select, radio and checkbox-group fields on compatible QuickMode forms. '
            . 'Updates can change text/textarea type, label, placeholder or choice options; field names stay fixed. '
            . 'Existing scripts and settings are preserved but cannot be edited. Fields cannot be removed after submissions exist. '
            . 'Forms with other field types or nested field structures are not changed. Choice options cannot be changed after submissions exist.';
    }

    public function inputSchema(): array
    {
        $newField = [
            'type' => 'object',
            'properties' => [
                'type' => ['type' => 'string', 'enum' => ['text', 'textarea', 'select', 'radio', 'checkbox_group'], 'description' => 'Field type.'],
                'name' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 100, 'description' => 'Unique field name.'],
                'label' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 255, 'description' => 'Plain-text field label.'],
                'placeholder' => ['type' => 'string', 'maxLength' => 255, 'default' => '', 'description' => 'Optional plain-text placeholder.'],
                'options' => self::choiceOptionsSchema(),
            ],
            'required' => ['type', 'name', 'label'],
            'additionalProperties' => false,
        ];
        $updateField = [
            'type' => 'object',
            'properties' => [
                'name' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 100, 'description' => 'Existing field name.'],
                'type' => ['type' => 'string', 'enum' => ['text', 'textarea'], 'description' => 'Optional new text or textarea type.'],
                'label' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 255, 'description' => 'Optional new plain-text label.'],
                'placeholder' => ['type' => 'string', 'maxLength' => 255, 'description' => 'Optional new plain-text placeholder.'],
                'options' => self::choiceOptionsSchema(false),
            ],
            'required' => ['name'],
            'additionalProperties' => false,
        ];

        return [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'integer', 'minimum' => 1, 'description' => 'Id of the MCP-managed form.'],
                'add' => ['type' => 'array', 'maxItems' => 30, 'default' => [], 'items' => $newField, 'description' => 'Fields to add.'],
                'update' => ['type' => 'array', 'maxItems' => 30, 'default' => [], 'items' => $updateField, 'description' => 'Existing fields to update.'],
                'remove' => ['type' => 'array', 'maxItems' => 30, 'default' => [], 'items' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 100], 'description' => 'Existing field names to remove.'],
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
        return $this->writer->manageFields($arguments, $user);
    }

    private static function choiceOptionsSchema(bool $withDefault = true): array
    {
        $schema = [
            'type' => 'array',
            'maxItems' => 30,
            'description' => 'Choices for select, radio and checkbox_group fields; each value must be unique.',
            'items' => [
                'type' => 'object',
                'properties' => [
                    'label' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 255],
                    'value' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 255],
                ],
                'required' => ['label', 'value'],
                'additionalProperties' => false,
            ],
        ];
        if ($withDefault) {
            $schema['default'] = [];
        }

        return $schema;
    }
}
