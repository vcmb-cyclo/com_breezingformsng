<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Vcmb\Component\BreezingformsNG\Administrator\Mcp;

\defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/** Validates and normalizes MCP form and field input at the write boundary. */
final class McpFormInputNormalizer
{
    private ?int $requiredValidationId = null;

    public function __construct(private readonly DatabaseInterface $db)
    {
    }

    public function stringValue(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value) || is_bool($value)) {
            return (string) $value;
        }

        if ($value === null) {
            return '';
        }

        throw new ToolException('Expected a scalar text value.');
    }

    public function intValue(mixed $value): int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && preg_match('/^-?[0-9]+$/D', $value)) {
            return (int) $value;
        }

        throw new ToolException('Expected an integer value.');
    }

    /**
     * @return list<array{type: string, name: string, label: string, placeholder: string, hint: string, size: string, width: string, height: string, default_value: string, max_length: int, readonly: bool, options: list<array{label: string, value: string, selected: bool}>, multiple: bool, checked: bool, value: string, required: bool, required_message: string, page: int}>
     * @throws ToolException
     */
    public function normalizeFields(mixed $fields): array
    {
        if (!is_array($fields) || !array_is_list($fields) || count($fields) > 30) {
            throw new ToolException('Fields must be a list of at most 30 simple fields.');
        }

        $normalized = [];
        $names = [];
        foreach ($fields as $field) {
            if (!is_array($field)) {
                throw new ToolException('Each field must be an object.');
            }

            $type = $this->stringValue($field['type'] ?? '');
            $name = trim($this->stringValue($field['name'] ?? ''));
            $label = trim($this->stringValue($field['label'] ?? ''));
            $placeholder = trim($this->stringValue($field['placeholder'] ?? ''));
            $hint = $this->stringValue($field['hint'] ?? '');
            $size = $this->stringValue($field['size'] ?? '');
            $width = $this->stringValue($field['width'] ?? '');
            $height = $this->stringValue($field['height'] ?? '');
            $defaultValue = $this->stringValue($field['default_value'] ?? '');
            $maxLength = $field['max_length'] ?? 0;
            $readOnly = $field['readonly'] ?? false;
            if (!in_array($type, ['text', 'textarea', 'select', 'radio', 'checkbox_group', 'checkbox'], true)) {
                throw new ToolException('Supported field types are text, textarea, select, radio, checkbox_group and checkbox.');
            }
            if (
                mb_strlen($defaultValue) > 4000
                || (array_key_exists('default_value', $field) && !in_array($type, ['text', 'textarea'], true))
            ) {
                throw new ToolException('Default values are limited to 4000 characters and are only supported on text or textarea fields.');
            }
            $this->assertPlainText($defaultValue);
            if (mb_strlen($hint) > 1000) {
                throw new ToolException('Field hints must be plain text limited to 1000 characters.');
            }
            $hint = trim($hint);
            $this->assertPlainText($hint);
            foreach (['size' => $size, 'width' => $width, 'height' => $height] as $property => $dimension) {
                if (!array_key_exists($property, $field)) {
                    continue;
                }
                if (
                    ($property === 'size' && $type !== 'text')
                    || (in_array($property, ['width', 'height'], true) && $type !== 'textarea')
                ) {
                    throw new ToolException(sprintf('The %s setting is not supported on this field type.', $property));
                }
                $dimension = $this->normalizeCssDimension($dimension, $property);
                if ($property === 'size') {
                    $size = $dimension;
                } elseif ($property === 'width') {
                    $width = $dimension;
                } else {
                    $height = $dimension;
                }
            }
            if (
                !is_int($maxLength) || $maxLength < 0 || $maxLength > 10000
                || (array_key_exists('max_length', $field) && !in_array($type, ['text', 'textarea'], true))
            ) {
                throw new ToolException('Maximum length must be from 0 to 10000 and can only be set on text or textarea fields.');
            }
            if (!is_bool($readOnly) || (array_key_exists('readonly', $field) && !in_array($type, ['text', 'textarea'], true))) {
                throw new ToolException('Only text and textarea fields support the read-only setting.');
            }
            if (!preg_match('/^[A-Za-z][A-Za-z0-9_]{0,99}$/D', $name)) {
                throw new ToolException('Field names must start with a letter and contain only letters, numbers and underscores.');
            }
            if (preg_match('/^bfFakeName(?:[2-4])?$/iD', $name)) {
                throw new ToolException('That field name is reserved for a QuickMode submit control.');
            }
            if ($label === '' || mb_strlen($label) > 255 || mb_strlen($placeholder) > 255) {
                throw new ToolException('Field labels are required and field text is limited to 255 characters.');
            }
            $this->assertPlainText($label);
            $this->assertPlainText($placeholder);
            $multiple = $field['multiple'] ?? false;
            if (!is_bool($multiple) || (array_key_exists('multiple', $field) && $type !== 'select')) {
                throw new ToolException('Only select fields support the multiple setting.');
            }
            $checked = $field['checked'] ?? false;
            if (!is_bool($checked) || (array_key_exists('checked', $field) && $type !== 'checkbox')) {
                throw new ToolException('Only checkbox fields support the checked setting.');
            }
            $checkboxValue = array_key_exists('value', $field) ? $this->normalizeCheckboxValue($field['value']) : '1';
            if (array_key_exists('value', $field) && $type !== 'checkbox') {
                throw new ToolException('Only checkbox fields support a submitted value setting.');
            }
            $required = $field['required'] ?? false;
            if (
                !is_bool($required) || ((array_key_exists('required', $field) || array_key_exists('required_message', $field))
                && !in_array($type, ['text', 'textarea'], true))
            ) {
                throw new ToolException('Only text and textarea fields can use the required setting.');
            }
            $requiredMessage = $field['required_message'] ?? '';
            if (!is_string($requiredMessage) || mb_strlen($requiredMessage) > 255) {
                throw new ToolException('A required message must be plain text of at most 255 characters.');
            }
            $requiredMessage = trim($requiredMessage);
            if (array_key_exists('required_message', $field) && !$required) {
                throw new ToolException('A required message can only be set when the field is required.');
            }
            $this->assertPlainText($requiredMessage);
            if ($required) {
                $this->requiredValidationScriptId();
            }
            $allowMultipleSelections = $type === 'checkbox_group' || ($type === 'select' && $multiple);
            $choices = $this->normalizeChoices($field['options'] ?? [], $allowMultipleSelections);
            if (in_array($type, ['select', 'radio', 'checkbox_group'], true) && $choices === []) {
                throw new ToolException('Select, radio and checkbox_group fields need at least one choice.');
            }
            if (in_array($type, ['text', 'textarea', 'checkbox'], true) && $choices !== []) {
                throw new ToolException('Text, textarea and checkbox fields cannot have choice options.');
            }
            $page = $field['page'] ?? 1;
            if (!is_int($page) || $page < 1) {
                throw new ToolException('A field page must be a positive page number.');
            }

            $key = strtolower($name);
            if (isset($names[$key])) {
                throw new ToolException('Field names must be unique within the form.');
            }
            $names[$key] = true;
            $normalized[] = [
                'type' => $type,
                'name' => $name,
                'label' => $label,
                'placeholder' => $placeholder,
                'hint' => $hint,
                'size' => $size,
                'width' => $width,
                'height' => $height,
                'default_value' => $defaultValue,
                'max_length' => $maxLength,
                'readonly' => $readOnly,
                'options' => $choices,
                'multiple' => $multiple,
                'checked' => $checked,
                'value' => $checkboxValue,
                'required' => $required,
                'required_message' => $requiredMessage,
                'page' => $page,
            ];
        }

        return $normalized;
    }

    public function normalizePageIntro(mixed $intro): string
    {
        if (!is_string($intro) || mb_strlen($intro) > 1000) {
            throw new ToolException('Page intro text must be plain text limited to 1000 characters.');
        }

        $intro = trim($intro);
        if (strpbrk($intro, '<>{}') !== false) {
            throw new ToolException('Page intro text cannot contain markup or module/plugin syntax.');
        }

        return $intro;
    }

    /** @return list<array{label: string, value: string, selected: bool}> */
    public function normalizeChoices(mixed $choices, bool $allowMultipleSelections = true): array
    {
        if (!is_array($choices) || !array_is_list($choices) || count($choices) > 30) {
            throw new ToolException('Choice options must be a list of at most 30 items.');
        }

        $normalized = [];
        $values = [];
        $selectedCount = 0;
        foreach ($choices as $choice) {
            if (!is_array($choice)) {
                throw new ToolException('Each choice option must be an object.');
            }
            $label = trim($this->stringValue($choice['label'] ?? ''));
            $value = trim($this->stringValue($choice['value'] ?? ''));
            $selected = $choice['selected'] ?? false;
            if (!is_bool($selected)) {
                throw new ToolException('Choice selected flags must be boolean.');
            }
            $selectedCount += $selected ? 1 : 0;
            if (!$allowMultipleSelections && $selectedCount > 1) {
                throw new ToolException('Select fields and radio groups can have at most one initially selected choice.');
            }
            foreach ([$label, $value] as $text) {
                if ($text === '' || mb_strlen($text) > 255 || preg_match('/[;\r\n\x00]/u', $text)) {
                    throw new ToolException('Choice labels and values must be non-empty plain text of at most 255 characters without semicolons or line breaks.');
                }
                $this->assertPlainText($text);
            }
            if (isset($values[$value])) {
                throw new ToolException('Choice values must be unique within a field.');
            }
            $values[$value] = true;
            $normalized[] = ['label' => $label, 'value' => $value, 'selected' => $selected];
        }

        return $normalized;
    }

    /** @param list<array{label: string, value: string, selected: bool}> $choices */
    public function serializeChoices(array $choices): string
    {
        return implode('', array_map(static fn (array $choice): string => ($choice['selected'] ? '1;' : '0;') . $choice['label'] . ';' . $choice['value'] . "\n", $choices));
    }

    public function normalizeCheckboxValue(mixed $value): string
    {
        if (!is_string($value)) {
            throw new ToolException('A checkbox submitted value must be text.');
        }
        $value = trim($value);
        if ($value === '' || mb_strlen($value) > 255 || preg_match('/[\x00-\x1F\x7F]/u', $value)) {
            throw new ToolException('A checkbox submitted value must be non-empty text of at most 255 characters without control characters.');
        }
        $this->assertPlainText($value);

        return $value;
    }

    public function normalizeCssDimension(mixed $value, string $property): string
    {
        if (!is_string($value)) {
            throw new ToolException(sprintf('The %s setting must be a CSS dimension string.', $property));
        }

        $value = trim($value);
        if ($value !== '' && !preg_match('/^(?:[0-9]+(?:\.[0-9]+)?(?:px|%|em|rem|ch|vw|vh)|auto)$/iD', $value)) {
            throw new ToolException(sprintf('The %s setting must be a simple CSS dimension such as 240px, 80%%, 18rem or auto.', $property));
        }

        return $value;
    }

    public function assertPlainText(string $text): void
    {
        if (str_contains($text, '<') || str_contains($text, '>')) {
            throw new ToolException('Field text must be plain text without markup.');
        }
    }

    public function requiredValidationScriptId(): int
    {
        if ($this->requiredValidationId !== null) {
            return $this->requiredValidationId;
        }

        $scriptType = 'Element Validation';
        $scriptName = 'ff_valuenotempty';
        $query = $this->db->createQuery()
            ->select($this->db->quoteName('id'))
            ->from($this->db->quoteName('#__facileforms_scripts'))
            ->where($this->db->quoteName('published') . ' = 1')
            ->where($this->db->quoteName('type') . ' = :type')
            ->where($this->db->quoteName('name') . ' = :name')
            ->order($this->db->quoteName('id') . ' DESC')
            ->bind(':type', $scriptType, ParameterType::STRING)
            ->bind(':name', $scriptName, ParameterType::STRING);
        $id = $this->intValue($this->db->setQuery($query, 0, 1)->loadResult());
        if ($id <= 0) {
            throw new ToolException('The standard ff_valuenotempty validation is unavailable or unpublished.');
        }
        $this->requiredValidationId = $id;

        return $id;
    }
}
