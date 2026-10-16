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
}
