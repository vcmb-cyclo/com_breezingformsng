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
     * @param array<string, mixed> $dataObject
     */
    public function findOversizedLabel(array $dataObject, string $targetFile, string $itemName): ?string
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
            return trim((string) ($metadata['label'] ?? ''));
        }

        $children = $dataObject['children'] ?? [];
        if (!\is_array($children)) {
            return null;
        }

        foreach ($children as $child) {
            if (!\is_array($child)) {
                continue;
            }

            $label = $this->findOversizedLabel($child, $targetFile, $itemName);
            if ($label !== null) {
                return $label;
            }
        }

        return null;
    }
}
