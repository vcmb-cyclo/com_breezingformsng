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

/** Builds the shared upload image thumbnail callback. */
// phpcs:disable Generic.Files.LineLength
final class QuickModeUploadThumbnailScriptBuilder
{
    public static function build(string $newline): string
    {
        return '                                                        function bfUploadImageThumb(file) {' . $newline .
            '                                                                var img;' . $newline .
            '                                                                var thumbId = \'#\' + file.id + \'thumb\';' . $newline .
            '                                                                var thumbEl = JQuery(thumbId).get(0);' . $newline .
            $newline .
            '                                                                function bfIsImage(f) {' . $newline .
            '                                                                        var name = (f && f.name) ? f.name : \'\';' . $newline .
            '                                                                        var ext = name.split(\'.\').pop().toLowerCase();' . $newline .
            '                                                                        if (f && f.type && f.type.indexOf(\'image/\') === 0) {' . $newline .
            '                                                                                return true;' . $newline .
            '                                                                        }' . $newline .
            '                                                                        return [\'jpg\', \'jpeg\', \'png\', \'gif\', \'webp\', \'bmp\', \'svg\'].indexOf(ext) !== -1;' . $newline .
            '                                                                }' . $newline .
            $newline .
            '                                                                function bfFallbackThumb() {' . $newline .
            '                                                                        if (!thumbEl || !bfIsImage(file) || !window.FileReader) {' . $newline .
            '                                                                                return;' . $newline .
            '                                                                        }' . $newline .
            '                                                                        var nativeFile = null;' . $newline .
            '                                                                        if (file && typeof file.getNative === \'function\') {' . $newline .
            '                                                                                nativeFile = file.getNative();' . $newline .
            '                                                                        }' . $newline .
            '                                                                        if (!nativeFile && file && typeof file.getSource === \'function\') {' . $newline .
            '                                                                                var src = file.getSource();' . $newline .
            '                                                                                if (src && typeof src.getSource === \'function\') {' . $newline .
            '                                                                                        nativeFile = src.getSource();' . $newline .
            '                                                                                }' . $newline .
            '                                                                        }' . $newline .
            '                                                                        if (!nativeFile) {' . $newline .
            '                                                                                return;' . $newline .
            '                                                                        }' . $newline .
            '                                                                        var reader = new FileReader();' . $newline .
            '                                                                        reader.onload = function(e) {' . $newline .
            '                                                                                try {' . $newline .
            '                                                                                        var imgTag = new Image();' . $newline .
            '                                                                                        imgTag.onload = function() {' . $newline .
            '                                                                                                imgTag.style.maxWidth = \'100px\';' . $newline .
            '                                                                                                imgTag.style.maxHeight = \'60px\';' . $newline .
            '                                                                                                thumbEl.innerHTML = \'\';' . $newline .
            '                                                                                                thumbEl.appendChild(imgTag);' . $newline .
            '                                                                                        };' . $newline .
            '                                                                                        imgTag.src = e.target.result;' . $newline .
            '                                                                                } catch (err) {}' . $newline .
            '                                                                        };' . $newline .
            '                                                                        reader.readAsDataURL(nativeFile);' . $newline .
            '                                                                }' . $newline .
            $newline .
            '                                                                if (window.moxie && window.moxie.image && window.moxie.image.Image && thumbEl) {' . $newline .
            '                                                                        try {' . $newline .
            '                                                                                img = new moxie.image.Image;' . $newline .
            '                                                                                img.onload = function() {' . $newline .
            '                                                                                        img.embed(thumbEl, {' . $newline .
            '                                                                                                width: 100,' . $newline .
            '                                                                                                height: 60,' . $newline .
            '                                                                                                crop: true' . $newline .
            '                                                                                        });' . $newline .
            '                                                                                };' . $newline .
            $newline .
            '                                                                                img.onembedded = function() {' . $newline .
            '                                                                                        img.destroy();' . $newline .
            '                                                                                };' . $newline .
            $newline .
            '                                                                                img.onerror = function() {' . $newline .
            '                                                                                        bfFallbackThumb();' . $newline .
            '                                                                                };' . $newline .
            $newline .
            '                                                                                img.load(file.getSource());' . $newline .
            '                                                                                return;' . $newline .
            '                                                                        } catch (e) {}' . $newline .
            '                                                                }' . $newline .
            $newline .
            '                                                                bfFallbackThumb();' . $newline .
            '                                                        }';
    }
}
// phpcs:enable Generic.Files.LineLength
