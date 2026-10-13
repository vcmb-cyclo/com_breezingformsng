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

/** Builds the shared initial upload queue and FilesAdded callbacks. */
final class QuickModeUploadEntryCallbacksBuilder
{
    public static function build(
        int $elementId,
        string $cancelImagePath,
        bool $bootstrapMarkup,
        bool $hasBlankLine,
        string $multiSelection,
        int $maxBytes,
        string $extensions,
        string $tooLargeMessage,
        string $extensionMessage,
        string $newline = "\n"
    ): string {
        return self::queueEntryScript($newline, $hasBlankLine)
            . $newline
            . self::fileAddedHandler(
                $elementId,
                $cancelImagePath,
                $bootstrapMarkup,
                $multiSelection,
                $maxBytes,
                $extensions,
                $tooLargeMessage,
                $extensionMessage,
                $newline
            );
    }

    // phpcs:disable Generic.Files.LineLength
    private static function queueEntryScript(string $newline, bool $hasBlankLine): string
    {
        $optionalBlankLine = $hasBlankLine ? $newline : '';

        return "                                                                uploader.bind('FilesAdded', function(up, files) {" . $newline . $optionalBlankLine
            . self::filesLoop(
                $newline,
                self::fileSizeScript($newline, 84, 88)
                    . str_repeat(' ', 84) . "if(typeof bfUploadFileAdded == 'function'){" . $newline .
                    str_repeat(' ', 88) . 'bfUploadFileAdded(files[i]);' . $newline .
                    str_repeat(' ', 84) . "}" . $newline .
                    str_repeat(' ', 84) . "JQuery('#bfFileQueue').append( '<div id=\"' + files[i].id + 'queue\">' + (iOS ? '' : files[i].name.replace(/[/\\?%*:|\"<>]/g, '')) + ' '+fsize+'<b></b></div>' );" . $newline,
                80
            );
    }

    private static function fileAddedHandler(
        int $elementId,
        string $cancelImagePath,
        bool $bootstrapMarkup,
        string $multiSelection,
        int $maxBytes,
        string $extensions,
        string $tooLargeMessage,
        string $extensionMessage,
        string $newline
    ): string {
        return self::filesLoop(
            $newline,
            str_repeat(' ', 80) . "var error = false;" . $newline .
                self::fileSizeScript($newline, 80, 84) .
                QuickModeUploadQueueItemMarkupBuilder::build($elementId, $cancelImagePath, $bootstrapMarkup, false) . $newline .
                QuickModeUploadCancelScriptBuilder::build($multiSelection, $elementId, $bootstrapMarkup, $newline) .
                $newline .
                QuickModeUploadValidationScriptBuilder::build(
                    $maxBytes,
                    strtolower($extensions),
                    $tooLargeMessage,
                    $extensionMessage,
                    $newline
                ) . $newline,
            76
        );
    }

    private static function filesLoop(string $newline, string $body, int $guardIndent): string
    {
        return str_repeat(' ', 72) . 'for (var i in files) {' . $newline .
            str_repeat(' ', $guardIndent) . "if(typeof files[i].id != 'undefined' && files[i].id != null){" . $newline .
            $body .
            str_repeat(' ', $guardIndent) . '}' . $newline .
            str_repeat(' ', 72) . '}';
    }

    private static function fileSizeScript(string $newline, int $indent, int $nestedIndent): string
    {
        return str_repeat(' ', $indent) . "var fsize = '';" . $newline .
            str_repeat(' ', $indent) . "if(typeof files[i].size != 'undefined'){" . $newline .
            str_repeat(' ', $nestedIndent) . "fsize = '(' + plupload.formatSize(files[i].size) + ') ';" . $newline .
            str_repeat(' ', $indent) . '}' . $newline;
    }
    // phpcs:enable Generic.Files.LineLength
}
