<?php

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator;

use PHPUnit\Framework\TestCase;

final class PackageTransferArchitectureTest extends TestCase
{
    public function testTransferUsesTheVersionedBfngFormat(): void
    {
        $source = file_get_contents(__DIR__ . '/../../administrator/components/com_breezingformsng/src/Model/PackageTransferModel.php');

        self::assertIsString($source);
        self::assertStringContainsString("private const FORMAT = 'breezingformsng-package';", $source);
        self::assertStringContainsString('private const VERSION = 2;', $source);
        self::assertStringContainsString("[1, self::VERSION]", $source);
        self::assertStringContainsString("'scripts' =>", $source);
        self::assertStringContainsString("'pieces' =>", $source);
        self::assertStringContainsString("'forms' =>", $source);
        self::assertStringContainsString("'menus' =>", $source);
        self::assertStringContainsString("'metadata' =>", $source);
        self::assertStringContainsString('includeMenuAncestors(', $source);
        self::assertStringContainsString('transactionStart()', $source);
        self::assertStringContainsString('transactionRollback()', $source);
    }

    public function testPackageTransferIsNotExposedInTheJoomlaConfigurationForm(): void
    {
        $config = file_get_contents(__DIR__ . '/../../administrator/components/com_breezingformsng/config.xml');

        self::assertIsString($config);
        self::assertStringNotContainsString('name="package_transfer"', $config);
        self::assertStringNotContainsString('COM_BREEZINGFORMSNG_IMPORT_EXPORT', $config);
    }
}
