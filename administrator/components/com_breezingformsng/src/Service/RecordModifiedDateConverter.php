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
 * Releases up to 6.1.0-RC07 stored the modification date of a record in the
 * site time zone, while its submission date has always been UTC. This converts
 * the stored values to UTC exactly once.
 *
 * Joomla records the new schema version before the installer finishes, so the
 * schema version alone cannot tell a finished conversion from an interrupted
 * one. A durable marker does: 'pending' is committed before the first change,
 * and 'done' is committed in the same transaction as every converted date, so
 * the dates are either all converted with the marker 'done', or all untouched
 * with the marker still 'pending' and the next run converts them again.
 */
final class RecordModifiedDateConverter
{
    public const PENDING = 'pending';
    public const DONE = 'done';

    public function __construct(private readonly RecordModifiedDateStore $store)
    {
    }

    /**
     * @param bool $schemaPredatesConversion The installed schema was older than the one introducing UTC dates.
     *
     * @return int Number of converted records, 0 when nothing had to be done.
     */
    public function convert(\DateTimeZone $siteTimezone, bool $schemaPredatesConversion): int
    {
        $marker = $this->store->getMarker();

        if ($marker === self::DONE) {
            return 0;
        }

        if ($marker === null) {
            if (!$schemaPredatesConversion) {
                // Installed after the change (or converted by an earlier release): nothing to convert.
                $this->store->setMarker(self::DONE);

                return 0;
            }

            // Committed before any change, so an interrupted run is retried even
            // though Joomla already recorded the new schema version.
            $this->store->setMarker(self::PENDING);
        }

        $dates = $this->store->loadModifiedDates();
        $converted = 0;
        $this->store->transactionStart();

        try {
            foreach ($dates as $id => $modified) {
                $utc = self::toUtc($modified, $siteTimezone);

                if ($utc !== null && $utc !== $modified) {
                    $this->store->updateModified($id, $utc);
                    $converted++;
                }
            }

            $this->store->setMarker(self::DONE);
            $this->store->transactionCommit();
        } catch (\Throwable $e) {
            $this->store->transactionRollback();

            throw $e;
        }

        return $converted;
    }

    /** Null for an empty or zero date, which has nothing to convert. */
    public static function toUtc(string $modified, \DateTimeZone $siteTimezone): ?string
    {
        $modified = trim($modified);

        if ($modified === '' || str_starts_with($modified, '0000-00-00')) {
            return null;
        }

        return (new \DateTimeImmutable($modified, $siteTimezone))
            ->setTimezone(new \DateTimeZone('UTC'))
            ->format('Y-m-d H:i:s');
    }
}
