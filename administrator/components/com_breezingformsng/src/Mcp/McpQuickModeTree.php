<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Vcmb\Component\BreezingformsNG\Administrator\Mcp;

\defined('_JEXEC') or die;

use Vcmb\Component\BreezingformsNG\Administrator\Model\FormOptionsModel;
use Vcmb\Component\BreezingformsNG\Administrator\Model\QuickmodeModel;

/**
 * Builds and validates the bounded QuickMode tree handled by MCP writes.
 *
 * @phpstan-type QuickModeElement array{
 *     attributes: array{id: string},
 *     properties: array<string, mixed>,
 *     children: list<never>
 * }
 * @phpstan-type QuickModePage array{
 *     attributes: array{id: string},
 *     properties: array<string, mixed>,
 *     children: list<QuickModeElement>
 * }
 * @phpstan-type QuickModeTree array{
 *     attributes: array{id: string},
 *     properties: array<string, mixed>,
 *     children: list<QuickModePage>
 * }
 */
final class McpQuickModeTree
{
    public function __construct(
        private readonly FormOptionsModel $options,
        private readonly QuickmodeModel $quickmode,
        private readonly McpFormInputNormalizer $input
    ) {
    }

    /**
     * @param list<array{type: string, name: string, label: string, placeholder: string, hint: string, size: string, width: string, height: string, default_value: string, max_length: int, readonly: bool, options: list<array{label: string, value: string, selected: bool}>, multiple: bool, checked: bool, value: string, required: bool, required_message: string, page: int}> $fields
     * @return array<string, mixed>
     */
    public function create(string $name, string $title, string $description, array $fields, string $pageIntro): array
    {
        $children = [];
        foreach ($fields as $index => $field) {
            $children[] = $this->element($field, $index + 1);
        }

        return [
            'attributes' => ['id' => 'bfQuickModeForm'],
            'properties' => [
                'type' => 'root',
                'name' => $name,
                'title' => $title,
                'description' => $description,
                'mailNotification' => false,
                'mailRecipient' => '',
                'submittedScriptCondidtion' => -1,
                'bfngManaged' => true,
            ],
            'children' => [[
                'attributes' => ['id' => 'bfQuickModePage1'],
                'properties' => ['type' => 'page', 'pageNumber' => 1, 'pageIntro' => $pageIntro],
                'children' => $children,
            ]],
        ];
    }

    /**
     * @param array{type: string, name: string, label: string, placeholder: string, hint: string, size: string, width: string, height: string, default_value: string, max_length: int, readonly: bool, options: list<array{label: string, value: string, selected: bool}>, multiple: bool, checked: bool, value: string, required: bool, required_message: string, page: int} $field
     * @return array<string, mixed>
     */
    public function element(array $field, int $order): array
    {
        $type = match ($field['type']) {
            'text' => 'bfTextfield',
            'textarea' => 'bfTextarea',
            'select' => 'bfSelect',
            'radio' => 'bfRadioGroup',
            'checkbox_group' => 'bfCheckboxGroup',
            'checkbox' => 'bfCheckbox',
            default => throw new ToolException('Unsupported field type.'),
        };
        $properties = [
            'type' => 'element',
            'bfType' => $type,
            'value' => $type === 'bfCheckbox' ? $field['value'] : $field['default_value'],
            'placeholder' => $field['placeholder'],
            'hint' => $field['hint'],
            'readonly' => $field['readonly'],
            'label' => $field['label'],
            'bfName' => $field['name'],
            'orderNumber' => $order,
            'tabIndex' => 0,
            'logging' => 1,
            'validationId' => 0,
            'validationCode' => '',
            'validationMessage' => '',
            'validationFunctionName' => '',
            'validationCondition' => 0,
            'initId' => 0,
            'initCode' => '',
            'initFunctionName' => '',
            'initCondition' => 0,
            'actionId' => 0,
            'actionCode' => '',
            'actionFunctionName' => '',
            'actionCondition' => 0,
            'dbId' => 0,
        ];
        if ($type === 'bfTextfield') {
            $properties['maxLength'] = $field['max_length'] > 0 ? $field['max_length'] : '';
            $properties['size'] = $field['size'];
        } elseif ($type === 'bfTextarea') {
            $properties['maxlength'] = $field['max_length'];
            $properties['showMaxlengthCounter'] = true;
            $properties['width'] = $field['width'];
            $properties['height'] = $field['height'];
        }
        if ($type === 'bfSelect') {
            $properties['multiple'] = $field['multiple'];
            $properties['list'] = $this->input->serializeChoices($field['options']);
            $properties['mailback'] = false;
        } elseif (in_array($type, ['bfRadioGroup', 'bfCheckboxGroup'], true)) {
            $properties['group'] = $this->input->serializeChoices($field['options']);
        } elseif ($type === 'bfCheckbox') {
            $properties['checked'] = $field['checked'];
            $properties['mailbackAccept'] = false;
            $properties['mailbackConnectWith'] = '';
        }
        if ($field['required']) {
            $properties['validationId'] = $this->input->requiredValidationScriptId();
            $properties['validationFunctionName'] = 'ff_valuenotempty';
            $properties['validationCondition'] = 1;
            $properties['validationMessage'] = $field['required_message'];
        }

        return [
            'attributes' => ['id' => 'bfQuickModeElement' . $order . '_' . strtolower($field['name'])],
            'properties' => $properties,
            'children' => [],
        ];
    }

