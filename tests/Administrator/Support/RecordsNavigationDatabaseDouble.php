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

final class RecordsNavigationDatabaseDouble implements DatabaseInterface
{
    public RecordsNavigationQueryDouble $query;

    /** @param list<int> $recordIds */
    public function __construct(private readonly array $recordIds)
    {
    }

    public function getQuery(bool $new = false): RecordsNavigationQueryDouble
    {
        return $this->query = new RecordsNavigationQueryDouble();
    }

    public function quoteName(string|array $name): string|array
    {
        return $name;
    }

    public function setQuery(object $query, int $offset = 0, int $limit = 0): void
    {
    }

    /** @return list<int> */
    public function loadColumn(): array
    {
        return $this->recordIds;
    }
}
