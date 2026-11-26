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

namespace Vcmb\Component\BreezingformsNG\Site\Service\Widget;

use DateTimeImmutable;
use DateTimeZone;

/**
 * The value stored for a given consent: when it was given, the text the visitor accepted and the
 * privacy policy it linked. The server writes it, so the browser cannot choose the date.
 */
final class ConsentProof
{
    /**
     * @param string $template translated sentence: %1$s is the date, %2$s the accepted text
     *
     * @return string '' when the box was not checked
     */
    public static function value(
        string $posted,
        string $template,
        string $label,
        string $url,
        DateTimeImmutable $now
    ): string {
        if (trim($posted) === '') {
            return '';
        }

        $date = $now->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s') . ' UTC';
        $text = trim(html_entity_decode(strip_tags($label), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        return sprintf($template, $date, $text) . ($url === '' ? '' : ' — ' . $url);
    }
}
