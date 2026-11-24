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
 * Where an administrator sees one extension registration: the manager of its own type when that manager
 * lists the registration by its extension id, else the Extensions Manager of Joomla.
 */
final class ExtensionAdminLink
{
    /**
     * @param array<string, mixed> $entry A row of the extensions table: type, element, client_id, extension_id.
     */
    public static function forEntry(array $entry): string
    {
        $type = (string) ($entry['type'] ?? '');
        $client = (int) ($entry['client_id'] ?? 0);
        $id = (int) ($entry['extension_id'] ?? 0);
        $element = rawurlencode((string) ($entry['element'] ?? ''));

        return match ($type) {
            'template' => 'index.php?option=com_templates&view=styles&client_id=' . $client . '&filter[search]=' . $element,
            // The plugin manager searches the registrations by their extension id.
            'plugin' => 'index.php?option=com_plugins&view=plugins&filter[search]=id:' . $id,
            // The module manager lists module instances: its "id:" is the id of an instance, not of the
            // extension. The Extensions Manager searches the registration by its extension id.
            default => 'index.php?option=com_installer&view=manage&filter[search]=id:' . $id,
        };
    }
}
