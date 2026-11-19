<?php

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Site\Service\Callback;

use Joomla\Database\DatabaseInterface;

final class PaymentDownloadDatabaseDouble implements DatabaseInterface
{
    /** @var list<PaymentDownloadQueryDouble> */
    public array $queries = [];

    public int $executions = 0;

    public int $affectedRows = 1;

    /** @var list<list<object>> */
    private array $results;

    /** @param list<list<object>> $results */
    public function __construct(array $results)
    {
        $this->results = $results;
    }

    public function createQuery(): PaymentDownloadQueryDouble
    {
        return $this->queries[] = new PaymentDownloadQueryDouble();
    }

    public function quoteName(string|array $name): string|array
    {
        return $name;
    }

    public function setQuery(object $query, int $offset = 0, int $limit = 0): void
    {
    }

    /** @return list<object> */
    public function loadObjectList(): array
    {
        return array_shift($this->results) ?? [];
    }

    public function getAffectedRows(): int
    {
        return $this->affectedRows;
    }

    public function execute(): bool
    {
        $this->executions++;

        return true;
    }
}
