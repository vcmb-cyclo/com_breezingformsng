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

namespace Vcmb\Component\BreezingformsNG\Site\Service\Rendering\Debug;

use Joomla\CMS\Date\Date;
use Joomla\CMS\Language\Text;
use Joomla\CMS\User\User;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use stdClass;
use Vcmb\Component\BreezingformsNG\Administrator\Model\FormModel;
use Vcmb\Component\BreezingformsNG\Administrator\Model\FormOptionsModel;
use Vcmb\Component\BreezingformsNG\Administrator\Service\FormVersioning;

/**
 * Saves what the debug bar of the site changes in a form: the places of fields, their
 * texts, whether they are required and their limits. Each save is one new version of
 * the form; nothing else of the form changes.
 *
 * @phpstan-import-type Changes from FieldEdit
 */
final class FieldWriter
{
    public const SAVED = 200;

    public const INVALID = 400;

    public const DENIED = 403;

    public const CHANGED = 409;

    public const LOCKED = 423;

    /** Columns of an element row, by the property of the tree they come from. */
    private const ROW_COLUMNS = [
        'label' => 'title',
        'validationCondition' => 'script3cond',
        'validationId' => 'script3id',
        'validationCode' => 'script3code',
        'validationMessage' => 'script3msg',
    ];

    /** Field whose changes were refused by the last save. */
    private string $refused = '';

    public function __construct(
        private readonly DatabaseInterface $db,
        private readonly FormOptionsModel $options,
        private readonly FormModel $forms
    ) {
    }

    public function refusedField(): string
    {
        return $this->refused;
    }

    /**
     * Writes the new places and the new settings of fields, all of them or none.
     *
     * @param list<array{field: string, target: string, after: bool}> $moves In the order they were made.
     * @param array<string, Changes>                                  $edits By field name.
     * @param string $note     Note of the version, written for the Super User when empty.
     * @param string $language Tag of the page, see FieldEdit.
     * @param string $revision Revision of the form the page was rendered from.
     *
     * @return self::SAVED|self::INVALID|self::DENIED|self::CHANGED|self::LOCKED
     */
    public function write(
        int $formId,
        array $moves,
        array $edits,
        string $note,
        string $language,
        string $revision,
        User $user
    ): int {
        $this->refused = '';
        $change = function (
            stdClass $form,
            stdClass $tree
        ) use (
            $formId,
            $moves,
            $edits,
            $note,
            $language,
            $user
        ): int {
            $former = FieldOrder::names($tree);

            if (!FieldOrder::moveAll($tree, $moves)) {
                return self::INVALID;
            }

            $new = FieldOrder::names($tree);
            $areas = (string) $form->template_areas;
            $fields = [];

            // Fields put back where they were change nothing.
            if ($new !== $former) {
                FieldOrder::renumber($tree, $former);
                $areas = FieldOrder::areas($areas, $former, $new);
                $fields = array_column($moves, 'field');
            }

            $elements = FieldOrder::elements($tree);
            $rows = [];

            foreach ($edits as $field => $edit) {
                $field = (string) $field;
                $properties = $elements[$field] ?? null;
                $changed = $properties === null ? null : FieldEdit::apply(
                    $properties,
                    $edit,
                    $language,
                    $edit['required'] === true ? $this->requiredCheckId() : 0,
                    Text::sprintf('COM_BREEZINGFORMSNG_DEBUG_BAR_REQUIRED_MESSAGE', strip_tags(trim($edit['label'])))
                );

                if ($changed === null) {
                    $this->refused = $field;

                    return self::INVALID;
                }

                if ($changed !== []) {
                    $areas = FieldOrder::editArea($areas, $field, $properties, $changed);
                    $rows[$field] = [$properties, $changed];
                    $fields[] = $field;
                }
            }

            // Nothing to write: no new version.
            if ($fields === []) {
                return self::SAVED;
            }

            $note = trim($note) !== '' ? trim($note) : Text::sprintf(
                'COM_BREEZINGFORMSNG_DEBUG_BAR_CHANGE_NOTE',
                implode(', ', array_unique($fields))
            );

            $this->writeVersion(
                $formId,
                $tree,
                $areas,
                (string) $user->username,
                // The note of a version holds 255 characters.
                mb_strimwidth($note, 0, 250, '…'),
                function () use ($formId, $former, $new, $rows): void {
                    if ($new !== $former) {
                        $this->writeOrder($formId, $former, $new);
                    }

                    foreach ($rows as $field => [$properties, $changed]) {
                        $this->writeRow($formId, (string) $field, $properties, $changed);
                    }
                }
            );

            return self::SAVED;
        };

        return $this->save($formId, $revision, $user, $change);
    }

    /**
     * Runs a change of a form that may be changed from the site, in one transaction.
     *
     * @param callable(stdClass, stdClass): (200|400) $change Receives the form row and its structure.
     *
     * @return self::SAVED|self::INVALID|self::DENIED|self::CHANGED|self::LOCKED
     */
    private function save(int $formId, string $revision, User $user, callable $change): int
    {
        $this->db->transactionStart(true);

        try {
            $status = $this->check($formId, $revision, $user, $change);

            if ($status === self::SAVED) {
                $this->db->transactionCommit(true);
            } else {
                $this->db->transactionRollback(true);
            }
        } catch (\Throwable $e) {
            $this->db->transactionRollback(true);

            throw $e;
        }

        return $status;
    }

