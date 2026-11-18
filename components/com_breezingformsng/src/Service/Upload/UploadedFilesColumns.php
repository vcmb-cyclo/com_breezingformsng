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

namespace Vcmb\Component\BreezingformsNG\Site\Service\Upload;

/**
 * The files of one upload field, in the layout the submission reads: one list per
 * property (name, type, tmp_name, error, size), as PHP's $_FILES holds a field named
 * "ff_nm_Field[]". Joomla's files input gives one entry per file instead.
 */
final class UploadedFilesColumns
{
    private const PROPERTIES = ['name', 'type', 'tmp_name', 'error', 'size'];

    /**
     * @param array<mixed>|null $files What Joomla's files input returns for a field.
     * @return array{name: list<mixed>, type: list<mixed>, tmp_name: list<mixed>, error: list<mixed>, size: list<mixed>}|null
     */
    public static function fromJoomla(?array $files): ?array
    {
        if ($files === null || $files === []) {
            return null;
        }

        // A field without brackets is a single file: its properties are not lists.
        $entries = isset($files['tmp_name']) ? [$files] : array_values($files);
        $columns = array_fill_keys(self::PROPERTIES, []);

        foreach ($entries as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            foreach (self::PROPERTIES as $property) {
                $columns[$property][] = $entry[$property] ?? ($property === 'error' || $property === 'size' ? 0 : '');
            }
        }

        return $columns['name'] === [] ? null : $columns;
    }
}
