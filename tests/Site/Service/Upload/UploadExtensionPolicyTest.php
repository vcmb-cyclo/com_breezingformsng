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
            'trailing space' => ['shell.php '],
            'trailing dot' => ['shell.php.'],
            'trailing dots and spaces' => ['shell.php . .'],
            'space inside' => ['shell. php'],
            'no break space' => ["shell.php\u{00A0}"],
            'NUL truncation' => ["shell.php\0.jpg"],
            'NUL at the end' => ["shell.jpg\0.php"],
            'alternate data stream' => ['shell.php::$DATA'],
            'stream with extension' => ['shell.php:.jpg'],
            'full width' => ['shell.ｐｈｐ'],
            'right to left override' => ["shell.\u{202E}gpj.php"],
            'zero width' => ["shell.p\u{200B}hp"],
            'invalid utf-8' => ["shell\xFF.php"],
            'php 8 variants' => ['a.php8'],
            'phar' => ['a.phar'],
            'pht' => ['a.pht'],
            'inc' => ['config.inc'],
            'shtml' => ['a.shtml'],
            'cgi' => ['a.cgi'],
            'double cgi' => ['a.cgi.png'],
            'asp' => ['a.aspx'],
            'jsp' => ['a.jsp'],
            'user ini' => ['.user.ini'],
            'user ini upper case' => ['.USER.INI'],
            'php ini' => ['php.ini'],
            'web config' => ['web.config'],
            'web config in a path' => ['uploads/Web.Config'],
            'htpasswd' => ['.htpasswd'],
            'env' => ['.env'],
            'windows program' => ['setup.exe'],
            'batch' => ['run.BAT'],
            'powershell' => ['x.ps1'],
            'jar' => ['x.jar'],
            'html page' => ['page.html'],
            'svg image' => ['logo.SVG'],
            'xml' => ['data.xml'],
            'javascript' => ['x.js'],
            'only dots' => ['...'],
            'only spaces' => ['   '],
        ];
    }

    #[DataProvider('legitimateNames')]
    public function testLegitimateFilesAreAccepted(string $name): void
    {
        self::assertNull((new UploadExtensionPolicy())->firstRefused([$name], ''));
        self::assertTrue((new UploadExtensionPolicy())->isAccepted($name, [strtolower((string) pathinfo($name, PATHINFO_EXTENSION))]));
    }

    /** @return array<string, array{string}> */
    public static function legitimateNames(): array
    {
        return [
            'pdf' => ['report.pdf'],
            'jpg' => ['photo.jpg'],
            'jpeg upper case' => ['PHOTO.JPEG'],
            'png' => ['logo.png'],
            'zip' => ['archive.zip'],
            'docx' => ['letter.docx'],
            'xlsx' => ['sheet.xlsx'],
            'txt' => ['notes.txt'],
            'csv' => ['data.csv'],
            'mp4' => ['clip.mp4'],
            'accents' => ['résumé été.pdf'],
            'dots in name' => ['v1.2.final.pdf'],
            'spaces' => ['my report.pdf'],
            'php in a word' => ['phpbook.pdf'],
            'com in a name' => ['john.com.pdf'],
            'path with folders' => ['/Users/me/photo.jpg'],
        ];
    }

    public function testActiveContentIsAcceptedOnlyWhenTheFormListsItExplicitly(): void
    {
        $policy = new UploadExtensionPolicy();

        self::assertNull($policy->firstRefused(['logo.svg'], 'svg,png'));
        self::assertSame('logo.svg', $policy->firstRefused(['logo.svg'], 'png'));
        self::assertSame('logo.svg', $policy->firstRefused(['logo.svg'], ''));
    }

    public function testTheAllowListIsNotAWayAroundTheDeniedList(): void
    {
        $policy = new UploadExtensionPolicy();

        self::assertSame('a.phtml', $policy->firstRefused(['a.phtml'], 'phtml,jpg'));
        self::assertSame('a.htaccess', $policy->firstRefused(['a.htaccess'], 'htaccess'));
        self::assertSame('x.exe', $policy->firstRefused(['x.exe'], 'exe'));
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
