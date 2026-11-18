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

namespace Vcmb\Component\BreezingformsNG\Administrator\Service;

\defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;

/**
 * What the trash holds besides the submitted records: forms, scripts, pieces and
 * Integrator rules whose published state is -2, with the list that shows them.
 */
final class TrashInventoryService
{
    /** The trashed state of an item of the lists (the Joomla value). */
    public const TRASHED = -2;

    /** Language key of the type => [table, list view]. */
    public const TYPES = [
        'COM_BREEZINGFORMSNG_MANAGEFORMS' => ['#__facileforms_forms', 'forms'],
        'COM_BREEZINGFORMSNG_MANAGESCRIPTS' => ['#__facileforms_scripts', 'scripts'],
        'COM_BREEZINGFORMSNG_MANAGEPIECES' => ['#__facileforms_pieces', 'pieces'],
        'COM_BREEZINGFORMSNG_INTEGRATOR' => ['#__facileforms_integrator_rules', 'integrator'],
    ];

    /** How many trashed items of a type the screen names. */
    public const LIST_LIMIT = 200;

    public function __construct(private readonly DatabaseInterface $db)
    {
    }

    /**
     * Only the types that have something in the trash.
     *
     * @return list<array{label: string, view: string, count: int}>
     */
    public function getTrashedItems(): array
    {
        $items = [];

        foreach (self::TYPES as $label => [$table, $view]) {
            $trashed = self::TRASHED;
            $query = $this->db->createQuery()
                ->select('COUNT(*)')
                ->from($this->db->quoteName($table))
                ->where($this->db->quoteName('published') . ' = :trashed')
                ->bind(':trashed', $trashed, \Joomla\Database\ParameterType::INTEGER);
            $count = (int) $this->db->setQuery($query)->loadResult();

            if ($count > 0) {
                $items[] = ['label' => $label, 'view' => $view, 'count' => $count];
            }
        }

        return $items;
    }

    /**
     * The trashed items of each type, by name, for the Trash screen. Only the
     * types that have something in the trash.
     *
     * @return list<array{view: string, label: string, count: int, items: list<array{id: int, title: string, name: string}>}>
     */
    public function getTrashedByType(): array
    {
        $types = [];

        foreach (self::TYPES as $label => [$table, $view]) {
            $count = $this->countTrashed($table);

            if ($count === 0) {
                continue;
            }

            $db = $this->db;
            $trashed = self::TRASHED;
            $query = $db->createQuery();

            if ($view === 'integrator') {
                $query->select([$db->quoteName('t.id'), $db->quoteName('t.name', 'title'), $db->quoteName('f.title', 'name')])
                    ->from($db->quoteName($table, 't'))
                    ->join('LEFT', $db->quoteName('#__facileforms_forms', 'f') . ' ON f.id = t.form_id');
            } else {
                $query->select([$db->quoteName('t.id'), $db->quoteName('t.title'), $db->quoteName('t.name')])
                    ->from($db->quoteName($table, 't'));
            }

            $query->where($db->quoteName('t.published') . ' = :trashed')
                ->bind(':trashed', $trashed, \Joomla\Database\ParameterType::INTEGER)
                ->order($db->quoteName('t.id'))
                ->setLimit(self::LIST_LIMIT);
            $items = array_values(array_map(static fn (array $row): array => [
                'id' => (int) $row['id'],
                'title' => (string) ($row['title'] ?? ''),
                'name' => (string) ($row['name'] ?? ''),
            ], $db->setQuery($query)->loadAssocList() ?: []));

            $types[] = ['view' => $view, 'label' => $label, 'count' => $count, 'items' => $items];
        }

        return $types;
    }

    /**
     * Ids of every trashed item of a type, whatever the limit of the screen.
     *
     * @return list<int>
     */
    public function getTrashedIds(string $view): array
    {
        foreach (self::TYPES as [$table, $typeView]) {
            if ($typeView !== $view) {
                continue;
            }

            $trashed = self::TRASHED;
            $query = $this->db->createQuery()
                ->select($this->db->quoteName('id'))
                ->from($this->db->quoteName($table))
                ->where($this->db->quoteName('published') . ' = :trashed')
                ->bind(':trashed', $trashed, \Joomla\Database\ParameterType::INTEGER)
                ->order($this->db->quoteName('id'));

            return array_values(array_map('intval', $this->db->setQuery($query)->loadColumn() ?: []));
        }

        return [];
    }

    private function countTrashed(string $table): int
    {
        $trashed = self::TRASHED;
        $query = $this->db->createQuery()
            ->select('COUNT(*)')
            ->from($this->db->quoteName($table))
            ->where($this->db->quoteName('published') . ' = :trashed')
            ->bind(':trashed', $trashed, \Joomla\Database\ParameterType::INTEGER);

        return (int) $this->db->setQuery($query)->loadResult();
    }
}
