<?php
/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Site\Service\Scripting;

\defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

final class Repository
{
    /**
     * Code already read during this request: a form asks for the same entry
     * many times. A missing entry is kept too.
     *
     * @var array<string, StoredCode|null>
     */
    private array $found = [];

    public function __construct(private readonly DatabaseInterface $database)
    {
    }

    public function findPublishedPieceById(int $id): ?StoredCode
    {
        $key = 'piece-id:' . $id;

        if (\array_key_exists($key, $this->found)) {
            return $this->found[$key];
        }

        $query = $this->database->createQuery()
            ->select($this->database->quoteName(['id', 'name', 'code']))
            ->from($this->database->quoteName('#__facileforms_pieces'))
            ->where($this->database->quoteName('id') . ' = :id')
            ->where($this->database->quoteName('published') . ' = 1')
            ->bind(':id', $id, ParameterType::INTEGER);

        $this->database->setQuery($query, 0, 1);

        return $this->found[$key] = $this->toStoredCode($this->database->loadAssoc());
    }

    public function findPublishedPieceByName(string $name): ?StoredCode
    {
        $key = 'piece-name:' . $name;

        if (\array_key_exists($key, $this->found)) {
            return $this->found[$key];
        }

        $query = $this->database->createQuery()
            ->select($this->database->quoteName(['id', 'name', 'code']))
            ->from($this->database->quoteName('#__facileforms_pieces'))
            ->where($this->database->quoteName('name') . ' = :name')
            ->where($this->database->quoteName('published') . ' = 1')
            ->order($this->database->quoteName('id') . ' DESC')
            ->bind(':name', $name, ParameterType::STRING);

        $this->database->setQuery($query, 0, 1);

        return $this->found[$key] = $this->toStoredCode($this->database->loadAssoc());
    }

    public function findPublishedScriptById(int $id): ?StoredCode
    {
        $key = 'script-id:' . $id;

        if (\array_key_exists($key, $this->found)) {
            return $this->found[$key];
        }

        $query = $this->database->createQuery()
            ->select($this->database->quoteName(['id', 'name', 'code']))
            ->from($this->database->quoteName('#__facileforms_scripts'))
            ->where($this->database->quoteName('id') . ' = :id')
            ->where($this->database->quoteName('published') . ' = 1')
            ->bind(':id', $id, ParameterType::INTEGER);

        $this->database->setQuery($query, 0, 1);

        return $this->found[$key] = $this->toStoredCode($this->database->loadAssoc());
    }

    /**
     * @return list<StoredCode>
     */
    public function getPublishedScripts(): array
    {
        $query = $this->database->createQuery()
            ->select($this->database->quoteName(['id', 'name', 'code']))
            ->from($this->database->quoteName('#__facileforms_scripts'))
            ->where($this->database->quoteName('published') . ' = 1')
            ->order([
                $this->database->quoteName('type'),
                $this->database->quoteName('title'),
                $this->database->quoteName('name'),
                $this->database->quoteName('id') . ' DESC',
            ]);

        $this->database->setQuery($query);

        return array_values(array_map(
            fn (array $row): StoredCode => $this->storedCodeFromRow($row),
            $this->database->loadAssocList()
        ));
    }

    /**
     * @param array<string, mixed>|null $row
     */
    private function toStoredCode(?array $row): ?StoredCode
    {
        return $row === null ? null : $this->storedCodeFromRow($row);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function storedCodeFromRow(array $row): StoredCode
    {
        return new StoredCode((int) $row['id'], (string) $row['name'], (string) $row['code']);
    }
}
