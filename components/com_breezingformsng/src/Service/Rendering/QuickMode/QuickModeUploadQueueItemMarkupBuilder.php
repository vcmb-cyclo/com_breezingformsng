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

/** Builds the shared upload queue item markup. */
// phpcs:disable Generic.Files.LineLength
final class QuickModeUploadQueueItemMarkupBuilder
{
    public static function build(
        int $dbId,
        string $cancelImagePath,
        bool $conditionalName,
        bool $includeBorderAttribute,
    ): string {
        $escapedCancelImagePath = htmlspecialchars(
            $cancelImagePath,
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );
        $escapedCancelImagePath = str_replace(
            ['\\', "'"],
            ['\\\\', "\\'"],
            $escapedCancelImagePath
        );
        $name = $conditionalName
            ? "(iOS ? '' : files[i].name.replace(/[/\\?%*:|\"<>]/g, '') ? files[i].name.replace(/[/\\?%*:|\"<>]/g, '') : '')"
            : "(iOS ? '' : files[i].name.replace(/[/\\?%*:|\"<>]/g, ''))";

        $markup = <<<'JAVASCRIPT'
                                                                                JQuery('#bfFlashFileQueue{{DB_ID}}').append('<div class="bfFileQueueItem" id="' + files[i].id + 'queueitem"><div id="' + files[i].id + 'thumb"></div><div id="' + files[i].id + '"><img id="' + files[i].id + 'cancel" src="{{CANCEL_IMAGE}}" style="cursor: pointer; padding-right: 10px;"{{BORDER}}{{IMAGE_END}}' + {{NAME}} + ' ' + fsize + '<b id="' + files[i].id + 'msg" style="color:red;"></b></div></div>');
JAVASCRIPT;

        return strtr($markup, [
            '{{DB_ID}}' => (string) $dbId,
            '{{CANCEL_IMAGE}}' => $escapedCancelImagePath,
            '{{BORDER}}' => $includeBorderAttribute ? ' border="0"' : '',
            '{{IMAGE_END}}' => $includeBorderAttribute ? '/>' : ' />',
            '{{NAME}}' => $name,
        ]);
    }
}
// phpcs:enable Generic.Files.LineLength
