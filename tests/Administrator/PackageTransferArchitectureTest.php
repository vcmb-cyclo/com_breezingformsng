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
        self::assertStringContainsString('private const VERSION = 1;', $source);
        self::assertStringContainsString("'scripts' =>", $source);
        self::assertStringContainsString("'pieces' =>", $source);
        self::assertStringContainsString("'forms' =>", $source);
        self::assertStringContainsString('transactionStart()', $source);
        self::assertStringContainsString('transactionRollback()', $source);
    }

    public function testPackageScreenIsLinkedFromTheConfigurationForm(): void
    {
        $config = file_get_contents(__DIR__ . '/../../administrator/components/com_breezingformsng/config.xml');
        $field = file_get_contents(__DIR__ . '/../../administrator/components/com_breezingformsng/src/Field/PackageTransferField.php');

        self::assertIsString($config);
        self::assertIsString($field);
        self::assertStringContainsString('type="packagetransfer"', $config);
        self::assertStringContainsString("view=packages", $field);
    }
}
