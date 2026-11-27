<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Site\Service\Export;

\defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;

/**
 * Runs writes that must be saved together. Inside an open transaction, a
 * savepoint is used so the outer one stays in control.
 */
final class DatabaseTransaction
{
    public function __construct(private readonly DatabaseInterface $database)
    {
    }

    /**
     * Commits when the work returns true; rolls back when it returns false or
     * throws.
     *
     * @param callable(): bool $work
     */
    public function run(callable $work): bool
    {
        $this->database->transactionStart(true);
        $succeeded = false;

        try {
            $succeeded = $work();

            if ($succeeded) {
                $this->database->transactionCommit(true);
            }
        } finally {
            if (!$succeeded) {
                $this->database->transactionRollback(true);
            }
        }

        return $succeeded;
    }
}
