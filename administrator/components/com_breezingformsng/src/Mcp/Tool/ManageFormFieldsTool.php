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
        return 'Adds, updates or removes text, textarea, select, radio, checkbox-group and checkbox fields on compatible QuickMode forms. '
            . 'Pages can be added or empty pages removed; new fields can target existing or new pages, and existing fields can move between pages. Updates can change text/textarea type, labels, placeholders, required state, checkbox values, select multiplicity or choice options; field names stay fixed. '
            . 'Existing custom scripts and settings are preserved; the required option uses only the standard non-empty validation. Fields cannot be removed after submissions exist. '
            . 'Forms with other field types or nested field structures are not changed. Choice options cannot be changed after submissions exist.';
    }

    public function inputSchema(): array
    {
        $newField = [
            'type' => 'object',
            'properties' => [
                'type' => ['type' => 'string', 'enum' => ['text', 'textarea', 'select', 'radio', 'checkbox_group', 'checkbox'], 'description' => 'Field type.'],
                'name' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 100, 'description' => 'Unique field name.'],
                'label' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 255, 'description' => 'Plain-text field label.'],
                'placeholder' => ['type' => 'string', 'maxLength' => 255, 'default' => '', 'description' => 'Optional plain-text placeholder.'],
                'multiple' => ['type' => 'boolean', 'description' => 'Allow multiple selected values on a select field.'],
                'checked' => ['type' => 'boolean', 'description' => 'Initial checked state for a checkbox field.'],
                'value' => ['type' => 'string', 'maxLength' => 255, 'description' => 'Submitted value for a checkbox field.'],
                'required' => ['type' => 'boolean', 'description' => 'Make a text or textarea field required.'],
                'required_message' => ['type' => 'string', 'maxLength' => 255, 'description' => 'Optional plain-text required message.'],
                'page' => ['type' => 'integer', 'minimum' => 1, 'description' => 'Existing or newly added QuickMode page number, starting at 1; defaults to the first page.'],
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
                'multiple' => ['type' => 'boolean', 'description' => 'Whether a select field accepts more than one choice.'],
                'checked' => ['type' => 'boolean', 'description' => 'New initial checked state for a checkbox field.'],
                'value' => ['type' => 'string', 'maxLength' => 255, 'description' => 'New submitted value for a checkbox field.'],
                'required' => ['type' => 'boolean', 'description' => 'Whether a text or textarea field is required.'],
                'required_message' => ['type' => 'string', 'maxLength' => 255, 'description' => 'New plain-text required message.'],
                'page' => ['type' => 'integer', 'minimum' => 1, 'description' => 'Move the field to an existing or newly added QuickMode page number, starting at 1.'],
                'options' => self::choiceOptionsSchema(false),
            ],
            'required' => ['name'],
            'additionalProperties' => false,
        ];

        return [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'integer', 'minimum' => 1, 'description' => 'Id of the MCP-managed form.'],
                'add_pages' => ['type' => 'integer', 'minimum' => 0, 'maximum' => 10, 'default' => 0, 'description' => 'Number of blank QuickMode pages to append before adding or moving fields.'],
                'remove_pages' => ['type' => 'array', 'maxItems' => 10, 'default' => [], 'items' => ['type' => 'integer', 'minimum' => 1], 'description' => 'Existing empty page numbers to remove; submissions prevent page removal.'],
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
                    'selected' => ['type' => 'boolean', 'default' => false],
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
