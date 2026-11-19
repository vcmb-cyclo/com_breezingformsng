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

namespace Vcmb\Component\BreezingformsNG\Tests\Site\Service\Integration;

use Joomla\Http\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Vcmb\Component\BreezingformsNG\Site\Service\Integration\MailchimpClient;

if (!class_exists(Http::class)) {
    eval('namespace Joomla\\Http; class Http {}');
}

final class MailchimpClientTest extends TestCase
{
    /** @return iterable<string, array{string}> */
    public static function hostileKeys(): iterable
    {
        yield 'path' => ['secretkey-evil.example/x'];
        yield 'query' => ['secretkey-us1?x'];
        yield 'fragment' => ['secretkey-us1#x'];
        yield 'userinfo' => ['secretkey-a@evil'];
        yield 'dot' => ['secretkey-evil.com'];
        yield 'uppercase' => ['secretkey-US1'];
        yield 'newline' => ["secretkey-us1\n"];
        yield 'no datacenter' => ['secretkey-'];
        yield 'no dash' => ['secretkey'];
    }

    #[DataProvider('hostileKeys')]
    public function testRefusesInvalidDatacenterWithoutLeakingTheKey(string $apiKey): void
    {
        $http = new class () extends Http {
            public int $calls = 0;

            public function delete($url, array $headers = [], $timeout = null)
            {
                $this->calls++;
            }

            public function put($url, $data, array $headers = [], $timeout = null)
            {
                $this->calls++;
            }
        };

        try {
            (new MailchimpClient($http))->request($apiKey, 'DELETE', 'lists/1');
            self::fail('Expected a RuntimeException.');
        } catch (RuntimeException $exception) {
            self::assertStringNotContainsString('secretkey', $exception->getMessage());
        }

        self::assertSame(0, $http->calls);
    }

    public function testBuildsTheHostFromAValidDatacenter(): void
    {
        $http = new class () extends Http {
            public string $url = '';

            public function delete($url, array $headers = [], $timeout = null)
            {
                $this->url = $url;

                return new class () {
                    public function getBody(): string
                    {
                        return '';
                    }

                    public function getStatusCode(): int
                    {
                        return 204;
                    }
                };
            }
        };

        self::assertSame([], (new MailchimpClient($http))->request('abc123-us21', 'DELETE', '/lists/1'));
        self::assertSame('https://us21.api.mailchimp.com/3.0/lists/1', $http->url);
    }
}
