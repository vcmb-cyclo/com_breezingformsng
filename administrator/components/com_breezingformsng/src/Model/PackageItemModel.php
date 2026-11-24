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

namespace Vcmb\Component\BreezingformsNG\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Date\Date;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Form\FormFactoryInterface;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Versioning\VersionableModelInterface;
use Joomla\CMS\Versioning\VersionableModelTrait;
use Joomla\Database\ParameterType;
use Joomla\Utilities\ArrayHelper;
use Vcmb\Component\BreezingformsNG\Administrator\Service\AuditUserNames;
use Vcmb\Component\BreezingformsNG\Administrator\Service\FormVersioning;
use Vcmb\Component\BreezingformsNG\Administrator\Service\ItemName;
use Vcmb\Component\BreezingformsNG\Administrator\Service\VersionOrigin;

/**
 * A code item kept in a package: a script or a piece.
 *
 * Its versions use the Joomla 6 version history of models, as articles do:
 * AdminModel::save() stores a version, AdminModel::delete() deletes them.
 * The first save of an item without versions keeps its previous state as
 * its version of origin.
 */
abstract class PackageItemModel extends AdminModel implements VersionableModelInterface
{
    use VersionableModelTrait;

    /**
     * The published state of an item in the trash.
     */
    public const int TRASHED = -2;

    /** The name of the item being copied, set only while copy() saves its copy. */
    private ?string $copiedName = null;

    /**
     * @param list<string> $values
     * @return array<string, string>
     */
    public function resolveAuditNames(array $values): array
    {
        return AuditUserNames::resolve($this->getDatabase(), $values);
    }

    /** @param list<string> $values
     *  @return array<string, int>
     */
    public function resolveAuditUserIds(array $values): array
    {
        return AuditUserNames::resolveUserIds($this->getDatabase(), $values);
    }

    /**
     * @param array<string, mixed> $config
     */
    public function __construct($config = [], ?MVCFactoryInterface $factory = null, ?FormFactoryInterface $formFactory = null)
    {
        parent::__construct($config, $factory, $formFactory);

        $this->typeAlias = 'com_breezingformsng.' . $this->getName();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function save($data)
    {
        $nameProblem = $this->namedByIdentifier()
            ? ItemName::problemOnSave((string) ($data['name'] ?? ''), $this->storedName((int) ($data['id'] ?? $this->getState($this->getName() . '.id'))), 30)
            : null;
        if ($nameProblem !== null) {
            $this->setError(Text::_($nameProblem));

            return false;
        }

        $this->keepOrigin((int) ($data['id'] ?? $this->getState($this->getName() . '.id')));

        return parent::save($data);
    }

    /**
     * The name the item keeps if it is saved as posted: the stored name of an existing item, the name of the
     * source while it is being copied (a copy keeps the name of its source, so it never derives an invalid one),
     * null for a new item. Such a name is not revalidated, so a legacy name does not block a save or a copy.
     */
    private function storedName(int $id): ?string
    {
        if ($id <= 0) {
            return $this->copiedName;
        }

        $table = $this->getTable();

        return $table->load($id) ? (string) $table->name : null;
    }

    /**
     * Whether the name of the item is an identifier (letters, digits, underscores). Pieces and
     * scripts are, the other items are free.
     */
    protected function namedByIdentifier(): bool
    {
        return false;
    }

    /**
     * Stores the current state of the item as its version of origin, kept
     * forever, when it has no version yet.
     */
    public function keepOrigin(int $id, string $note = ''): void
    {
        $db = $this->getDatabase();

        if (!FormVersioning::isEnabled() || VersionOrigin::missing($db, $this->typeAlias, [$id]) === []) {
            return;
        }

        $this->recordVersion($id, $note);
        VersionOrigin::keepForever($db, $this->typeAlias . '.' . $id);
    }

    /**
     * Stores the current state of the item, kept forever, before an update
     * replaces its code. It is stored even when the version history is
     * disabled: the replaced code must never be lost.
     */
    public function keepVersionBeforeReplacement(int $id, string $note): void
    {
        $this->recordVersion($id, $note, true);
        VersionOrigin::keepForever($this->getDatabase(), $this->typeAlias . '.' . $id);
    }

    /**
     * Stores the current state of an item written without save(), by an import.
     * A note is given to the version stored; a state already in the history
     * adds no version, and keeps its note.
     *
     * @param bool $always Store the version even when the version history is disabled
     */
    public function recordVersion(int $id, string $note = '', bool $always = false): void
    {
        if (!$always && !FormVersioning::isEnabled()) {
            return;
        }

        $this->setState($this->getName() . '.id', $id);
        $itemId = $this->typeAlias . '.' . $id;
        $last = $this->lastHistoryId($itemId);

        if (!$always ? !$this->saveHistory([], $this->typeAlias) : !$this->storeCurrentState($id)) {
            throw new \RuntimeException(Text::_('COM_BREEZINGFORMSNG_FORM_VERSION_NOT_STORED'));
        }

        $version = $this->lastHistoryId($itemId);

        if ($note !== '' && $version > $last) {
            $db = $this->getDatabase();
            // The note column of the history holds 255 characters.
            $note = mb_substr($note, 0, 255);
            $db->setQuery(
                $db->createQuery()
                    ->update($db->quoteName('#__history'))
                    ->set($db->quoteName('version_note') . ' = :note')
                    ->where($db->quoteName('version_id') . ' = :versionId')
                    ->bind(':note', $note)
                    ->bind(':versionId', $version, ParameterType::INTEGER)
            )->execute();
        }
    }

    /**
     * The history store of the model refuses when the option is off: this
     * stores the same data, as VersionableModelTrait::saveHistory() does.
     */
    private function storeCurrentState(int $id): bool
    {
        $table = $this->getTable();
        $table->load($id);
        $data = ArrayHelper::fromObject($table);

        foreach ($this->ignoreChanges as $ignore) {
            unset($data[$ignore]);
        }

        return (bool) $this->storeHistory($this->typeAlias, $id, ArrayHelper::toObject($data));
    }

    private function lastHistoryId(string $itemId): int
    {
        $db = $this->getDatabase();

        return (int) $db->setQuery(
            $db->createQuery()
                ->select('MAX(' . $db->quoteName('version_id') . ')')
                ->from($db->quoteName('#__history'))
                ->where($db->quoteName('item_id') . ' = :itemId')
                ->bind(':itemId', $itemId)
        )->loadResult();
    }

    /**
     * @param string               $name
     * @param string               $prefix
     * @param array<string, mixed> $options
     */
    public function getTable($name = '', $prefix = 'Site', $options = [])
    {
        return parent::getTable($name ?: ucfirst($this->getName()), $prefix, $options);
    }

    /**
     * @param array<string, mixed> $data
     * @param bool                 $loadData
     */
    public function getForm($data = [], $loadData = true)
    {
        return $this->loadForm('com_breezingformsng.' . $this->getName(), $this->getName(), ['control' => 'jform', 'load_data' => $loadData]);
    }

    /**
     * @return array<string, mixed>|object|false
     */
    protected function loadFormData()
    {
        $application = Factory::getApplication();

        if (!$application instanceof CMSApplication) {
            throw new \RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));
        }