    /**
     * @return QuickModeTree
     * @throws ToolException
     */
    public function managed(int $formId): array
    {
        $form = $this->options->getForm($formId);
        $template = $this->quickmode->getTemplateCode($formId);
        if (
            $form === null || (string) ($form->package ?? '') !== 'QuickModeForms'
            || (string) ($form->template_code_processed ?? '') !== 'QuickMode' || strlen($template) > 500000
        ) {
            throw new ToolException('Only QuickMode forms with a supported simple field structure can be edited here.');
        }

        $tree = json_decode($template, true);
        if (
            !is_array($tree) || !is_array($tree['attributes'] ?? null)
            || ($tree['attributes']['id'] ?? null) !== 'bfQuickModeForm'
            || !is_array($tree['properties'] ?? null) || !is_array($tree['children'] ?? null) || $tree['children'] === []
            || ($tree['properties']['type'] ?? null) !== 'root'
            || !is_array($tree['children'])
        ) {
            throw new ToolException('This QuickMode form has an unsupported root structure.');
        }
        if (
            ($tree['properties']['submittedScriptCondidtion'] ?? -1) != -1
            && !is_string($tree['properties']['submittedScriptCode'] ?? null)
        ) {
            throw new ToolException('This form has an incomplete submitted script configuration.');
        }

        $elementIds = [];
        $quickmodeIds = [];
        $pageIds = [];
        foreach ($tree['children'] as $pageIndex => $page) {
            if (!is_array($page) || !is_array($page['attributes'] ?? null) || !is_array($page['properties'] ?? null)) {
                throw new ToolException('This form has a malformed page that the MCP field editor cannot safely preserve.');
            }
            $pageId = $this->input->stringValue($page['attributes']['id'] ?? '');
            if (
                $pageId !== 'bfQuickModePage' . ((int) $pageIndex + 1)
                || !preg_match('/^bfQuickModePage[1-9][0-9]*$/D', $pageId)
                || isset($pageIds[$pageId])
                || ($page['properties']['type'] ?? null) !== 'page' || !is_array($page['children'] ?? null)
            ) {
                throw new ToolException('This form has a page structure that the MCP field editor cannot safely preserve.');
            }
            $pageIds[$pageId] = true;

            foreach ($page['children'] as $node) {
                if (!is_array($node)) {
                    throw new ToolException('This form contains a malformed field that cannot be safely preserved.');
                }
                $properties = $node['properties'] ?? null;
                if (!is_array($node['attributes'] ?? null) || !is_array($properties)) {
                    throw new ToolException('This form contains a malformed field that cannot be safely preserved.');
                }
                $nodeId = $node['attributes']['id'] ?? null;
                $requiredProperties = [
                    'type', 'bfType', 'value', 'label', 'bfName', 'orderNumber', 'tabIndex', 'logging',
                    'validationId', 'validationCode', 'validationMessage', 'validationFunctionName', 'validationCondition',
                    'initId', 'initCode', 'initFunctionName', 'initCondition', 'actionId', 'actionFunctionName', 'dbId',
                ];
                if (
                    !is_string($nodeId) || isset($quickmodeIds[$nodeId])
                    || ($properties['type'] ?? null) !== 'element'
                    || !in_array($properties['bfType'] ?? null, ['bfTextfield', 'bfTextarea', 'bfSelect', 'bfRadioGroup', 'bfCheckboxGroup', 'bfCheckbox'], true)
                    || array_diff($requiredProperties, array_keys($properties)) !== []
                    || !is_array($node['children'] ?? null) || $node['children'] !== []
                ) {
                    throw new ToolException('This form contains an unsupported field or nested structure the MCP field editor cannot safely preserve.');
                }
                $dbId = $this->input->intValue($properties['dbId'] ?? 0);
                $fieldName = trim($this->input->stringValue($properties['bfName'] ?? ''));
                if (
                    $dbId <= 0 || isset($elementIds[$dbId]) || $fieldName === '' || mb_strlen($fieldName) > 255
                    || preg_match('/[\x00-\x1F\x7F]/u', $fieldName)
                    || !is_string($properties['value']) || !is_string($properties['label'])
                    || mb_strlen($properties['label']) > 255
                    || mb_strlen($this->input->stringValue($properties['placeholder'] ?? '')) > 255
                ) {
                    throw new ToolException('This form has a field identity or text value that cannot be safely edited through MCP.');
                }
                $choiceProperty = match ($properties['bfType']) {
                    'bfSelect' => 'list',
                    'bfRadioGroup', 'bfCheckboxGroup' => 'group',
                    default => null,
                };
                if (
                    $choiceProperty !== null && (!is_string($properties[$choiceProperty] ?? null)
                    || ($properties['bfType'] === 'bfSelect'
                        && (!is_bool($properties['multiple'] ?? null) || !is_bool($properties['mailback'] ?? null))))
                ) {
                    throw new ToolException('This form has a malformed choice field that cannot be safely edited through MCP.');
                }
                if (
                    $properties['bfType'] === 'bfCheckbox'
                    && (!is_bool($properties['checked'] ?? null) || !is_string($properties['mailbackConnectWith'] ?? null)
                        || !is_bool($properties['mailbackAccept'] ?? null))
                ) {
                    throw new ToolException('This form has a malformed checkbox that cannot be safely edited through MCP.');
                }
                $elementIds[$dbId] = true;
                $quickmodeIds[$nodeId] = true;
            }
        }

        $this->assertManagedShape($tree);

        return $tree;
    }

