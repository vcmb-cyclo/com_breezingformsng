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

namespace Vcmb\Component\BreezingformsNG\Site\Service\Upload;

\defined('_JEXEC') or die;

final class FlashUploadSizeValidator
{
    /**
     * Ceiling for one chunked file when the field sets no limit (flashUploaderBytes = 0). Anyone who has
     * displayed the form holds an upload ticket, so an unlimited field would let a visitor fill the disk.
     * 256 MiB covers large documents and short videos; a larger limit is still possible by configuring
     * it on the field.
     */
    public const GLOBAL_MAX_BYTES = 268435456;

    /** Chunk size announced to the uploader (plupload chunk_size '100kb'). */
    public const CHUNK_BYTES = 102400;

    /**
     * Effective byte limit of the field: its own limit when positive, otherwise the global ceiling.
     *
     * @param array<string, mixed> $dataObject
     * @return array{label: string, maxBytes: int}
     */
    public function limitFor(array $dataObject, string $itemName): array
    {
        $metadata = $dataObject['properties'] ?? [];

        if (
            \is_array($metadata)
            && ($metadata['type'] ?? '') === 'element'
            && ($metadata['bfType'] ?? '') === 'bfFile'
            && trim((string) ($metadata['bfName'] ?? '')) === trim($itemName)
        ) {
            $configured = (int) ($metadata['flashUploaderBytes'] ?? 0);

            return [
                'label' => trim((string) ($metadata['label'] ?? '')),
                'maxBytes' => $configured > 0 ? $configured : self::GLOBAL_MAX_BYTES,
            ];
        }

        $children = $dataObject['children'] ?? [];
        if (\is_array($children)) {
            foreach ($children as $child) {
                if (\is_array($child)) {
                    $found = $this->limitFor($child, $itemName);
                    if ($found['maxBytes'] !== self::GLOBAL_MAX_BYTES || $found['label'] !== '') {
                        return $found;
                    }
                }
            }
        }

        return ['label' => '', 'maxBytes' => self::GLOBAL_MAX_BYTES];
    }

    /**
     * @param array<string, mixed> $dataObject
     * @return array{label: string, maxBytes: int}|null
     */
    public function findOversized(array $dataObject, string $targetFile, string $itemName): ?array
    {
        $metadata = $dataObject['properties'] ?? [];

        if (
            \is_array($metadata)
            && ($metadata['type'] ?? '') === 'element'
            && ($metadata['bfType'] ?? '') === 'bfFile'
            && (int) ($metadata['flashUploaderBytes'] ?? 0) > 0
            && trim((string) ($metadata['bfName'] ?? '')) === trim($itemName)
            && \is_file($targetFile)
            && (int) \filesize($targetFile) > (int) $metadata['flashUploaderBytes']
        ) {
            return [
                'label' => trim((string) ($metadata['label'] ?? '')),
                'maxBytes' => (int) $metadata['flashUploaderBytes'],
            ];
        }

        $children = $dataObject['children'] ?? [];
        if (!\is_array($children)) {
            return null;
        }

        foreach ($children as $child) {
            if (!\is_array($child)) {
                continue;
            }

            $oversized = $this->findOversized($child, $targetFile, $itemName);
            if ($oversized !== null) {
                return $oversized;
            }
        }

        return null;
    }
}
