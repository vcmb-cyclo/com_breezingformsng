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

require_once __DIR__ . '/../../administrator/components/com_breezingformsng/libraries/crosstec/functions/helpers.php';

final class PaymentCacheHelperTest extends TestCase
{
    private string $tmp;
    private string $folder;

    protected function setUp(): void
    {
        if (!\defined('JPATH_SITE')) {
            \define('JPATH_SITE', sys_get_temp_dir() . '/bfng_site_' . bin2hex(random_bytes(6)));
        }

        $this->tmp = sys_get_temp_dir() . '/bfng_helper_' . bin2hex(random_bytes(6));
        $this->folder = $this->tmp . '/bfng_payment_cache';
        mkdir($this->folder, 0777, true);

        eval('namespace Joomla\\Filesystem; class File {'
            . 'public static function write($path, $content) { return file_put_contents($path, $content) !== false; }'
            . 'public static function delete($path) { return @unlink($path); } }');
        eval('namespace Joomla\\CMS; final class Factory {'
            . 'public static object $application;'
            . 'public static function getApplication(): object { return self::$application; } }');
        $tmp = $this->tmp;
        \Joomla\CMS\Factory::$application = new class ($tmp) {
            public function __construct(private readonly string $tmp)
            {
            }

            public function get(string $key): ?string
            {
                return $key === 'tmp_path' ? $this->tmp : null;
            }
        };
    }

    protected function tearDown(): void
    {
        foreach (glob($this->folder . '/*') ?: [] as $file) {
            unlink($file);
        }
        rmdir($this->folder);
        rmdir($this->tmp);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testNoQueuedEntryCountsAsSent(): void
    {
        self::assertTrue(bf_sendNotificationByPaymentCache(1, 2, 'admin'));
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testMalformedEntryFailsWithoutCrashingAndIsKept(): void
    {
        $file = $this->folder . '/1_2_admin_' . str_repeat('c', 32) . '.json';
        file_put_contents($file, '{broken');

        self::assertFalse(bf_sendNotificationByPaymentCache(1, 2, 'admin'));
        self::assertFileExists($file);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testSerializedObjectInTheNewFolderIsRejected(): void
    {
        $file = $this->folder . '/1_2_admin_' . str_repeat('d', 32) . '.json';
        file_put_contents($file, serialize(new \ArrayObject(['from' => 'x'])));

        self::assertFalse(bf_sendNotificationByPaymentCache(1, 2, 'admin'));
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testEntryRemovedByAnotherRequestWhileWaitingForTheLockCountsAsSent(): void
    {
        $file = $this->folder . '/1_2_admin_' . str_repeat('e', 32) . '.json';
        file_put_contents($file, '{}');

        $script = 'ini_set("display_errors","1");$h=fopen($argv[1],"rb");flock($h,LOCK_EX);echo "locked\n";fflush(STDOUT);'
            . 'usleep(600000);unlink($argv[1]);flock($h,LOCK_UN);';
        $process = proc_open([PHP_BINARY, '-r', $script, '--', $file], [1 => ['pipe', 'w']], $pipes);
        self::assertIsResource($process);
        self::assertSame("locked\n", fgets($pipes[1]));

        // Blocks on flock until the other request has sent and removed the entry.
        self::assertTrue(bf_sendNotificationByPaymentCache(1, 2, 'admin'));

        fclose($pipes[1]);
        proc_close($process);
        self::assertFileDoesNotExist($file);
    }
}
