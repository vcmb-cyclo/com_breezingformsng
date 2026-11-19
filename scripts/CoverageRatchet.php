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

declare(strict_types=1);

/**
 * The coverage ratchet: the line coverage may not fall below the committed
 * baseline (less a small tolerance for measurement noise), and a rise beyond
 * a margin is a reminder to raise the baseline.
 */
final class CoverageRatchet
{
    /** Points the coverage may lose against the baseline before the build fails. */
    public const TOLERANCE = 0.5;

    /** Points above the baseline from which raising the baseline is suggested. */
    public const RAISE_MARGIN = 1.0;

    /**
     * The baseline of a file: the first line that is not empty and not a "#" comment.
     *
     * @throws \RuntimeException When the file is unreadable or holds no percentage from 0 to 100.
     */
    public static function readBaseline(string $file): float
    {
        $lines = is_file($file) ? file($file, FILE_IGNORE_NEW_LINES) : false;

        if ($lines === false) {
            throw new \RuntimeException("Coverage baseline not found: {$file}");
        }

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '' || $line[0] === '#') {
                continue;
            }

            if (!is_numeric($line) || (float) $line < 0.0 || (float) $line > 100.0) {
                throw new \RuntimeException("Coverage baseline is not a percentage from 0 to 100: {$line}");
            }

            return (float) $line;
        }

        throw new \RuntimeException("Coverage baseline is empty: {$file}");
    }

    /**
     * @return array{failed: bool, message: string, notice: string}
     *         "notice" is empty unless the baseline deserves to be raised.
     */
    public static function evaluate(float $percentage, float $baseline): array
    {
        // Rounded as printed, so that 29.96 against 30.46 - 0.5 does not fail on float noise.
        $floor = round($baseline - self::TOLERANCE, 4);
        $failed = round($percentage, 4) < $floor;
        $message = sprintf(
            'Coverage baseline: %.2f%%, minimum with a %.1f point tolerance: %.2f%%',
            $baseline,
            self::TOLERANCE,
            $floor
        );
        $notice = '';

        if (!$failed && round($percentage, 4) - $baseline > self::RAISE_MARGIN) {
            $notice = sprintf(
                'Coverage (%.2f%%) is more than %.1f point above the baseline (%.2f%%): consider raising coverage-baseline.txt.',
                $percentage,
                self::RAISE_MARGIN,
                $baseline
            );
        }

        return ['failed' => $failed, 'message' => $message, 'notice' => $notice];
    }
}
