<?php
/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Vcmb\Component\BreezingformsNG\Administrator\Service;

\defined('_JEXEC') or die;

/**
 * Turns a free-text date (package files, legacy package XML) into a value a
 * DATETIME column accepts. The date must exist: 2024-02-31 is refused.
 */
final class SqlDate
{
    /**
     * @return string|null "Y-m-d H:i:s", or null when the text is no real date.
     */
    public static function normalise(string $value): ?string
    {
        $value = trim($value);

        foreach (['Y-m-d H:i:s', 'Y-m-d'] as $format) {
            $date = \DateTimeImmutable::createFromFormat('!' . $format, $value);

            // A round trip rejects the dates PHP rolls over (2024-02-31 -> 03-02).
            if ($date !== false && $date->format($format) === $value && (int) $date->format('Y') >= 1000) {
                return $date->format('Y-m-d H:i:s');
            }
        }

        return null;
    }
}