    /**
     * @param callable(stdClass, stdClass): (200|400) $change
     *
     * @return self::SAVED|self::INVALID|self::DENIED|self::CHANGED|self::LOCKED
     */
    private function check(int $formId, string $revision, User $user, callable $change): int
    {
        // Locked row: two changes cannot both start from the same state.
        $form = $this->options->getForm($formId, true);

        if (
            $form === null || (int) $form->published === -2
            || !FormDebugContext::isActive($form->debug_mode ?? 0, true, $user->authorise('core.admin'))
        ) {
            return self::DENIED;
        }

        // Open in the editor: its next save would meet a changed form.
        if ((int) ($form->checked_out ?? 0) > 0) {
            return self::LOCKED;
        }

        if (!hash_equals(FormOptionsModel::quickmodeRevision($form), $revision)) {
            return self::CHANGED;
        }

        $tree = json_decode((string) base64_decode((string) $form->template_code, true));

        if (!$tree instanceof stdClass) {
            return self::INVALID;
        }

        $names = FieldOrder::names($tree);

        // A name used twice would not tell which field is meant.
        return count($names) === count(array_unique($names)) ? $change($form, $tree) : self::INVALID;
    }

    /** @param callable(): void $writeElements */
    private function writeVersion(
        int $formId,
        stdClass $tree,
        string $areas,
        string $username,
        string $note,
        callable $writeElements
    ): void {
        $db = $this->db;
        $versioning = new FormVersioning($db, $this->forms);
        $versioning->keepOrigin($formId);

        $code = base64_encode((string) json_encode($tree));
        $now = (new Date())->toSql();

        $db->setQuery(
            $db->createQuery()
                ->update($db->quoteName('#__facileforms_forms'))
                ->set($db->quoteName('template_code') . ' = :code')
                ->set($db->quoteName('template_areas') . ' = :areas')
                ->set($db->quoteName('modified') . ' = :modified')
                ->set($db->quoteName('modified_by') . ' = :modifiedBy')
                ->where($db->quoteName('id') . ' = :id')
                ->bind(':code', $code)
                ->bind(':areas', $areas)
                ->bind(':modified', $now)
                ->bind(':modifiedBy', $username)
                ->bind(':id', $formId, ParameterType::INTEGER)
        )->execute();
        $writeElements();
        $versioning->record($formId, $note);
    }

    /**
     * @param list<string> $former
     * @param list<string> $new
     */
    private function writeOrder(int $formId, array $former, array $new): void
    {
        $db = $this->db;
        $rows = $db->setQuery(
            $db->createQuery()
                ->select($db->quoteName(['id', 'name', 'ordering']))
                ->from($db->quoteName('#__facileforms_elements'))
                ->where($db->quoteName('form') . ' = :form')
                ->bind(':form', $formId, ParameterType::INTEGER)
        )->loadObjectList();
        $ids = [];
        $positions = [];

        foreach ($rows as $row) {
            $ids[(string) $row->name] = (int) $row->id;
            $positions[(string) $row->name] = (int) $row->ordering;
        }

        foreach (FieldOrder::reassign($positions, $former, $new) as $name => $position) {
            if ($position === $positions[$name]) {
                continue;
            }

            $id = $ids[$name];
            $ordering = (int) $position;
            $db->setQuery(
                $db->createQuery()
                    ->update($db->quoteName('#__facileforms_elements'))
                    ->set($db->quoteName('ordering') . ' = :ordering')
                    ->where($db->quoteName('id') . ' = :id')
                    ->bind(':ordering', $ordering, ParameterType::INTEGER)
                    ->bind(':id', $id, ParameterType::INTEGER)
            )->execute();
        }
    }

    /** @param list<string> $changed */
    private function writeRow(int $formId, string $field, stdClass $properties, array $changed): void
    {
        $db = $this->db;
        $columns = array_intersect_key(self::ROW_COLUMNS, array_flip($changed));

        if ($columns === []) {
            return;
        }

        $query = $db->createQuery()->update($db->quoteName('#__facileforms_elements'));
        $values = [];

        foreach ($columns as $property => $column) {
            $values[$column] = (string) $properties->{$property};
            $query->set($db->quoteName($column) . ' = :' . $column)->bind(':' . $column, $values[$column]);
        }

        $db->setQuery(
            $query->where($db->quoteName('form') . ' = :form')
                ->where($db->quoteName('name') . ' = :name')
                ->bind(':form', $formId, ParameterType::INTEGER)
                ->bind(':name', $field)
        )->execute();
    }

    private function requiredCheckId(): int
    {
        $db = $this->db;
        $name = FieldEdit::REQUIRED_CHECK;
        $type = 'Element Validation';

        return (int) $db->setQuery(
            $db->createQuery()
                ->select('MAX(' . $db->quoteName('id') . ')')
                ->from($db->quoteName('#__facileforms_scripts'))
                ->where($db->quoteName('published') . ' = 1')
                ->where($db->quoteName('type') . ' = :type')
                ->where($db->quoteName('name') . ' = :name')
                ->bind(':type', $type)
                ->bind(':name', $name)
        )->loadResult();
    }
}
