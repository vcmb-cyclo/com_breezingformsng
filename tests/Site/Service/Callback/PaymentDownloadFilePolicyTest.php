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

namespace Vcmb\Component\BreezingformsNG\Tests\Site\Service\Callback;

use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Site\Service\Callback\PaymentDownloadFilePolicy;

if (!defined('_JEXEC')) {
    define('_JEXEC', 1);
}

final class PaymentDownloadFilePolicyTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = (string) realpath(sys_get_temp_dir()) . '/bfng-download-' . bin2hex(random_bytes(4));
        mkdir($this->directory);
    }

    protected function tearDown(): void
    {
        $this->remove($this->directory);
    }

    private function remove(string $path): void
    {
        if (is_link($path) || is_file($path)) {
            unlink($path);

            return;
        }

        foreach (scandir($path) ?: [] as $entry) {
            if ($entry !== '.' && $entry !== '..') {
                $this->remove($path . '/' . $entry);
            }
        }

        rmdir($path);
    }

    private function file(string $name, string $folder = ''): string
    {
        $directory = $this->directory . ($folder === '' ? '' : '/' . $folder);
        is_dir($directory) || mkdir($directory, 0777, true);
        $path = $directory . '/' . $name;
        file_put_contents($path, 'x');

        return (string) realpath($path);
    }

    public function testAnOrdinaryDownloadIsServed(): void
    {
        $policy = new PaymentDownloadFilePolicy();

        foreach (['ebook.pdf', 'archive.zip', 'song.mp3', 'book.epub', 'My Book.v2.PDF'] as $name) {
            self::assertTrue($policy->isServable($this->file($name)), $name);
        }
    }

    public function testALegitimateFileInAnAllowedFolderIsServedNextToASensitiveRoot(): void
    {
        mkdir($this->directory . '/private');
        $policy = new PaymentDownloadFilePolicy([$this->directory . '/private']);

        self::assertTrue($policy->isServable($this->file('paid.zip', 'downloads')));
        self::assertFalse($policy->isServable($this->file('paid.zip', 'private')));
        self::assertFalse($policy->isServable($this->file('paid.zip', 'private/deep/er')));
    }

    public function testASiblingFolderSharingThePrefixOfASensitiveRootIsServed(): void
    {
        mkdir($this->directory . '/html');
        $policy = new PaymentDownloadFilePolicy([$this->directory . '/html']);

        self::assertFalse($policy->isServable($this->file('a.zip', 'html')));
        self::assertTrue($policy->isServable($this->file('a.zip', 'html2')));
        self::assertTrue($policy->isServable($this->file('a.zip', 'html-private')));
    }

    public function testASensitiveRootGivenAsAFileIsRefused(): void
    {
        $configuration = $this->file('configuration.php', 'site');
        $policy        = new PaymentDownloadFilePolicy([$configuration]);

        self::assertFalse($policy->isServable($configuration));
        self::assertTrue($policy->isServable($this->file('guide.pdf', 'site')));
    }

    public function testASymbolicLinkIntoASensitiveRootIsJudgedByItsTarget(): void
    {
        $target = $this->file('secret.zip', 'private');
        mkdir($this->directory . '/public');
        symlink($target, $this->directory . '/public/innocent.zip');
        symlink($this->directory . '/private', $this->directory . '/public/folder');
        $policy = new PaymentDownloadFilePolicy([$this->directory . '/private']);

        // The service resolves the path first: the policy then sees the real location.
        self::assertFalse($policy->isServable((string) realpath($this->directory . '/public/innocent.zip')));
        self::assertFalse($policy->isServable((string) realpath($this->directory . '/public/folder/secret.zip')));
    }

    public function testAFileWithoutExtensionIsRefused(): void
    {
        $policy = new PaymentDownloadFilePolicy();

        self::assertFalse($policy->isServable($this->file('passwd')));
        self::assertFalse($policy->isServable($this->file('archive.')));
    }

    public function testSystemRootsAreRefusedWithoutConfiguration(): void
    {
        $policy = new PaymentDownloadFilePolicy();

        foreach (['/etc/passwd', '/etc/hostname', '/proc/self/environ', '/proc/version', '/sys/kernel/mm/ksm/run'] as $path) {
            if (is_file($path)) {
                self::assertFalse($policy->isServable((string) realpath($path)), $path);
            }
        }
    }

    public function testTheCodeAndConfigurationOfTheSiteAreNeverServed(): void
    {
        $policy = new PaymentDownloadFilePolicy();

        self::assertFalse($policy->isServable($this->file('configuration.php')));
        self::assertFalse($policy->isServable($this->file('script.PHP')));
        self::assertFalse($policy->isServable($this->file('.env')));
        self::assertFalse($policy->isServable($this->file('.htaccess')));
        self::assertFalse($policy->isServable($this->file('page.php.pdf')));
    }

    public function testAMissingFileOrAFolderIsNotServed(): void
    {
        $policy = new PaymentDownloadFilePolicy();

        self::assertFalse($policy->isServable(''));
        self::assertFalse($policy->isServable($this->directory . '/missing.pdf'));
        self::assertFalse($policy->isServable($this->directory));
    }

    public function testAMissingRootIsIgnored(): void
    {
        $policy = new PaymentDownloadFilePolicy(['', $this->directory . '/nowhere']);

        self::assertTrue($policy->isServable($this->file('ebook.pdf')));
    }
}
