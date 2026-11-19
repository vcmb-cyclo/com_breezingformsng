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

    #[\Override]
    public function name(): string
    {
        return 'bfng_create_form';
    }

    #[\Override]
    public function title(): string
    {
        return 'Create form';
    }

    #[\Override]
    public function description(): string
    {
        return 'Creates an unpublished BreezingForms NG form with an optional plain-text first-page introduction and up to 30 text, textarea, select, radio, checkbox-group or checkbox fields. '
            . 'Text and textarea fields can use the standard non-empty validation; custom scripts, other validations, integrations, file uploads, payments and notification destinations are not configured.';
    }

    #[\Override]
    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'name' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 255, 'description' => 'Internal form name.'],
                'title' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 255, 'description' => 'Title shown to administrators and visitors.'],
                'description' => ['type' => 'string', 'maxLength' => 10000, 'default' => '', 'description' => 'Optional form description.'],
                'page_intro' => ['type' => 'string', 'maxLength' => 1000, 'default' => '', 'description' => 'Optional plain-text introduction for the first page. Markup and module/plugin syntax are rejected.'],
                'fields' => [
                    'type' => 'array',
                    'maxItems' => 30,
                    'default' => [],
                    'description' => 'Optional fields. Names must be unique; labels, placeholders, initial values and choice labels/values are plain text. Text fields support a maximum length.',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'type' => ['type' => 'string', 'enum' => ['text', 'textarea', 'select', 'radio', 'checkbox_group', 'checkbox'], 'description' => 'Field type.'],
                            'name' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 100, 'description' => 'Field name, starting with a letter; letters, numbers and underscores only.'],
                            'label' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 255, 'description' => 'Plain-text field label.'],
                            'placeholder' => ['type' => 'string', 'maxLength' => 255, 'default' => '', 'description' => 'Optional plain-text placeholder.'],
                            'hint' => ['type' => 'string', 'maxLength' => 1000, 'description' => 'Optional plain-text help shown with the field.'],
                            'size' => ['type' => 'string', 'maxLength' => 20, 'description' => 'Optional text-field width, for example 240px, 80%, 18rem or auto.'],
                            'width' => ['type' => 'string', 'maxLength' => 20, 'description' => 'Optional textarea width, for example 480px, 100% or 30rem.'],
                            'height' => ['type' => 'string', 'maxLength' => 20, 'description' => 'Optional textarea height, for example 120px or 8rem.'],
                            'default_value' => ['type' => 'string', 'maxLength' => 4000, 'description' => 'Optional plain-text initial value for a text or textarea field.'],
                            'max_length' => ['type' => 'integer', 'minimum' => 0, 'maximum' => 10000, 'description' => 'Optional maximum characters for a text or textarea field; 0 means unlimited.'],
                            'readonly' => ['type' => 'boolean', 'description' => 'Whether a text or textarea field is read-only.'],
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

    #[\Override]
    public function readOnly(): bool
    {
        return false;
    }

    #[\Override]
    public function allows(User $user): bool
    {
        return $user->authorise('core.manage', 'com_breezingformsng')
            && $user->authorise('core.create', 'com_breezingformsng');
    }

    #[\Override]
    public function call(array $arguments, User $user): array
    {
        return $this->writer->create($arguments, $user);
    }
}
