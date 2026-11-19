<?php

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Site\Service\Callback;

final class PaymentDownloadQueryDouble
{
    /** @var array<string, string> */
    public array $bindings = [];

    public function select(mixed $columns): self
    {
        return $this;
    }

    public function from(string $table): self
    {
        return $this;
    }

    /** @var list<string> */
    public array $conditions = [];

    public function conditionsInclude(string $needle): bool
    {
        return str_contains(implode(' ', $this->conditions), $needle);
    }

    public function where(string $condition): self
    {
        $this->conditions[] = $condition;

        return $this;
    }

    public function extendWhere(string $outerGlue, array $conditions, string $innerGlue): self
    {
        return $this;
    }

    public function bind(string $key, mixed $value, mixed $type): self
    {
        $this->bindings[$key] = (string) $value;

        return $this;
    }

    public function update(string $table): self
    {
        return $this;
    }

    public function set(string $condition): self
    {
        return $this;
    }
}
