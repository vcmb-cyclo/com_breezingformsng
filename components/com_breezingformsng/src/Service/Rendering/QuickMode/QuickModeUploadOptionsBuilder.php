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

namespace Vcmb\Component\BreezingformsNG\Site\Service\Rendering\QuickMode;

final class QuickModeUploadOptionsBuilder
{
    /**
     * @param array<string, mixed> $data
     * @return array{
     *     extensions: string,
     *     maxFileSize: string,
     *     maxBytes: string,
     *     multiSelection: string,
     *     buttonWidth: string,
     *     buttonHeight: string
     * }
     */
    public function build(array $data): array
    {
        $extensions = implode(',', explode(',', (string) ($data['allowedFileExtensions'] ?? '')));
        $bytes = $data['flashUploaderBytes'] ?? null;
        $maxBytes = is_numeric($bytes) && $bytes > 0 ? (string) (int) $bytes : '0';
        $maxFileSize = $maxBytes !== '0' ? "max_file_size : '" . $maxBytes . "'," : '';
        $multiSelection = !empty($data['flashUploaderMulti']) ? 'true' : 'false';
        $buttonWidth = is_numeric($data['flashUploaderWidth'] ?? null) && $data['flashUploaderWidth'] > 0
            ? (string) (int) $data['flashUploaderWidth']
            : '64';
        $buttonHeight = is_numeric($data['flashUploaderHeight'] ?? null) && $data['flashUploaderHeight'] > 0
            ? (string) (int) $data['flashUploaderHeight']
            : '64';

        return [
            'extensions' => $extensions,
            'maxFileSize' => $maxFileSize,
            'maxBytes' => $maxBytes,
            'multiSelection' => $multiSelection,
            'buttonWidth' => $buttonWidth,
            'buttonHeight' => $buttonHeight,
        ];
    }
}
