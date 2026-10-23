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

final class QueryBuilderApiTest extends TestCase
{
    private const ROOT = __DIR__ . '/../..';

    /**
     * PHP kept outside .php files: the standard library pieces are PHP stored
     * in XML and installed on every site, the smoke test runs PHP from a
     * shell script.
     */
    public function testPhpOutsidePhpFilesUsesCreateQuery(): void
    {
        $files = [
            self::ROOT . '/administrator/components/com_breezingformsng/packages/stdlib.english.xml',
            ...glob(self::ROOT . '/scripts/*.sh'),
        ];

        foreach ($files as $file) {
            $source = file_get_contents($file);

            self::assertIsString($source);
            self::assertStringNotContainsString('getQuery(true)', $source, basename($file));
        }
    }

    /**
     * BFFactory.php declares BFFile, which extends Joomla's File: it is
     * loaded with its own stubs, apart from the File stub of other tests.
     */
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testTheCompatibilityDatabaseBuildsNewQueriesWithCreateQuery(): void
    {
        eval('namespace Joomla\Database; interface DatabaseInterface {} interface QueryInterface {}');
        eval('namespace Joomla\Filesystem; class File {}');
        eval('namespace Joomla\CMS; final class Factory {
            public static object $container;
            public static function getContainer(): object { return self::$container; }
        }');

        $database = new class () implements \Joomla\Database\DatabaseInterface {
            /** @var list<string> */
            public array $calls = [];

            public function setQuery($query, $offset = 0, $limit = 0): void
            {
                throw new \RuntimeException('Query failed');
            }

            public function createQuery(): \Joomla\Database\QueryInterface
            {
                $this->calls[] = 'createQuery';

                return new class () implements \Joomla\Database\QueryInterface {};
            }

            public function getQuery(...$arguments): string
            {
                $this->calls[] = $arguments === [] ? 'getQuery()' : 'getQuery($new)';

                return 'current query';
            }
        };

        \Joomla\CMS\Factory::$container = new class ($database) {
            public function __construct(private readonly object $database)
            {
            }

            public function get(string $id): object
            {
                return $this->database;
            }
        };

        require_once self::ROOT
            . '/administrator/components/com_breezingformsng/plugins/bfcompat/src/Compat/BFFactory.php';

        $dbo = new \BFDbo();

        self::assertInstanceOf(\Joomla\Database\QueryInterface::class, $dbo->createQuery());
        self::assertInstanceOf(\Joomla\Database\QueryInterface::class, $dbo->getQuery(true));
        self::assertSame('current query', $dbo->getQuery());

        // After a failed query, getQuery() hands back that query, but a new
        // query is still a new one.
        $dbo->setQuery('SELECT broken');

        self::assertSame('SELECT broken', $dbo->getQuery());
        self::assertInstanceOf(\Joomla\Database\QueryInterface::class, $dbo->getQuery(true));
        self::assertSame(['createQuery', 'createQuery', 'getQuery()', 'createQuery'], $database->calls);
    }
}
