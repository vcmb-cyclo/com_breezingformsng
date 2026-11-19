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

namespace Vcmb\Component\BreezingformsNG\Tests\Site\Service\Upload;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Site\Service\Upload\UploadExtensionPolicy;
use Vcmb\Component\BreezingformsNG\Site\Service\Upload\TokenizedDirectoryResolver;

if (!defined('_JEXEC')) {
    define('_JEXEC', 1);
}

final class UploadExtensionPolicyTest extends TestCase
{
    public function testOnlyTheFirstFileUsedToBeChecked(): void
    {
        $policy = new UploadExtensionPolicy();

        self::assertSame('shell.exe', $policy->firstRefused(['photo.jpg', 'shell.exe'], 'jpg,png'));
        self::assertSame('shell.php', $policy->firstRefused(['photo.jpg', 'shell.php'], 'jpg,php'));
        self::assertNull($policy->firstRefused(['photo.jpg', 'logo.PNG', ''], ' jpg , png '));
    }

    #[DataProvider('executableNames')]
    public function testExecutableFilesAreRefusedEvenWithoutAnAllowList(string $name): void
    {
        self::assertSame($name, (new UploadExtensionPolicy())->firstRefused([$name], ''));
    }

    /** @return array<string, array{string}> */
    public static function executableNames(): array
    {
        return [
            'php' => ['shell.php'],
            'upper case' => ['SHELL.PHP'],
            'phtml' => ['a.phtml'],
            'double extension' => ['shell.php.jpg'],
            'htaccess' => ['.htaccess'],
            'path in the name' => ['..\\x\\shell.php5'],
        ];
    }

    public function testAnyOtherFileIsAcceptedWhenTheFormAllowsEverything(): void
    {
        $policy = new UploadExtensionPolicy();

        self::assertNull($policy->firstRefused(['report.pdf', 'archive.zip', 'noextension'], ''));
        self::assertNull($policy->firstRefused([], 'jpg'));
    }

    public function testAFileWithoutExtensionIsRefusedWhenAListIsSet(): void
    {
        self::assertSame('README', (new UploadExtensionPolicy())->firstRefused(['README'], 'jpg'));
    }

    #[DataProvider('folders')]
    public function testAFolderNeverLeavesItsBase(string $path, string $expected): void
    {
        $resolver = (new \ReflectionClass(TokenizedDirectoryResolver::class))->newInstanceWithoutConstructor();

        self::assertSame($expected, $resolver->makeSafeFolder($path));
    }

    /** @return array<string, array{string, string}> */
    public static function folders(): array
    {
        return [
            'plain' => ['uploads/user name/file', 'uploads/user_name/file'],
            'parent segments' => ['/var/www/uploads/../../etc/x', '/var/www/uploads/etc/x'],
            'value typed in a field' => ['uploads/../../configuration/', 'uploads/configuration/'],
            'current dir' => ['uploads/./a', 'uploads/a'],
            'doubled slashes' => ['uploads//a', 'uploads/a'],
            'dots inside a name stay' => ['uploads/a..b/c.d', 'uploads/a..b/c.d'],
            'absolute root kept' => ['/srv/up', '/srv/up'],
        ];
    }
}
