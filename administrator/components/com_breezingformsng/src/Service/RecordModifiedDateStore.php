<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Administrator\Service;

\defined('_JEXEC') or die;

/**
 * Storage used by the one-off conversion of the record modification dates.
 */
interface RecordModifiedDateStore
{
    /** Durable marker: null when never set, otherwise 'pending' or 'done'. */
    public function getMarker(): ?string;

    public function setMarker(string $state): void;

    /** @return array<int, string> Stored modification date by record id, NULL dates excluded. */
    public function loadModifiedDates(): array;

    public function updateModified(int $id, string $modified): void;

    public function transactionStart(): void;

    public function transactionCommit(): void;

    public function transactionRollback(): void;
}
