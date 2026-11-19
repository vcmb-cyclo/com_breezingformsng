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

namespace Vcmb\Component\BreezingformsNG\Administrator\Service;

\defined('_JEXEC') or die;

/**
 * Keeps the code of a form (the PHP pieces and the scripts run on the site)
 * away from users who may edit forms but do not hold the "code" permission.
 */
final class FormCodeGuard
{
    /** The columns of a form that hold code run by the site. */
    public const CODE_COLUMNS = [
        'script1code', 'script2code',
        'piece1code', 'piece2code', 'piece3code', 'piece4code',
    ];

    /**
     * @param array<string, mixed> $data     What the user sent
     * @param object|null          $existing The form as stored, null for a new form
     *
     * @return array{data: array<string, mixed>, ignored: list<string>} The data to store,
     *         and the code columns whose change was not kept
     */
    public function apply(array $data, ?object $existing, bool $mayEditCode): array
    {
        if ($mayEditCode) {
            return ['data' => $data, 'ignored' => []];
        }

        $ignored = [];

        foreach (self::CODE_COLUMNS as $column) {
            $stored = $existing === null ? '' : (string) ($existing->$column ?? '');

            if (array_key_exists($column, $data) && $this->normalise((string) $data[$column]) !== $this->normalise($stored)) {
                $ignored[] = $column;
            }

            // An existing form keeps its code; a new form starts without any.
            if ($existing === null) {
                $data[$column] = '';
            } else {
                unset($data[$column]);
            }
        }

        return ['data' => $data, 'ignored' => $ignored];
    }

    /** A browser posts line breaks as CRLF: the same code is not a change. */
    private function normalise(string $code): string
    {
        return str_replace("\r\n", "\n", $code);
    }
}
