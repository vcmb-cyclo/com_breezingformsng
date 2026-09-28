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

/** Builds the shared plupload cancellation and reactivation callback. */
// phpcs:disable Generic.Files.LineLength
final class QuickModeUploadCancelScriptBuilder
{
    public static function build(
        string $multiSelection,
        int $dbId,
        bool $usesDisabledProperty,
        string $newline,
    ): string {
        $reenableScript = $usesDisabledProperty
            ? "                                                                                                JQuery('#bfPickFiles" . $dbId . "').prop('disabled',false);" . $newline
            : "                                                                                                JQuery('#bfPickFiles" . $dbId . "').css('display','block');" . $newline
                . "                                                                                                JQuery('#bfPickFiles" . $dbId . "holder').css('display','none');" . $newline;

        return '                                                                                var file_ = files[i];' . $newline .
            '                                                                                var uploader_ = uploader;' . $newline .
            '                                                                                var bfUploaders_ = bfUploaders;' . $newline .
            "                                                                                JQuery('#' + files[i].id + 'cancel').click(" . $newline .
            '                                                                                    function(){' . $newline .
            '                                                                                        for( var i = 0; i < bfUploaders_.length; i++ ){' . $newline .
            '                                                                                            bfUploaders_[i].stop();' . $newline .
            '                                                                                        }' . $newline .
            "                                                                                        var id_ = this.id.split('cancel');" . $newline .
            '                                                                                        id_ = id_[0];' . $newline .
            '                                                                                        uploader_.removeFile(id_);' . $newline .
            "                                                                                        JQuery('#'+id_+'queue').remove();" . $newline .
            "                                                                                        JQuery('#'+id_+'queueitem').remove();" . $newline .
            '                                                                                        bfFlashUploadersLength--;' . $newline .
            '                                                                                        for( var i = 0; i < bfUploaders_.length; i++ ){' . $newline .
            '                                                                                            bfUploaders_[i].start();' . $newline .
            '                                                                                        }' . $newline .
            '                                                                                        // re-enable button if there is none left' . $newline .
            '                                                                                        if( ' . $multiSelection . ' == false ){' . $newline .
            "                                                                                            var the_size = JQuery('#bfFlashFileQueue" . $dbId . " .bfFileQueueItem').length;" . $newline .
            '                                                                                            if( the_size == 0 ){' . $newline .
            $reenableScript .
            '                                                                                            }' . $newline .
            '                                                                                        }' . $newline .
            '                                                                                    }' . $newline .
            '                                                                                );';
    }
}
// phpcs:enable Generic.Files.LineLength
