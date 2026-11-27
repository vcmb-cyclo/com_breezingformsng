<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Vcmb\Component\BreezingformsNG\Administrator\Mcp;

\defined('_JEXEC') or die;

/** Safely reads and writes the small, legacy QuickMode visibility-rule grammar. */
final class McpVisibilityRules
{
    private const RULE = '/^turn (on|off) element ([A-Za-z][A-Za-z0-9_]{0,99}) if '
        . '([A-Za-z][A-Za-z0-9_]{0,99}) (is|isnot) (.+)$/D';

    /**
     * @param list<array<string, mixed>> $rules
     * @param list<string>                $fieldNames
     * @throws ToolException
     */
    public static function serialize(array $rules, array $fieldNames): string
    {
        $fields = self::fields($fieldNames);
        $lines = [];

        foreach ($rules as $rule) {
            $target = self::field($rule['target'] ?? null, $fields, 'target');
            $source = self::field($rule['source'] ?? null, $fields, 'source');
            if ($target === $source) {
                throw new ToolException('A visibility rule must not use the same field as its source and target.');
            }
            $operator = $rule['operator'] ?? null;
            if (!is_string($operator) || !in_array($operator, ['equals', 'not_equals'], true)) {
                throw new ToolException('A visibility-rule operator must be equals or not_equals.');
            }
            if (!is_bool($rule['visible'] ?? null)) {
                throw new ToolException('A visibility rule must say whether its target is visible.');
            }
            $value = self::value($rule['value'] ?? null);
            $lines[] = sprintf(
                'turn %s element %s if %s %s %s',
                $rule['visible'] ? 'on' : 'off',
                $target,
                $source,
                $operator === 'equals' ? 'is' : 'isnot',
                $value
            );
        }

        return implode("\n", $lines);
    }

    /**
     * @param list<string> $fieldNames
     * @return array{rules: list<array{target: string, source: string, operator: string, value: string, visible: bool}>,
     *                supported: bool}
     */
    public static function read(mixed $storedRules, array $fieldNames): array
    {
        if (!is_string($storedRules) || trim($storedRules) === '') {
            return ['rules' => [], 'supported' => true];
        }

        $fields = self::fields($fieldNames);
        $rules = [];
        foreach (explode("\n", str_replace("\r", '', $storedRules)) as $line) {
            if (
                preg_match(
                    self::RULE,
                    trim($line),
                    $matches
                ) !== 1
            ) {
                return ['rules' => [], 'supported' => false];
            }

            try {
                $target = self::field($matches[2], $fields, 'target');
                $source = self::field($matches[3], $fields, 'source');
                $value = self::value($matches[5]);
            } catch (ToolException) {
                return ['rules' => [], 'supported' => false];
            }
            if ($target === $source) {
                return ['rules' => [], 'supported' => false];
            }
            $rules[] = [
                'target' => $target,
                'source' => $source,
                'operator' => $matches[4] === 'is' ? 'equals' : 'not_equals',
                'value' => $value,
                'visible' => $matches[1] === 'on',
            ];
        }

        return ['rules' => $rules, 'supported' => true];
    }

    /**
     * @param list<string> $fieldNames
     * @return array<string, string>
     */
    private static function fields(array $fieldNames): array
    {
        $fields = [];
        foreach ($fieldNames as $name) {
            if (is_string($name) && preg_match('/^[A-Za-z][A-Za-z0-9_]{0,99}$/D', $name) === 1) {
                $fields[strtolower($name)] = $name;
            }
        }

        return $fields;
    }

    /** @param array<string, string> $fields @throws ToolException */
    private static function field(mixed $name, array $fields, string $role): string
    {
        if (!is_string($name) || !isset($fields[strtolower($name)])) {
            throw new ToolException(sprintf('A visibility-rule %s must name an existing field.', $role));
        }

        return $fields[strtolower($name)];
    }

    /** @throws ToolException */
    private static function value(mixed $value): string
    {
        if (!is_string($value)) {
            throw new ToolException('A visibility-rule value must be text.');
        }
        $value = trim($value);
        if (
            $value === '' || mb_strlen($value) > 255 || preg_match('/[\x00-\x1F\x7F]/u', $value) === 1
            || strpbrk($value, '<>{}') !== false
        ) {
            throw new ToolException(
                'A visibility-rule value must be plain single-line text of at most 255 characters.'
            );
        }

        return $value;
    }
}
