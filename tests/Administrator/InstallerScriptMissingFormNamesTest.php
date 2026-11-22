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

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator;

use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

final class InstallerScriptMissingFormNamesTest extends TestCase
{
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testMigrationFillsStablePackageScopedNamesAndIsIdempotent(): void
    {
        eval(<<<'PHP'
            namespace Joomla\Database {
                interface DatabaseInterface {}
                final class ParameterType
                {
                    public const STRING = 'string';
                    public const INTEGER = 'integer';
                }
            }
            namespace Joomla\CMS\Installer {
                interface InstallerScriptInterface {}
            }
            namespace Joomla\CMS\Log {
                final class Log
                {
                    public const ALL = 0;
                    public const INFO = 1;
                    public const WARNING = 2;
                    public const ERROR = 3;
                    public static array $messages = [];
                    public static function addLogger(...$args): void {}
                    public static function add(
                        string $message,
                        int $priority = self::INFO,
                        string $category = ''
                    ): void {
                        self::$messages[] = $message;
                    }
                }
            }
            namespace Joomla\Filesystem {
                final class Folder
                {
                    public static function exists(string $path): bool
                    {
                        return is_dir($path);
                    }
                    public static function create(string $path): bool
                    {
                        return mkdir($path, 0777, true);
                    }
                }
            }
            namespace Joomla\CMS {
                final class Factory
                {
                    public static object $container;
                    public static function getContainer(): object
                    {
                        return self::$container;
                    }
                    public static function getApplication(): object
                    {
                        throw new \RuntimeException('No application in this test.');
                    }
                }
            }
            PHP);

        $logRoot = sys_get_temp_dir() . '/bfng-missing-name-' . bin2hex(random_bytes(6));
        define('JPATH_ADMINISTRATOR', $logRoot);
        define('JVERSION', '6.2.0');

        $rows = [
            ['id' => 1, 'name' => 'contact', 'title' => 'Existing', 'package' => 'alpha'],
            ['id' => 2, 'name' => '', 'title' => 'Contact', 'package' => 'alpha'],
            ['id' => 3, 'name' => '  ', 'title' => 'Contact', 'package' => 'alpha'],
            ['id' => 4, 'name' => '', 'title' => 'Contact', 'package' => 'beta'],
            ['id' => 5, 'name' => '', 'title' => 'Événement spécial!', 'package' => 'alpha'],
            ['id' => 6, 'name' => '', 'title' => '---', 'package' => 'alpha'],
            ['id' => 7, 'name' => 'legacy-name', 'title' => 'Legacy', 'package' => 'alpha'],
        ];
        $db = new class ($rows) implements \Joomla\Database\DatabaseInterface {
            /** @var list<array{id: int, name: string, title: string, package: string}> */
            public array $rows;
            public int $updates = 0;
            private ?object $query = null;

            /** @param list<array{id: int, name: string, title: string, package: string}> $rows */
            public function __construct(array $rows)
            {
                $this->rows = $rows;
            }

            public function createQuery(): object
            {
                return new class {
                    /** @var array<string, mixed> */
                    public array $bindings = [];

                    public function select(string|array $columns): self
                    {
                        return $this;
                    }

                    public function from(string $table): self
                    {
                        return $this;
                    }

                    public function order(string $column): self
                    {
                        return $this;
                    }

                    public function update(string $table): self
                    {
                        return $this;
                    }

                    public function set(string $expression): self
                    {
                        return $this;
                    }

                    public function where(string $expression): self
                    {
                        return $this;
                    }

                    public function bind(string $key, mixed $value, mixed $type = null): self
                    {
                        $this->bindings[$key] = $value;

                        return $this;
                    }
                };
            }

            public function quoteName(string|array $name): string|array
            {
                return $name;
            }

            public function setQuery(object $query): self
            {
                $this->query = $query;

                return $this;
            }

            /** @return list<array{id: int, name: string, title: string, package: string}> */
            public function loadAssocList(): array
            {
                return $this->rows;
            }

            public function execute(): void
            {
                $id = (int) ($this->query?->bindings[':id'] ?? 0);
                $name = (string) ($this->query?->bindings[':name'] ?? '');
                foreach ($this->rows as &$row) {
                    if ($row['id'] === $id) {
                        $row['name'] = $name;
                        ++$this->updates;
                        break;
                    }
                }
                unset($row);
            }
        };
        \Joomla\CMS\Factory::$container = new class ($db) {
            public function __construct(private object $database)
            {
            }

            public function get(string $id): object
            {
                return $this->database;
            }
        };

        require __DIR__ . '/../../script.php';
        $reflection = new \ReflectionClass('com_breezingformsngInstallerScript');
        $installer = $reflection->newInstanceWithoutConstructor();
        $migration = $reflection->getMethod('migrateMissingFormNames');
        $migration->invoke($installer);

        self::assertSame(
            ['contact', 'contact_1', 'contact_2', 'contact', 'v_nement_sp_cial', '', 'legacy-name'],
            array_column($db->rows, 'name')
        );
        self::assertSame(4, $db->updates);

        $migration->invoke($installer);
        self::assertSame(4, $db->updates, 'A second migration pass must not update any row.');

        if (is_file($logRoot . '/logs/breezingforms_install2.log')) {
            unlink($logRoot . '/logs/breezingforms_install2.log');
        }
        if (is_dir($logRoot . '/logs')) {
            rmdir($logRoot . '/logs');
        }
        if (is_dir($logRoot)) {
            rmdir($logRoot);
        }
    }
}
