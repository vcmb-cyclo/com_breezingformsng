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
        $this->directory = sys_get_temp_dir() . '/bfng-download-' . bin2hex(random_bytes(4));
        mkdir($this->directory);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->directory . '/{,.}*', GLOB_BRACE) ?: [] as $file) {
            if (is_file($file) || is_link($file)) {
                unlink($file);
            }
        }
        rmdir($this->directory);
    }

    private function file(string $name): string
    {
        $path = $this->directory . '/' . $name;
        file_put_contents($path, 'x');

        return (string) realpath($path);
    }

    public function testAnOrdinaryDownloadIsServed(): void
    {
        self::assertTrue((new PaymentDownloadFilePolicy())->isServable($this->file('ebook.pdf')));
        self::assertTrue((new PaymentDownloadFilePolicy())->isServable($this->file('archive.zip')));
    }

    public function testTheCodeAndConfigurationOfTheSiteAreNeverServed(): void
    {
        $policy = new PaymentDownloadFilePolicy();

        self::assertFalse($policy->isServable($this->file('configuration.php')));
        self::assertFalse($policy->isServable($this->file('script.PHP')));
        self::assertFalse($policy->isServable($this->file('.env')));
        self::assertFalse($policy->isServable($this->file('.htaccess')));
    }

    public function testAMissingFileOrAFolderIsNotServed(): void
    {
        $policy = new PaymentDownloadFilePolicy();

        self::assertFalse($policy->isServable(''));
        self::assertFalse($policy->isServable($this->directory . '/missing.pdf'));
        self::assertFalse($policy->isServable($this->directory));
    }

    public function testASymbolicLinkIsJudgedByItsTarget(): void
    {
        $target = $this->file('configuration.php');
        $link   = $this->directory . '/innocent.pdf';
        symlink($target, $link);

        // The service resolves the path first: the policy then sees configuration.php.
        self::assertFalse((new PaymentDownloadFilePolicy())->isServable((string) realpath($link)));
    }
}
