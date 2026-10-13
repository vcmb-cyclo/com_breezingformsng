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

/** Builds the common plupload configuration object used by QuickMode. */
final class QuickModeUploadConfigurationBuilder
{
    public function build(
        int $formId,
        string $fieldName,
        string $ticket,
        string $baseUrl,
        int $elementId,
        string $extensions,
        string $multipleSelection,
        string $chooseFileLabel,
        string $newline = "\n",
        ?string $uploadUrl = null
    ): string {
        $indent = '                                                                ';
        $fieldIndent = $indent . '        ';
        $escapedFieldName = $this->escapeJavaScriptString($fieldName);
        $escapedTicket = $this->escapeJavaScriptString($ticket);
        $escapedUploadUrl = $this->escapeJavaScriptString($uploadUrl ?? $baseUrl . 'index.php');
        $escapedExtensions = $this->escapeJavaScriptString($extensions);

        return $indent . 'var iOS = ( navigator.userAgent.match(/(iPad|iPhone|iPod)/i) ? true : false );'
            . $newline . $indent . 'var uploader = new plupload.Uploader({'
            . $newline . '                                                                        max_retries: 10,'
            . $newline . $fieldIndent . 'multi_selection: ' . $multipleSelection . ','
            . $newline . $fieldIndent . 'unique_names: iOS,'
            . $newline . $fieldIndent . "chunk_size: '100kb',"
            . $newline . $fieldIndent . "runtimes : 'html5',"
            . $newline . $fieldIndent . "browse_button : 'bfPickFiles" . $elementId . "',"
            . $newline . $fieldIndent . "container: 'bfUploadContainer" . $elementId . "',"
            . $newline . $fieldIndent . "file_data_name: 'Filedata',"
            . $newline . $fieldIndent . "multipart_params: { form: " . $formId
            . ", itemName : '" . $escapedFieldName . "', bfFlashUploadTicket: '" . $escapedTicket
            . "', option: 'com_breezingformsng', format: 'html', flashUpload: 'true', Itemid: 0 },"
            . $newline . $fieldIndent . "url : '" . $escapedUploadUrl . "',"
            . $newline . $fieldIndent . "filters : ["
            . $newline . $fieldIndent . '        {title : ' . $chooseFileLabel
            . ", extensions : '" . $escapedExtensions . "'}"
            . $newline . $fieldIndent . ']'
            . $newline . $indent . '});';
    }

    private function escapeJavaScriptString(string $value): string
    {
        return strtr($value, [
            '\\' => '\\\\',
            "'" => "\\'",
            "\r" => '\\r',
            "\n" => '\\n',
        ]);
    }
}
