<?php

/**
 * BreezingForms NG - A Joomla Forms Application
 *
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 *
 * SPDX-License-Identifier: GPL-2.0-or-later
 *
 * Runs QuickmodeModel::save2() against an in-memory elements table holding the form 7 (ids 1 and 2)
 * and the form 9 (id 50). argv[1] is the JSON list of [dbId, name] pairs saved into the form 7;
 * the output is the JSON of the elements table afterwards.
 **/

declare(strict_types=1);

// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses

namespace Joomla\Database {
    interface DatabaseInterface
    {
    }

    interface QueryInterface
    {
    }

    final class ParameterType
    {
        public const INTEGER = 1;
        public const STRING = 2;
    }
}

namespace Joomla\CMS\MVC\Model {
    class BaseDatabaseModel
    {
        public function getCurrentUser(): object
        {
            return (object) ['username' => 'test'];
        }
    }
}

namespace Joomla\CMS\Date {
    final class Date
    {
        public function toSql(): string
        {
            return '2026-01-01 00:00:00';
        }
    }
}

namespace {
    define('_JEXEC', 1);

    final class MemoryQuery implements \Joomla\Database\QueryInterface
    {
        public string $kind = '';
        public string $table = '';
        /** @var list<string> */
        public array $wheres = [];
        /** @var list<string> */
        public array $sets = [];
        /** @var array<string, mixed> */
        public array $binds = [];
        /** @var list<int> */
        public array $notIn = [];

        public function select(mixed $columns): self
        {
            $this->kind = 'select';

            return $this;
        }

        public function update(string $table): self
        {
            $this->kind = 'update';
            $this->table = $table;

            return $this;
        }

        public function insert(string $table): self
        {
            $this->kind = 'insert';
            $this->table = $table;

            return $this;
        }

        public function delete(string $table): self
        {
            $this->kind = 'delete';
            $this->table = $table;

            return $this;
        }

        public function from(string $table): self
        {
            $this->table = $table;

            return $this;
        }

        public function set(string $expression): self
        {
            $this->sets[] = trim(explode('=', $expression)[0]);

            return $this;
        }

        public function where(string $condition): self
        {
            $this->wheres[] = $condition;

            return $this;
        }

        public function whereNotIn(string $column, array $values, mixed $type = null): self
        {
            $this->notIn = array_map('intval', $values);

            return $this;
        }

        public function bind(string $name, mixed &$value, mixed $type = null): self
        {
            $this->binds[$name] = &$value;

            return $this;
        }

        public function columns(array $columns): self
        {
            $this->sets = $columns;

            return $this;
        }

        public function values(string $values): self
        {
            return $this;
        }
    }

    final class MemoryDatabase implements \Joomla\Database\DatabaseInterface
    {
        /** @var array<int, array<string, mixed>> */
        public array $elements = [
            1 => ['id' => 1, 'form' => 7, 'name' => 'a', 'title' => 'Own A'],
            2 => ['id' => 2, 'form' => 7, 'name' => 'b', 'title' => 'Own B'],
            50 => ['id' => 50, 'form' => 9, 'name' => 'c', 'title' => 'Foreign C'],
        ];
        private int $nextId = 100;
        private MemoryQuery $query;

        public function createQuery(): MemoryQuery
        {
            return new MemoryQuery();
        }

        public function quoteName(string|array $name): string|array
        {
            return $name;
        }

        public function setQuery(MemoryQuery $query): self
        {
            $this->query = $query;

            return $this;
        }

        public function execute(): bool
        {
            $query = $this->query;

            if ($query->table !== '#__facileforms_elements') {
                return true;
            }

            if ($query->kind === 'insert') {
                $id = $this->nextId++;
                $row = ['id' => $id];

                foreach ($query->sets as $column) {
                    $row[$column] = $query->binds[':' . $column];
                }

                $this->elements[$id] = $row;
            } elseif ($query->kind === 'update') {
                foreach ($this->matching($query) as $id) {
                    foreach ($query->sets as $column) {
                        $this->elements[$id][$column] = $query->binds[':' . $column];
                    }
                }
            } elseif ($query->kind === 'delete') {
                foreach ($this->matching($query) as $id) {
                    if (!in_array($id, $query->notIn, true)) {
                        unset($this->elements[$id]);
                    }
                }
            }

            return true;
        }

        public function insertid(): int
        {
            return $this->nextId - 1;
        }

        public function loadObjectList(): array
        {
            if ($this->query->table === '#__facileforms_forms') {
                return [(object) ['id' => 7]];
            }

            return array_map(static fn (int $id): object => (object) ['id' => $id], $this->matching($this->query));
        }

        public function loadColumn(): array
        {
            return $this->matching($this->query);
        }

        public function loadResult(): string
        {
            return '';
        }

        /**
         * @return list<int>
         */
        private function matching(MemoryQuery $query): array
        {
            $ids = [];

            foreach ($this->elements as $id => $row) {
                $keep = true;

                foreach ($query->wheres as $condition) {
                    $keep = $keep && match (true) {
                        $condition === 'id = :dbId' => $row['id'] === $query->binds[':dbId'],
                        $condition === 'name = :name' => $row['name'] === $query->binds[':name'],
                        $condition === 'form = :form' => (int) $row['form'] === $query->binds[':form'],
                        $condition === 'form = :ownerForm' => (int) $row['form'] === $query->binds[':ownerForm'],
                        default => throw new \LogicException('Unexpected condition ' . $condition),
                    };
                }

                if ($keep) {
                    $ids[] = $id;
                }
            }

            return $ids;
        }
    }

    require dirname(__DIR__, 3) . '/components/com_breezingformsng/src/Service/Submission/SofortOnlyFormDetector.php';
    require dirname(__DIR__, 3) . '/administrator/components/com_breezingformsng/src/Model/QuickmodeModel.php';

    $areas = [['elements' => []]];

    foreach (json_decode($argv[1], true, 512, JSON_THROW_ON_ERROR) as $position => [$dbId, $name]) {
        $areas[0]['elements'][] = [
            'dbId' => $dbId, 'qId' => 'q' . $position, 'name' => $name, 'title' => 'Saved ' . $name,
            'bfType' => 'Text', 'orderNumber' => $position, 'mailback' => 0, 'mailbackfile' => 0,
            'flag1' => 0, 'flag2' => 0, 'data1' => '', 'data2' => '', 'data3' => '',
            'script1cond' => 0, 'script1id' => 0, 'script1code' => '', 'script1flag1' => 0, 'script1flag2' => 0,
            'script2cond' => 0, 'script2id' => 0, 'script2code' => '', 'script2flag1' => 0, 'script2flag2' => 0,
            'script2flag3' => 0, 'script2flag4' => 0, 'script2flag5' => 0,
            'script3cond' => 0, 'script3id' => 0, 'script3code' => '', 'script3msg' => '',
        ];
    }

    $class = new \ReflectionClass(\Vcmb\Component\BreezingformsNG\Administrator\Model\QuickmodeModel::class);
    $model = $class->newInstanceWithoutConstructor();
    $database = new MemoryDatabase();
    $class->getProperty('db')->setValue($model, $database);
    $templateCode = base64_encode((string) json_encode(['properties' => [], 'children' => []]));
    $class->getMethod('save2')->invoke($model, 7, 'f', 't', 'd', $templateCode, $areas, 1);

    echo json_encode($database->elements, JSON_THROW_ON_ERROR);
}
