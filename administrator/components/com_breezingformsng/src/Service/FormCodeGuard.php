<?php

/**
 * BreezingForms NG - A Joomla Forms Application
 *
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 *
 * SPDX-License-Identifier: GPL-2.0-or-later
 **/

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Administrator\Service;

\defined('_JEXEC') or die;

/**
 * Keeps the code of a form (the PHP pieces and the scripts run on the site)
 * away from users who may edit forms but do not hold the "code" permission.
 *
 * Three levels are guarded: the form itself (apply()), the elements of a form
 * (applyToElement()) and the QuickMode editor tree, which carries both plus the
 * file path of a payment download (applyToTree()). A change is never stored:
 * what the form already holds is kept, and the changed columns are reported so
 * the caller can warn.
 */
final class FormCodeGuard
{
    /** The columns of a form that hold code run by the site. */
    public const CODE_COLUMNS = [
        'script1code', 'script2code',
        'piece1code', 'piece2code', 'piece3code', 'piece4code',
    ];

    /**
     * The columns of an element that hold code run in the browser. Choosing an existing library
     * script (the cond and id columns) or the message of a validation writes no code: not guarded.
     */
    public const ELEMENT_CODE_COLUMNS = ['script1code', 'script2code', 'script3code'];

    /** The option of a payment element that names a file of the server to hand out. */
    public const FILEPATH_OPTION = 'filepath';

    /** The properties of an element of the QuickMode tree, by the column they are stored in. */
    private const ELEMENT_TREE_PROPERTIES = [
        'validationCode' => 'script3code', 'initCode' => 'script1code', 'actionCode' => 'script2code',
    ];

    /**
     * @param array<string, mixed> $data     What the user sent
     * @param object|null          $existing The form as stored, null for a new form
     *
     * @return array{data: array<string, mixed>, ignored: list<string>} The data to store,
     *         and the code columns whose change was not kept
     */
    public function apply(array $data, ?object $existing, bool $mayEditCode): array
    {
        if ($mayEditCode) {
            return ['data' => $data, 'ignored' => []];
        }

        $ignored = [];

        foreach (self::CODE_COLUMNS as $column) {
            $stored = $existing === null ? '' : (string) ($existing->$column ?? '');

            // A field the screen did not post arrives as null: that is no change.
            if (($data[$column] ?? null) !== null && $this->normalise((string) $data[$column]) !== $this->normalise($stored)) {
                $ignored[] = $column;
            }

            // An existing form keeps its code; a new form starts without any.
            if ($existing === null) {
                $data[$column] = '';
            } else {
                unset($data[$column]);
            }
        }

        return ['data' => $data, 'ignored' => $ignored];
    }

    /**
     * The code columns of one element row (the choice of a library script is the user's), as the element is about to be stored.
     *
     * @param array<string, mixed>      $row    The element as it would be stored
     * @param array<string, mixed>|null $stored The element as stored, null for a new element
     *
     * @return array{data: array<string, mixed>, ignored: list<string>} The row with the stored
     *         code, and the columns whose change was not kept
     */
    public function applyToElement(array $row, ?array $stored, bool $mayEditCode): array
    {
        if ($mayEditCode) {
            return ['data' => $row, 'ignored' => []];
        }

        $ignored = [];

        foreach (self::ELEMENT_CODE_COLUMNS as $column) {
            $kept = (string) ($stored[$column] ?? '');

            if (!$this->sameElementValue($row[$column] ?? null, $kept)) {
                $ignored[] = $column;
            }

            $row[$column] = $kept;
        }

        return ['data' => $row, 'ignored' => $ignored];
    }

    /**
     * The QuickMode tree posted by the editor, with every guarded value put back
     * to what is stored: the code of the elements, the file path of a payment
     * download, and the script run after the form is submitted.
     *
     * The tree is saved with the form and read again by the editor, so a value
     * left in it would be written to the elements by the next save of a user
     * who may edit code.
     *
     * @param array<string, mixed>            $tree        What the editor sent
     * @param array<int, array<string, mixed>> $elements   The code columns of the stored elements, by element id
     * @param array<string, mixed>|null       $form        The script2cond and script2code of the stored form, null for a new form
     * @param array<int, string>              $filepaths   The stored file path of each payment element, by element id
     *
     * @return array{tree: array<string, mixed>, ignored: list<string>}
     */
    public function applyToTree(array $tree, array $elements, ?array $form, array $filepaths, bool $mayEditCode): array
    {
        if ($mayEditCode) {
            return ['tree' => $tree, 'ignored' => []];
        }

        $ignored = [];
        $tree = $this->guardNode($tree, $elements, $form, $filepaths, $ignored);

        return ['tree' => $tree, 'ignored' => array_values(array_unique($ignored))];
    }

