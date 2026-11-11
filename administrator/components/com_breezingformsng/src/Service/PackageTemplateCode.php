<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Vcmb\Component\BreezingformsNG\Administrator\Service;

\defined('_JEXEC') or die;

/**
 * The template code of a form in a package file.
 *
 * The database stores it base64-encoded. A package file holds it in clear, so
 * that it can be read and compared: the QuickMode structure as JSON, any other
 * code as text. It is kept as stored only when clear text cannot hold it
 * exactly, which the file says with STORED: a value that is not base64, or
 * whose content is not UTF-8 text, which JSON cannot carry.
 */
final class PackageTemplateCode
{
    /** The encoding of a template code kept as the database stores it. */
    public const STORED = 'stored';

    /**
     * @return array{0: string|array<mixed>, 1: ?string} The value for the file, and its encoding: null when in clear.
     */
    public static function toFile(string $stored): array
    {
        if ($stored === '') {
            return ['', null];
        }
        $code = base64_decode($stored, true);
        // Decoded and encoded again, it must give back the stored value exactly.
        if ($code === false || base64_encode($code) !== $stored || !mb_check_encoding($code, 'UTF-8')) {
            return [$stored, self::STORED];
        }
        // As QuickMode writes it: a structure only when it is written back the same.
        $structure = json_decode($code, true);
        if (is_array($structure) && json_encode($structure) === $code) {
            return [$structure, null];
        }

        return [$code, null];
    }

    /**
     * @param mixed $value The value of the file.
     * @throws \UnexpectedValueException for a value the file cannot hold.
     */
    public static function fromFile(mixed $value, mixed $encoding): string
    {
        if ($encoding === self::STORED && is_string($value)) {
            return $value;
        }
        if ($encoding !== null) {
            throw new \UnexpectedValueException('Unknown template code encoding.');
        }
        if (is_array($value)) {
            return base64_encode((string) json_encode($value));
        }
        if (is_string($value)) {
            return $value === '' ? '' : base64_encode($value);
        }

        throw new \UnexpectedValueException('Invalid template code.');
    }

    /**
     * Points the QuickMode template code of an imported form to the ids of this
     * site: its elements, and the scripts their validation, init and action use.
     * QuickMode writes these ids back into the elements at each save.
     *
     * An element whose id the file does not hold, as one left by a copy of the
     * form, is found by its name, as QuickMode does at the next save.
     *
     * @param array<int, int>    $elementIds    New id of each element, by its id in the file.
     * @param array<int, int>    $scriptIds     New id of each script, by its id in the file.
     * @param array<string, int> $elementByName New id of each element, by its name.
     * @return string The stored value, unchanged when nothing points elsewhere.
     */
    public static function remapIds(string $stored, array $elementIds, array $scriptIds, array $elementByName = []): string
    {
        $code = $stored === '' ? false : base64_decode($stored, true);
        $structure = $code === false ? null : json_decode($code, true);
        if (!is_array($structure)) {
            return $stored;
        }

        $changed = false;
        $structure = self::remapNode($structure, $elementIds, $scriptIds, $elementByName, $changed);

        return $changed ? base64_encode((string) json_encode($structure)) : $stored;
    }

    /**
     * @param array<mixed>    $node
     * @param array<int, int> $elementIds
     * @param array<int, int> $scriptIds
     * @param array<string, int> $elementByName
     * @return array<mixed>
     */
    private static function remapNode(array $node, array $elementIds, array $scriptIds, array $elementByName, bool &$changed): array
    {
        if (is_array($node['properties'] ?? null) && ($node['properties']['type'] ?? null) === 'element') {
            $properties = $node['properties'];
            $map = ['dbId' => $elementIds, 'validationId' => $scriptIds, 'initId' => $scriptIds, 'actionId' => $scriptIds];
            foreach ($map as $key => $ids) {
                $old = $properties[$key] ?? null;
                if ((is_int($old) || (is_string($old) && ctype_digit($old))) && isset($ids[(int) $old]) && $ids[(int) $old] !== (int) $old) {
                    $properties[$key] = $ids[(int) $old];
                    $changed = true;
                }
            }
            $dbId = $properties['dbId'] ?? null;
            $name = $properties['bfName'] ?? null;
            if (
                (is_int($dbId) || (is_string($dbId) && ctype_digit($dbId))) && (int) $dbId > 0 && !isset($elementIds[(int) $dbId])
                && is_string($name) && isset($elementByName[$name]) && $elementByName[$name] !== (int) $dbId
            ) {
                $properties['dbId'] = $elementByName[$name];
                $changed = true;
            }
            $node['properties'] = $properties;
        }

        if (is_array($node['children'] ?? null)) {
            foreach ($node['children'] as $key => $child) {
                if (is_array($child)) {
                    $node['children'][$key] = self::remapNode($child, $elementIds, $scriptIds, $elementByName, $changed);
                }
            }
        }

        return $node;
    }
}
