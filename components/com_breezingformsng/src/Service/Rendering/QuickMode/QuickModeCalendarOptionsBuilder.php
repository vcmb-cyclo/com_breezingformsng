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

namespace Vcmb\Component\BreezingformsNG\Site\Service\Rendering\QuickMode;

/**
 * Builds normalized calendar options shared by the QuickMode renderers.
 */
final class QuickModeCalendarOptionsBuilder
{
    /**
     * @param array<mixed> $metadata
     */
    public function isTruthy(array $metadata, string $key): bool
    {
        return isset($metadata[$key])
            && $metadata[$key] !== ''
            && $metadata[$key] !== '0'
            && $metadata[$key] !== 0
            && $metadata[$key] !== false;
    }

    /**
     * @param array<mixed> $metadata
     */
    public function showTimeEnabled(array $metadata): bool
    {
        return $this->isTruthy($metadata, 'showTime');
    }

    public function toPickadateFormat(mixed $format): string
    {
        $format = trim((string) $format);
        if ($format === '') {
            return 'yyyy-mm-dd';
        }

        $format = str_replace(
            ['%Y', '%y', '%m', '%d', '%e', '%B', '%b'],
            ['yyyy', 'yy', 'mm', 'dd', 'd', 'mmmm', 'mmm'],
            $format
        );
        $format = preg_replace('/\s*(%H|%I|%k|%l|%M|%S|%p).*/', '', $format);
        $format = trim((string) $format);

        return $format !== '' ? $format : 'yyyy-mm-dd';
    }

    public function toPickadateFirstDay(mixed $firstDay): int
    {
        $firstDay = (int) $firstDay;
        if ($firstDay < 1 || $firstDay > 7) {
            $firstDay = 1;
        }

        return $firstDay === 7 ? 0 : $firstDay;
    }

    /**
     * @param array<mixed> $metadata
     */
    public function selectYears(array $metadata): int
    {
        $minYear = isset($metadata['minYear']) && is_numeric($metadata['minYear'])
            ? max(0, (int) $metadata['minYear'])
            : 0;
        $maxYear = isset($metadata['maxYear']) && is_numeric($metadata['maxYear'])
            ? max(0, (int) $metadata['maxYear'])
            : 0;
        $range = $minYear + $maxYear;

        return $range > 0 ? max(10, $range + 1) : 60;
    }
}
