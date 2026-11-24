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

namespace Vcmb\Component\BreezingformsNG\Administrator\Service;

\defined('_JEXEC') or die;

/**
 * The extensions the BreezingForms NG package installs. Everything else whose name contains
 * "breezingforms" belongs to a former BreezingForms installation. Keep in line with script.php.
 */
final class NgExtensions
{
    /** Plugins by group. */
    private const PLUGINS = [
        'system' => ['bfcompat'],
        'content' => ['breezingforms'],
        'breezingforms_addons' => ['mailchimp', 'salesforce', 'dropbox'],
        'privacy' => ['breezingformsng'],
        'webservices' => ['breezingformsng'],
    ];

    /**
     * True when the registration is one of the extensions installed by the BreezingForms NG package.
     */
    public static function owns(string $type, string $element, string $folder = '', int $clientId = 0): bool
    {
        $element = strtolower(trim($element));
        $folder = strtolower(trim($folder));

        return match ($type) {
            'component' => $element === 'com_breezingformsng',
            'module' => $element === 'mod_breezingforms' && $clientId === 0,
            'plugin' => in_array($element, self::PLUGINS[$folder] ?? [], true),
            default => false,
        };
    }
}
