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
    public function __construct(private readonly DatabaseInterface $database)
    {
    }

    public function findPublishedPieceById(int $id): ?StoredCode
    {
        $query = $this->database->createQuery()
            ->select($this->database->quoteName(['id', 'name', 'code']))
            ->from($this->database->quoteName('#__facileforms_pieces'))
            ->where($this->database->quoteName('id') . ' = :id')
            ->where($this->database->quoteName('published') . ' = 1')
            ->bind(':id', $id, ParameterType::INTEGER);

        $this->database->setQuery($query, 0, 1);

        return $this->toStoredCode($this->database->loadAssoc());
    }

    public function findPublishedPieceByName(string $name): ?StoredCode
    {
        $query = $this->database->createQuery()
            ->select($this->database->quoteName(['id', 'name', 'code']))
            ->from($this->database->quoteName('#__facileforms_pieces'))
            ->where($this->database->quoteName('name') . ' = :name')
            ->where($this->database->quoteName('published') . ' = 1')
            ->order($this->database->quoteName('id') . ' DESC')
            ->bind(':name', $name, ParameterType::STRING);

        $this->database->setQuery($query, 0, 1);

        return $this->toStoredCode($this->database->loadAssoc());
    }

    public function findPublishedScriptById(int $id): ?StoredCode
    {
        $query = $this->database->createQuery()
            ->select($this->database->quoteName(['id', 'name', 'code']))
            ->from($this->database->quoteName('#__facileforms_scripts'))
            ->where($this->database->quoteName('id') . ' = :id')
            ->where($this->database->quoteName('published') . ' = 1')
            ->bind(':id', $id, ParameterType::INTEGER);

        $this->database->setQuery($query, 0, 1);

        return $this->toStoredCode($this->database->loadAssoc());
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

        return array_map(
            fn (array $row): StoredCode => $this->toStoredCode($row),
            $this->database->loadAssocList()
        );
    }

    /**
     * @param array<string, mixed>|null $row
     */
    private function toStoredCode(?array $row): ?StoredCode
    {
        return $row === null
            ? null
            : new StoredCode((int) $row['id'], (string) $row['name'], (string) $row['code']);
    }
}