        $data = $application->getUserState('com_breezingformsng.edit.' . $this->getName() . '.data', []);

        if (empty($data)) {
            $data = $this->getItem();
        }

        $this->preprocessData('com_breezingformsng.' . $this->getName(), $data);

        return $data;
    }

    /**
     * Stamps the author and the date of the change.
     */
    protected function prepareTable($table)
    {
        $now = (new Date())->toSql();
        $username = (string) $this->getCurrentUser()->username;

        if ((int) $table->id === 0) {
            $table->created = $now;
            $table->created_by = $username;
        }

        $table->package = (string) $table->package;
        $table->modified = $now;
        $table->modified_by = $username;
    }

    /**
     * Joomla stops at the first item it cannot delete and still runs the
     * controller hook: $pks ends up holding the ids actually deleted.
     *
     * @param array<mixed> $pks
     */
    public function delete(&$pks)
    {
        $ids = array_values(array_unique(ArrayHelper::toInteger((array) $pks)));

        if ($ids !== [] && !$this->allTrashed($ids)) {
            throw new \RuntimeException(Text::_('JLIB_APPLICATION_ERROR_DELETE_NOT_PERMITTED'));
        }

        $deleted = parent::delete($pks);
        $pks = $ids;

        if ($ids !== []) {
            $db = $this->getDatabase();
            $table = $this->getTable();
            $query = $db->createQuery()
                ->select($db->quoteName('id'))
                ->from($db->quoteName($table->getTableName()))
                ->whereIn($db->quoteName('id'), $ids);
            $pks = array_values(array_diff($ids, array_map('intval', $db->setQuery($query)->loadColumn())));
        }

        return $deleted;
    }

    /**
     * Only an item in the trash is deleted for good.
     */
    #[\Override]
    protected function canDelete($record)
    {
        return (int) ($record->published ?? 0) === self::TRASHED && parent::canDelete($record);
    }

    /** Whether this item is in the trash: it is restored from the trash list, not toggled in place. */
    public function isTrashed(int $id): bool
    {
        return $id > 0 && $this->allTrashed([$id]);
    }

    /**
     * Whether every id exists and is in the trash: the whole selection is
     * refused otherwise, whatever the order of its items.
     *
     * @param int[] $ids
     */
    private function allTrashed(array $ids): bool
    {
        $db = $this->getDatabase();
        $trashed = self::TRASHED;
        $query = $db->createQuery()
            ->select('COUNT(*)')
            ->from($db->quoteName($this->getTable()->getTableName()))
            ->whereIn($db->quoteName('id'), $ids)
            ->where($db->quoteName('published') . ' = :trashed')
            ->bind(':trashed', $trashed, ParameterType::INTEGER);

        return (int) $db->setQuery($query)->loadResult() === \count($ids);
    }

    /**
     * @param int[] $ids
     */
    public function copy(array $ids): int
    {
        $copied = 0;

        foreach ($ids as $id) {
            $source = $this->getTable();

            if (!$source->load($id)) {
                continue;
            }

            $data = get_object_vars($source);
            $data['id'] = 0;
            $this->setState($this->getName() . '.id', 0);
            $this->copiedName = (string) ($source->name ?? '');

            try {
                $saved = $this->save($data);
            } finally {
                $this->copiedName = null;
            }

            if (!$saved) {
                throw new \RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));
            }

            $copied++;
        }

        return $copied;
    }
}
