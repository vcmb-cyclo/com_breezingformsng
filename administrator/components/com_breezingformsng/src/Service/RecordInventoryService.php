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
use Joomla\Database\ParameterType;

/**
 * Read-only inventory of the submitted records: how many each form holds,
 * by state. Records of a form that no longer exists are listed under its id.
 */
final class RecordInventoryService
{
    public function __construct(private readonly DatabaseInterface $db)
    {
    }

    /**
     * @return list<array{form: int, name: string, published: int, archived: int, trashed: int, total: int}>
     */
    public function getInventory(): array
    {
        $published = RecordState::PUBLISHED;
        $archived = RecordState::ARCHIVED;
        $trashed = RecordState::TRASHED;
        $state = $this->db->quoteName('r.state');

        $query = $this->db->createQuery()
            ->select($this->db->quoteName('r.form'))
            ->select('MAX(' . $this->db->quoteName('f.name') . ') AS ' . $this->db->quoteName('name'))
            ->select($this->countState($state, 'published'))
            ->select($this->countState($state, 'archived'))
            ->select($this->countState($state, 'trashed'))
            ->select('COUNT(*) AS ' . $this->db->quoteName('total'))
            ->from($this->db->quoteName('#__facileforms_records', 'r'))
            ->join('LEFT', $this->db->quoteName('#__facileforms_forms', 'f'), 'f.id = r.form')
            ->group($this->db->quoteName('r.form'))
            ->order($this->db->quoteName('r.form') . ' ASC')
            ->bind(':published', $published, ParameterType::INTEGER)
            ->bind(':archived', $archived, ParameterType::INTEGER)
            ->bind(':trashed', $trashed, ParameterType::INTEGER);

        return self::normalize($this->db->setQuery($query)->loadAssocList() ?: []);
    }

    /** Rows in the state bound to :$alias, as a column named $alias. */
    private function countState(string $state, string $alias): string
    {
        return 'SUM(CASE WHEN ' . $state . ' = :' . $alias . ' THEN 1 ELSE 0 END) AS '
            . $this->db->quoteName($alias);
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return list<array{form: int, name: string, published: int, archived: int, trashed: int, total: int}>
     */
    public static function normalize(array $rows): array
    {
        return array_map(static fn (array $row): array => [
            'form' => (int) ($row['form'] ?? 0),
            'name' => trim((string) ($row['name'] ?? '')),
            'published' => (int) ($row['published'] ?? 0),
            'archived' => (int) ($row['archived'] ?? 0),
            'trashed' => (int) ($row['trashed'] ?? 0),
            'total' => (int) ($row['total'] ?? 0),
        ], $rows);
    }
}
