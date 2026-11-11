<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Vcmb\Component\BreezingformsNG\Administrator\Mcp;

\defined('_JEXEC') or die;

/**
 * Checks the arguments of a tool call against the schema of the tool, and
 * returns them with the defaults of the schema filled in.
 *
 * It knows the subset of JSON Schema the tools use: an object with
 * properties of type string, integer, boolean or array of strings or
 * integers, with required, enum, minimum, maximum, minLength, maxLength,
 * maxItems and default, and no other property.
 */
final class ArgumentValidator
{
    /**
     * @param array<string, mixed> $schema
     * @return array<string, mixed>
     * @throws \InvalidArgumentException naming the first argument that does not match.
     */
    public static function validate(mixed $arguments, array $schema): array
    {
        $arguments ??= [];
        if (!is_array($arguments) || (array_is_list($arguments) && $arguments !== [])) {
            throw new \InvalidArgumentException('The arguments must be an object.');
        }

        /** @var array<string, array<string, mixed>> $properties */
        $properties = $schema['properties'] ?? [];
        foreach (array_keys($arguments) as $name) {
            if (!isset($properties[$name])) {
                throw new \InvalidArgumentException(sprintf('Unknown argument "%s".', $name));
            }
        }

        foreach ((array) ($schema['required'] ?? []) as $name) {
            if (!array_key_exists($name, $arguments)) {
                throw new \InvalidArgumentException(sprintf('The argument "%s" is required.', $name));
            }
        }

        $values = [];
        foreach ($properties as $name => $property) {
            if (array_key_exists($name, $arguments)) {
                $values[$name] = self::value($name, $arguments[$name], $property);
            } elseif (array_key_exists('default', $property)) {
                $values[$name] = $property['default'];
            }
        }

        return $values;
    }

    /**
     * @param array<string, mixed> $property
     */
    private static function value(string $name, mixed $value, array $property): mixed
    {
        $type = $property['type'] ?? null;
        $valid = match ($type) {
            'string' => is_string($value),
            // A JSON number without a fraction, as 5 or 5.0.
            'integer' => is_int($value) || (is_float($value) && floor($value) === $value && abs($value) <= PHP_INT_MAX),
            'boolean' => is_bool($value),
            'array' => is_array($value) && array_is_list($value),
            default => false,
        };
        if (!$valid) {
            throw new \InvalidArgumentException(sprintf('The argument "%s" must be of type %s.', $name, (string) $type));
        }

        if ($type === 'integer') {
            $value = (int) $value;
            if (isset($property['minimum']) && $value < $property['minimum']) {
                throw new \InvalidArgumentException(sprintf('The argument "%s" must be at least %d.', $name, $property['minimum']));
            }
            if (isset($property['maximum']) && $value > $property['maximum']) {
                throw new \InvalidArgumentException(sprintf('The argument "%s" must be at most %d.', $name, $property['maximum']));
            }
        }

        if ($type === 'string') {
            if (!mb_check_encoding($value, 'UTF-8')) {
                throw new \InvalidArgumentException(sprintf('The argument "%s" must be UTF-8 text.', $name));
            }
            $length = mb_strlen($value);
            if (isset($property['minLength']) && $length < $property['minLength']) {
                throw new \InvalidArgumentException(sprintf('The argument "%s" must have at least %d characters.', $name, $property['minLength']));
            }
            if (isset($property['maxLength']) && $length > $property['maxLength']) {
                throw new \InvalidArgumentException(sprintf('The argument "%s" must have at most %d characters.', $name, $property['maxLength']));
            }
        }

        if (isset($property['enum']) && !in_array($value, $property['enum'], true)) {
            throw new \InvalidArgumentException(sprintf('The argument "%s" must be one of: %s.', $name, implode(', ', $property['enum'])));
        }

        if ($type === 'array') {
            if (isset($property['maxItems']) && count($value) > $property['maxItems']) {
                throw new \InvalidArgumentException(sprintf('The argument "%s" must have at most %d items.', $name, $property['maxItems']));
            }
            /** @var array<string, mixed> $items */
            $items = $property['items'] ?? [];
            foreach ($value as $index => $item) {
                $value[$index] = self::value($name . '[' . $index . ']', $item, $items);
            }
        }

        return $value;
    }
}
