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

namespace Vcmb\Component\BreezingformsNG\Administrator\Service;

\defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\ContentHistory;
use Joomla\CMS\Versioning\Versioning;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/**
 * Versions of a form, kept in the Joomla content history: each save gives the
 * form its next version number and stores a copy of its row, structure
 * included, which can be restored later.
 */
final class FormVersioning
{
    public const TYPE_ALIAS = 'com_breezingformsng.form';

    /**
     * Columns a restore leaves as they are: identity, place in the list,
     * publication, lock and audit, and what QuickMode rebuilds from the
     * structure.
     */
    private const KEPT_COLUMNS = [
        'id', 'package', 'ordering', 'published', 'version',
        'checked_out', 'checked_out_time', 'created', 'created_by', 'modified', 'modified_by',
        'template_code', 'template_areas', 'template_code_processed',
    ];

    public function __construct(private readonly DatabaseInterface $db)
    {
    }

    public static function isEnabled(): bool
    {
        return (bool) ComponentHelper::getParams('com_breezingformsng')->get('save_history', 1);
    }

    /**
     * Gives the form its next version number and, when versions are enabled,
     * stores the form in the history.
     *
     * @param int|null $restoredFrom The version the form was restored from
     *
     * @return int The new version number
     */
    public function record(int $formId, ?int $restoredFrom = null): int
    {
        $db = $this->db;
        $db->setQuery(
            $db->createQuery()
                ->update($db->quoteName('#__facileforms_forms'))
                ->set($db->quoteName('version') . ' = ' . $db->quoteName('version') . ' + 1')
                ->where($db->quoteName('id') . ' = :id')
                ->bind(':id', $formId, ParameterType::INTEGER)
        )->execute();

        $row = $db->setQuery(
            $db->createQuery()
                ->select('*')
                ->from($db->quoteName('#__facileforms_forms'))
                ->where($db->quoteName('id') . ' = :id')
                ->bind(':id', $formId, ParameterType::INTEGER)
        )->loadAssoc();

        if (!\is_array($row)) {
            return 0;
        }

        $version = (int) $row['version'];

        if (self::isEnabled()) {
            $note = $restoredFrom === null
                ? Text::sprintf('COM_BREEZINGFORMSNG_FORM_VERSION_NOTE', $version)
                : Text::sprintf('COM_BREEZINGFORMSNG_FORM_VERSION_RESTORED_NOTE', $version, $restoredFrom);
            // The history hashes an object, as the Versionable behaviour plugin passes it.
            Versioning::store(self::TYPE_ALIAS, $formId, (object) $row, $note);
        }

        return $version;
    }

    /**
     * Removes the history of a deleted form.
     */
    public function forget(int $formId): void
    {
        Versioning::delete(self::TYPE_ALIAS, $formId);
    }

    /**
     * Reads a version of a form from the history.
     *
     * @return array{formId: int, version: int, columns: array<string, mixed>, structure: array<string, mixed>}
     */
    public function load(int $versionId): array
    {
        $history = new ContentHistory($this->db);
        $prefix = self::TYPE_ALIAS . '.';

        if ($versionId <= 0 || !$history->load($versionId) || !str_starts_with((string) $history->item_id, $prefix)) {
            throw new \RuntimeException(Text::_('COM_BREEZINGFORMSNG_FORM_VERSION_NOT_FOUND'));
        }

        $formId = (int) substr((string) $history->item_id, \strlen($prefix));
        $columns = json_decode((string) $history->version_data, true);
        $code = \is_array($columns) ? base64_decode((string) ($columns['template_code'] ?? ''), true) : false;
        $structure = \is_string($code) ? json_decode($code, true) : null;

        if ($formId <= 0 || !\is_array($columns) || !\is_array($structure)) {
            throw new \RuntimeException(Text::_('COM_BREEZINGFORMSNG_FORM_VERSION_NOT_FOUND'));
        }

        return [
            'formId' => $formId,
            'version' => (int) ($columns['version'] ?? 0),
            'columns' => $columns,
            'structure' => $structure,
        ];
    }

    /**
     * Puts back the options a version holds, for the columns the table still
     * has. The structure is restored separately, through QuickMode.
     *
     * @param array<string, mixed> $columns
     */
    public function restoreOptions(int $formId, array $columns): void
    {
        $db = $this->db;
        $existing = $db->getTableColumns('#__facileforms_forms');
        $query = $db->createQuery()->update($db->quoteName('#__facileforms_forms'));
        $set = 0;

        foreach ($columns as $column => $value) {
            if (\in_array($column, self::KEPT_COLUMNS, true) || !isset($existing[$column]) || \is_array($value)) {
                continue;
            }

            $query->set($db->quoteName($column) . ' = ' . ($value === null ? 'NULL' : $db->quote((string) $value)));
            $set++;
        }

        if ($set === 0) {
            return;
        }

        $query->where($db->quoteName('id') . ' = :id')->bind(':id', $formId, ParameterType::INTEGER);
        $db->setQuery($query)->execute();
    }

    /**
     * @return list<int>
     */
    public function elementIds(int $formId): array
    {
        $db = $this->db;

        return array_map('intval', $db->setQuery(
            $db->createQuery()
                ->select($db->quoteName('id'))
                ->from($db->quoteName('#__facileforms_elements'))
                ->where($db->quoteName('form') . ' = :form')
                ->bind(':form', $formId, ParameterType::INTEGER)
        )->loadColumn());
    }

    /**
     * Elements deleted since the version was saved get id 0, so that QuickMode
     * creates them again instead of updating a row that is gone.
     *
     * @param array<string, mixed> $node
     * @param list<int> $existingIds
     *
     * @return array<string, mixed>
     */
    public static function resetMissingElementIds(array $node, array $existingIds): array
    {
        if (
            \is_array($node['properties'] ?? null)
            && ($node['properties']['type'] ?? null) === 'element'
            && !\in_array((int) ($node['properties']['dbId'] ?? 0), $existingIds, true)
        ) {
            $node['properties']['dbId'] = 0;
        }

        if (\is_array($node['children'] ?? null)) {
            foreach ($node['children'] as $key => $child) {
                if (\is_array($child)) {
                    $node['children'][$key] = self::resetMissingElementIds($child, $existingIds);
                }
            }
        }

        return $node;
    }
}