    /**
     * @param array<string, mixed> $tree
     * @phpstan-assert QuickModeTree $tree
     * @throws ToolException
     */
    private function assertManagedShape(array $tree): void
    {
        if (
            !is_array($tree['attributes'] ?? null)
            || !is_string($tree['attributes']['id'] ?? null)
            || !is_array($tree['properties'] ?? null)
            || !is_array($tree['children'] ?? null)
            || !array_is_list($tree['children'])
        ) {
            throw new ToolException('This QuickMode form has an unsupported root structure.');
        }

        foreach ($tree['children'] as $page) {
            if (
                !is_array($page)
                || !is_array($page['attributes'] ?? null)
                || !is_string($page['attributes']['id'] ?? null)
                || !is_array($page['properties'] ?? null)
                || !is_array($page['children'] ?? null)
                || !array_is_list($page['children'])
            ) {
                throw new ToolException('This form has a malformed page that the MCP field editor cannot safely preserve.');
            }

            foreach ($page['children'] as $node) {
                if (
                    !is_array($node)
                    || !is_array($node['attributes'] ?? null)
                    || !is_string($node['attributes']['id'] ?? null)
                    || !is_array($node['properties'] ?? null)
                    || !is_array($node['children'] ?? null)
                    || $node['children'] !== []
                ) {
                    throw new ToolException('This form contains a malformed field that cannot be safely preserved.');
                }
            }
        }
    }
}
