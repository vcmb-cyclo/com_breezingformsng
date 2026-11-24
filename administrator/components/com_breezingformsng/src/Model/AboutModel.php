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

namespace Vcmb\Component\BreezingformsNG\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\Installer\Installer;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

final class AboutModel extends BaseDatabaseModel
{
    /**
     * @return list<array<string, mixed>>
     */
    public function getInstalledPluginRows(): array
    {
        $db = $this->getDatabase();
        $type = 'plugin';
        $namePattern = '%BreezingForms%';
        $elementPattern = '%breezingforms%';
        $compatElement = 'bfcompat';
        $query = $db->createQuery()
            ->select([
                $db->quoteName('extension_id'),
                $db->quoteName('name'),
                $db->quoteName('element'),
                $db->quoteName('folder'),
                $db->quoteName('enabled'),
                $db->quoteName('manifest_cache'),
            ])
            ->from($db->quoteName('#__extensions'))
            ->where($db->quoteName('type') . ' = :type')
            ->where(
                '('
                . $db->quoteName('element') . ' LIKE :elementPattern'
                . ' OR ' . $db->quoteName('element') . ' = :compatElement'
                . ' OR ' . $db->quoteName('folder') . ' LIKE :folderPattern'
                . ' OR ' . $db->quoteName('name') . ' LIKE :namePattern'
                . ')'
            )
            ->order([$db->quoteName('folder') . ' ASC', $db->quoteName('element') . ' ASC'])
            ->bind(':type', $type)
            ->bind(':elementPattern', $elementPattern)
            ->bind(':folderPattern', $elementPattern)
            ->bind(':compatElement', $compatElement)
            ->bind(':namePattern', $namePattern);

        return $db->setQuery($query)->loadAssocList() ?: [];
    }

    public function isInstalledPlugin(int $extensionId): bool
    {
        if ($extensionId <= 0) {
            return false;
        }

        foreach ($this->getInstalledPluginRows() as $row) {
            if ((int) ($row['extension_id'] ?? 0) === $extensionId) {
                return true;
            }
        }

        return false;
    }

    /**
     * Every extension of the BreezingForms family, whatever its type (component, module, plugin, library,
     * package, file, template, language): the ones of BreezingForms NG and the ones of the former BreezingForms.
     *
     * @return list<array<string, mixed>>
     */
    public function getBreezingFormsExtensionRows(): array
    {
        $db = $this->getDatabase();
        $namePattern = '%BreezingForms%';
        $elementPattern = '%breezingforms%';
        $compatElement = 'bfcompat';
        $query = $db->createQuery()
            ->select([
                $db->quoteName('extension_id'),
                $db->quoteName('name'),
                $db->quoteName('type'),
                $db->quoteName('element'),
                $db->quoteName('folder'),
                $db->quoteName('client_id'),
                $db->quoteName('enabled'),
                $db->quoteName('protected'),
                $db->quoteName('manifest_cache'),
            ])
            ->from($db->quoteName('#__extensions'))
            ->where(
                '('
                . $db->quoteName('element') . ' LIKE :elementPattern'
                . ' OR ' . $db->quoteName('element') . ' = :compatElement'
                . ' OR ' . $db->quoteName('folder') . ' LIKE :folderPattern'
                . ' OR ' . $db->quoteName('name') . ' LIKE :namePattern'
                . ')'
            )
            ->order([
                $db->quoteName('type') . ' ASC',
                $db->quoteName('folder') . ' ASC',
                $db->quoteName('element') . ' ASC',
            ])
            ->bind(':elementPattern', $elementPattern)
            ->bind(':folderPattern', $elementPattern)
            ->bind(':compatElement', $compatElement)
            ->bind(':namePattern', $namePattern);

        return $db->setQuery($query)->loadAssocList() ?: [];
    }

    /**
     * Uninstalls one extension of the BreezingForms family with the Joomla installer, like the Installer
     * component does. BreezingForms NG itself (the component running this screen) and the extensions
     * Joomla protects are refused.
     *
     * @throws \RuntimeException With a message fit for the administrator.
     */
    public function uninstallExtension(int $extensionId): string
    {
        $row = null;

        foreach ($this->getBreezingFormsExtensionRows() as $candidate) {
            if ((int) ($candidate['extension_id'] ?? 0) === $extensionId && $extensionId > 0) {
                $row = $candidate;
            }
        }

        if ($row === null) {
            throw new \RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));
        }

        $type = (string) $row['type'];
        $element = (string) $row['element'];

        if ((int) ($row['protected'] ?? 0) === 1 || ($type === 'component' && $element === 'com_breezingformsng')) {
            throw new \RuntimeException(Text::_('COM_BREEZINGFORMSNG_ABOUT_EXTENSION_UNINSTALL_PROTECTED'));
        }

        if (!Installer::getInstance()->uninstall($type, $extensionId)) {
            throw new \RuntimeException(Text::_('COM_BREEZINGFORMSNG_ABOUT_EXTENSION_UNINSTALL_REFUSED'));
        }

        return $type . '/' . ($type === 'plugin' ? (string) $row['folder'] . '/' : '') . $element;
    }
}
