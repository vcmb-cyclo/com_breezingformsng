<?php

/**
 * BreezingForms NG - A Joomla Forms Application
 *
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 *
 * SPDX-License-Identifier: GPL-2.0-or-later
 **/

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator\Support;

use Joomla\Database\DatabaseInterface;

/**
 * Records every query the model builds and answers them from an ordered list
 * of ids (the filtered list, in list order), without loading the whole list:
 * the current row, the list size, the rows ahead and the two neighbours, in
 * the order getNavigation() runs them (previous before next).
 */
final class RecordsNavigationDatabaseDouble implements DatabaseInterface
{
    /** @var list<RecordsNavigationQueryDouble> */
    public array $queries = [];

    private ?RecordsNavigationQueryDouble $pending = null;

    private int $neighbourCalls = 0;

    /**
     * @param list<int>          $recordIds  Ids of the filtered list, in list order.
     * @param array<int, ?string> $sortValues Sort value per id (default: the id).
     */
    public function __construct(private readonly array $recordIds, private readonly array $sortValues = [])
    {
    }

    public function createQuery(): RecordsNavigationQueryDouble
    {
        return $this->queries[] = new RecordsNavigationQueryDouble();
    }

    /** The count query that only carries the list filters. */
    public function filterQuery(): RecordsNavigationQueryDouble
    {
        return $this->queries[1];
    }

    public function lastQuery(): RecordsNavigationQueryDouble
    {
        return $this->queries[array_key_last($this->queries)];
    }

    public function quoteName(string|array $name, ?string $as = null): string|array
    {
        return $as === null ? $name : $name . ' AS ' . $as;
    }

    public function setQuery(object $query, int $offset = 0, int $limit = 0): self
    {
        \assert($query instanceof RecordsNavigationQueryDouble);
        $query->limit = $limit;
        $this->pending = $query;

        return $this;
    }

    /** @return ?array{id: int, nav_value: ?string} */
    public function loadAssoc(): ?array
    {
        $index = array_search($this->boundId(), $this->recordIds, true);

        if ($index === false) {
            return null;
        }

        $id = $this->recordIds[$index];

        return ['id' => $id, 'nav_value' => array_key_exists($id, $this->sortValues) ? $this->sortValues[$id] : (string) $id];
    }

    public function loadResult(): int|string|null
    {
        $query = $this->pending;
        $index = array_search($this->boundId(), $this->recordIds, true);

        if ($query === null) {
            return null;
        }

        if (in_array('COUNT(*)', $query->select, true)) {
            return $index === false ? count($this->recordIds) : $index;
        }

        $offset = $this->neighbourCalls++ === 0 ? -1 : 1;

        return $index === false ? null : ($this->recordIds[$index + $offset] ?? null);
    }

    private function boundId(): ?int
    {
        foreach ($this->pending->bindings ?? [] as [$key, $value]) {
            if ($key === ':navId') {
                return $value;
            }
        }

        return null;
    }
}
