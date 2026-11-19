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

namespace Vcmb\Component\BreezingformsNG\Tests\Site\Service\Notification;

use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Site\Service\Notification\PaymentNotificationCache;

final class PaymentNotificationCacheTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/bfng_pay_' . bin2hex(random_bytes(6));
        if (!class_exists(\Joomla\Filesystem\File::class)) {
            eval('namespace Joomla\\Filesystem; class File {'
                . 'public static function write($path, $content) {'
                . 'return file_put_contents($path, $content) !== false; }'
                . '}');
        }
        mkdir($this->root . '/tmp', 0777, true);
        mkdir($this->root . '/legacy', 0777, true);
    }

    protected function tearDown(): void
    {
        foreach (
            new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($this->root, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            ) as $item
        ) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($this->root);
    }

    /** @return array<string, mixed> */
    private function message(): array
    {
        return [
            'from' => 'shop@example.test',
            'fromname' => 'Shop é',
            'recipients' => ['a@example.test', 'b@example.test'],
            'subject' => 'Order',
            'body' => "<p>Hello \u{20AC}</p>",
            'attachment' => ['/tmp/a.pdf'],
            'isHtml' => true,
            'alt_sender' => '',
        ];
    }

    private function cache(): PaymentNotificationCache
    {
        return PaymentNotificationCache::fromTemporaryPath($this->root . '/tmp', $this->root . '/legacy');
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testStoreWritesProtectedJsonOutsideLegacyFolderAndRoundTrips(): void
    {
        $cache = $this->cache();
        $path = $cache->store(7, 42, 'admin', $this->message());

        self::assertNotNull($path);
        self::assertStringStartsWith($this->root . '/tmp/bfng_payment_cache/', $path);
        self::assertMatchesRegularExpression('/\/7_42_admin_[0-9a-f]{32}\.json$/', $path);
        self::assertSame($this->message(), json_decode((string) file_get_contents($path), true));
        self::assertFileExists($this->root . '/tmp/bfng_payment_cache/.htaccess');
        self::assertFileExists($this->root . '/tmp/bfng_payment_cache/index.html');
        self::assertSame(0, fileperms($this->root . '/tmp/bfng_payment_cache') & 0007);
        self::assertSame($path, $cache->find(7, 42, 'admin'));
        self::assertNull($cache->find(7, 42, 'mailback'));
        self::assertNull($cache->find(7, 43, 'admin'));
        self::assertSame($this->message(), $cache->decode($path, (string) file_get_contents($path)));
        self::assertFalse($cache->isLegacy($path));
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testLegacySerializedEntryIsFoundAndDecoded(): void
    {
        $legacy = $this->root . '/legacy/7_42_mailback_' . str_repeat('a', 32) . '.txt';
        file_put_contents($legacy, serialize($this->message()));

        $cache = $this->cache();
        self::assertSame($legacy, $cache->find(7, 42, 'mailback'));
        self::assertTrue($cache->isLegacy($legacy));
        self::assertSame($this->message(), $cache->decode($legacy, (string) file_get_contents($legacy)));
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testCorruptedOrIncompleteEntriesAreRejected(): void
    {
        $cache = $this->cache();
        $json = $this->root . '/tmp/bfng_payment_cache/x.json';
        $incomplete = $this->message();
        unset($incomplete['body']);
        $wrongType = $this->message();
        $wrongType['recipients'] = [['nested']];
        $wrongHtml = $this->message();
        $wrongHtml['isHtml'] = 'yes';

        $invalidPayloads = [
            '',
            '{not json',
            'null',
            '"text"',
            '[]',
            json_encode($incomplete),
            json_encode($wrongType),
            json_encode($wrongHtml),
        ];
        foreach ($invalidPayloads as $payload) {
            self::assertNull($cache->decode($json, (string) $payload), (string) $payload);
        }

        $legacy = $this->root . '/legacy/x.txt';
        foreach (['', 'a:1:{', 'b:0;', 's:3:"abc";'] as $payload) {
            self::assertNull($cache->decode($legacy, $payload), $payload);
        }
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testObjectInjectionInLegacyEntryNeverBuildsAnObject(): void
    {
        $GLOBALS['bfng_payment_probe'] = false;
        $payload = serialize(['from' => new PaymentCacheProbe()] + $this->message());
        $legacy = $this->root . '/legacy/x.txt';

        self::assertNull($this->cache()->decode($legacy, $payload));
        self::assertFalse($GLOBALS['bfng_payment_probe']);
        unset($GLOBALS['bfng_payment_probe']);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testPurgeExpiredCoversBothLocations(): void
    {
        $cache = $this->cache();
        $new = (string) $cache->store(1, 2, 'admin', $this->message());
        $legacy = $this->root . '/legacy/1_2_mailback_' . str_repeat('b', 32) . '.txt';
        file_put_contents($legacy, serialize($this->message()));

        $cache->purgeExpired(time());
        self::assertFileExists($new);
        self::assertFileExists($legacy);

        $cache->purgeExpired(time() + 86400 + 5);
        self::assertFileDoesNotExist($new);
        self::assertFileDoesNotExist($legacy);
        self::assertFileExists($this->root . '/tmp/bfng_payment_cache/.htaccess');
        self::assertFileExists($this->root . '/tmp/bfng_payment_cache/index.html');
    }
}
