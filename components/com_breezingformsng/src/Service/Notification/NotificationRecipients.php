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

namespace Vcmb\Component\BreezingformsNG\Site\Service\Notification;

\defined('_JEXEC') or die;

/**
 * The recipients of the administrator notification, as an administrator typed them: addresses and
 * {field:...} placeholders separated by semicolons, and an optional "sender:address" entry.
 */
final class NotificationRecipients
{
    /**
     * The text earlier versions offered as the default address. It is not an address: a site that kept it
     * has no recipient.
     */
    public const LEGACY_PLACEHOLDER = 'Enter your email address here';

    /**
     * @return array{recipients: list<string>, sender: string} The recipients to mail (none when nothing usable
     *                                                          is configured) and the sender address, if any.
     */
    public static function parse(string $raw): array
    {
        $recipients = [];
        $sender = '';

        foreach (explode(';', $raw) as $entry) {
            $entry = trim($entry);

            if ($entry === '' || $entry === self::LEGACY_PLACEHOLDER) {
                continue;
            }

            $parts = explode(':', $entry);

            if (count($parts) === 2 && strtolower(trim($parts[0])) === 'sender') {
                $sender = trim($parts[1]);

                continue;
            }

            $recipients[] = $entry;
        }

        return ['recipients' => $recipients, 'sender' => $sender];
    }
}