    /**
     * The stored areas of a form (the elements as QuickMode saves them, with their options), with the
     * file path of each payment download put back to the one the element of that name had, or none.
     * For an imported form, whose element ids are not those of the file path it came with.
     *
     * @param array<string, string> $filepathsByName
     *
     * @return string The JSON to store, the one given when no file path was found or none changes
     */
    public function applyToAreas(string $areas, array $filepathsByName): string
    {
        $decoded = json_decode($areas, true);

        if (!is_array($decoded) || !is_array($decoded[0]['elements'] ?? null)) {
            return $areas;
        }

        $changed = false;

        foreach ($decoded[0]['elements'] as $position => $element) {
            if (!is_array($element) || !is_array($element['options'] ?? null) || !array_key_exists(self::FILEPATH_OPTION, $element['options'])) {
                continue;
            }

            $kept = $filepathsByName[(string) ($element['name'] ?? '')] ?? '';

            if ($element['options'][self::FILEPATH_OPTION] !== $kept) {
                $decoded[0]['elements'][$position]['options'][self::FILEPATH_OPTION] = $kept;
                $changed = true;
            }
        }

        return $changed ? (string) json_encode($decoded) : $areas;
    }

    /**
     * @param array<string, mixed>            $node
     * @param array<int, array<string, mixed>> $elements
     * @param array<string, mixed>|null       $form
     * @param array<int, string>              $filepaths
     * @param list<string>                    $ignored
     *
     * @return array<string, mixed>
     */
    private function guardNode(array $node, array $elements, ?array $form, array $filepaths, array &$ignored): array
    {
        if (is_array($node['properties'] ?? null)) {
            $properties = $node['properties'];
            $type       = $properties['type'] ?? null;

            if ($type === 'element') {
                $id = (int) ($properties['dbId'] ?? 0);

                foreach (self::ELEMENT_TREE_PROPERTIES as $property => $column) {
                    $kept = (string) ($elements[$id][$column] ?? '');

                    if (!$this->sameElementValue($properties[$property] ?? null, $kept)) {
                        $ignored[] = $column;
                    }

                    $properties[$property] = $kept;
                }

                if (array_key_exists(self::FILEPATH_OPTION, $properties)) {
                    $kept = $filepaths[$id] ?? '';

                    if ($properties[self::FILEPATH_OPTION] !== null && (string) $properties[self::FILEPATH_OPTION] !== $kept) {
                        $ignored[] = self::FILEPATH_OPTION;
                    }

                    $properties[self::FILEPATH_OPTION] = $kept;
                }
            } elseif ($type === 'root' && (int) ($properties['submittedScriptCondidtion'] ?? -1) !== -1) {
                // -1 is "do not write the script run after the submission": nothing to guard.
                // The condition alone selects nothing new; the code is the stored one.
                $keptCode = (string) ($form['script2code'] ?? '');

                if ($this->normalise((string) ($properties['submittedScriptCode'] ?? '')) !== $this->normalise($keptCode)) {
                    $ignored[] = 'script2code';
                }

                $properties['submittedScriptCode'] = $keptCode;
            }

            $node['properties'] = $properties;
        }

        if (is_array($node['children'] ?? null)) {
            foreach ($node['children'] as $position => $child) {
                if (is_array($child)) {
                    $node['children'][$position] = $this->guardNode($child, $elements, $form, $filepaths, $ignored);
                }
            }
        }

        return $node;
    }

    /** Absent or null is no change, as for the form: the screen did not post it. */
    private function sameElementValue(mixed $posted, string $kept): bool
    {
        return $posted === null || $this->normalise((string) $posted) === $this->normalise($kept);
    }

    /** A browser posts line breaks as CRLF: the same code is not a change. */
    private function normalise(string $code): string
    {
        return str_replace("\r\n", "\n", $code);
    }
}
