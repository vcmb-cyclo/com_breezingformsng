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

/** Creates a blank, unpublished form with safe default settings. */
final class CreateFormTool implements McpTool
{
    public function __construct(private readonly McpFormWriter $writer)
    {
    }

    public function name(): string
    {
        return 'bfng_create_form';
    }

    public function title(): string
    {
        return 'Create form';
    }

    public function description(): string
    {
        return 'Creates an unpublished BreezingForms NG form with up to 30 text, textarea, select, radio, checkbox-group or checkbox fields. '
            . 'Text and textarea fields can use the standard non-empty validation; custom scripts, other validations, integrations, file uploads, payments and notification destinations are not configured.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'name' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 255, 'description' => 'Internal form name.'],
                'title' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 255, 'description' => 'Title shown to administrators and visitors.'],
                'description' => ['type' => 'string', 'maxLength' => 10000, 'default' => '', 'description' => 'Optional form description.'],
                'fields' => [
                    'type' => 'array',
                    'maxItems' => 30,
                    'default' => [],
                    'description' => 'Optional fields. Names must be unique; labels, placeholders and choice labels/values are plain text.',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'type' => ['type' => 'string', 'enum' => ['text', 'textarea', 'select', 'radio', 'checkbox_group', 'checkbox'], 'description' => 'Field type.'],
                            'name' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 100, 'description' => 'Field name, starting with a letter; letters, numbers and underscores only.'],
                            'label' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 255, 'description' => 'Plain-text field label.'],
                            'placeholder' => ['type' => 'string', 'maxLength' => 255, 'default' => '', 'description' => 'Optional plain-text placeholder.'],
                            'multiple' => ['type' => 'boolean', 'description' => 'Allow multiple selected values on a select field.'],
                            'checked' => ['type' => 'boolean', 'description' => 'Initial checked state for a checkbox field.'],
                            'value' => ['type' => 'string', 'maxLength' => 255, 'description' => 'Submitted value for a checkbox field; defaults to "1".'],
                            'required' => ['type' => 'boolean', 'description' => 'Make a text or textarea field required.'],
                            'required_message' => ['type' => 'string', 'maxLength' => 255, 'description' => 'Optional plain-text message shown when a required field is empty.'],
                            'options' => [
                                'type' => 'array',
                                'maxItems' => 30,
                                'default' => [],
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
                            ],
                        ],
                        'required' => ['type', 'name', 'label'],
                        'additionalProperties' => false,
                    ],
                ],
            ],
            'required' => ['name', 'title'],
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
            && $user->authorise('core.create', 'com_breezingformsng');
    }

    public function call(array $arguments, User $user): array
    {
        return $this->writer->create($arguments, $user);
    }
}
