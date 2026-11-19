<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Administrator\Service;

\defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/**
 * Joomla database implementation of the record date conversion storage. The
 * marker lives in the key/value table #__facileforms_config, which is InnoDB
 * like the records table.
 */
final class DatabaseRecordModifiedDateStore implements RecordModifiedDateStore
{
    public const MARKER_KEY = 'rec_modified_utc';

    public function __construct(private readonly DatabaseInterface $db)
    {
    }

    public function getMarker(): ?string
    {
        $key = self::MARKER_KEY;
        $query = $this->db->createQuery()
            ->select($this->db->quoteName('value'))
            ->from($this->db->quoteName('#__facileforms_config'))
            ->where($this->db->quoteName('id') . ' = :id')
            ->bind(':id', $key, ParameterType::STRING);
        $value = $this->db->setQuery($query)->loadResult();

        return $value === null ? null : (string) $value;
    }

    public function setMarker(string $state): void
    {
        $row = (object) ['id' => self::MARKER_KEY, 'value' => $state];

        if ($this->getMarker() === null) {
            $this->db->insertObject('#__facileforms_config', $row);

            return;
        }

        $this->db->updateObject('#__facileforms_config', $row, 'id');
    }

    public function loadModifiedDates(): array
    {
        $query = $this->db->createQuery()
            ->select($this->db->quoteName(['id', 'modified']))
            ->from($this->db->quoteName('#__facileforms_records'))
            ->where($this->db->quoteName('modified') . ' IS NOT NULL');
        $dates = [];

        foreach ((array) $this->db->setQuery($query)->loadObjectList() as $row) {
            $dates[(int) $row->id] = (string) $row->modified;
        }

        return $dates;
    }

    public function updateModified(int $id, string $modified): void
    {
        $query = $this->db->createQuery()
            ->update($this->db->quoteName('#__facileforms_records'))
            ->set($this->db->quoteName('modified') . ' = :modified')
            ->where($this->db->quoteName('id') . ' = :id')
            ->bind(':modified', $modified, ParameterType::STRING)
            ->bind(':id', $id, ParameterType::INTEGER);
        $this->db->setQuery($query)->execute();
    }

    public function transactionStart(): void
    {
        $this->db->transactionStart();
    }

    public function transactionCommit(): void
    {
        $this->db->transactionCommit();
    }

    public function transactionRollback(): void
    {
        $this->db->transactionRollback();
    }
}
