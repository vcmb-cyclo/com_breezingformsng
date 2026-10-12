<?php

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator\Support;

use Joomla\Database\QueryInterface;

final class RecordsNavigationQueryDouble implements QueryInterface
{
    /** @var list<string> */
    public array $where = [];

    /** @var list<string> */
    public array $whereGlue = [];

    /** @var list<array{string, mixed, mixed}> */
    public array $bindings = [];

    public string $order = '';

    public function select(mixed $columns): self
    {
        return $this;
    }

    public function from(string $table, ?string $alias = null): self
    {
        return $this;
    }

    public function join(string $type, string $conditions): self
    {
        return $this;
    }

    public function order(string $ordering): self
    {
        $this->order = $ordering;

        return $this;
    }

    public function where(string|array $condition, string $glue = 'AND'): self
    {
        $this->whereGlue[] = $glue;
        foreach ((array) $condition as $item) {
            $this->where[] = $item;
        }

        return $this;
    }

    public function whereIn(string $column, array $values, mixed $type = null): self
    {
        return $this->where($column . ' IN (' . implode(',', $values) . ')');
    }

    public function extendWhere(string $outerGlue, array $conditions, string $innerGlue = 'AND'): self
    {
        $this->whereGlue[] = $outerGlue;
        $this->whereGlue[] = $innerGlue;
        foreach ($conditions as $condition) {
            $this->where[] = $condition;
        }

        return $this;
    }

    public function bind(string $key, mixed $value, mixed $type = null): self
    {
        $this->bindings[] = [$key, $value, $type];

        return $this;
    }
}
