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

use PHPUnit\Framework\TestCase;

final class QueryBuilderApiTest extends TestCase
{
    private const ADMIN = __DIR__ . '/../../administrator/components/com_breezingformsng';

    /**
     * The pieces of the standard library are PHP stored in XML and installed
     * on every site, so the PHP checks do not see them.
     */
    public function testTheStandardLibraryPiecesUseCreateQuery(): void
    {
        $package = file_get_contents(self::ADMIN . '/packages/stdlib.english.xml');

        self::assertIsString($package);
        self::assertStringNotContainsString('getQuery(true)', $package);
        self::assertStringContainsString('$db-\x3EcreateQuery()', $package);
    }

    public function testTheCompatibilityDatabaseOffersCreateQuery(): void
    {
        $factory = file_get_contents(self::ADMIN . '/plugins/bfcompat/src/Compat/BFFactory.php');

        self::assertIsString($factory);
        self::assertStringContainsString("public function createQuery()\n    {\n        return \$this->dbo->createQuery();", $factory);
        self::assertStringContainsString('return $this->last_failed_query;', $factory);
        self::assertStringNotContainsString('getQuery($new)', $factory);
    }
}
