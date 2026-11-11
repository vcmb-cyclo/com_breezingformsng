<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator\Mcp;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Administrator\Mcp\OriginValidator;

final class OriginValidatorTest extends TestCase
{
    /** @return iterable<string, array{string, string, bool}> */
    public static function origins(): iterable
    {
        yield 'non-browser client' => ['', 'https://example.org/api', true];
        yield 'same host and scheme' => ['https://example.org', 'https://example.org/api?x=1', true];
        yield 'case and explicit default port' => ['HTTPS://EXAMPLE.ORG:443', 'https://example.org/api', true];
        yield 'http default port' => ['http://example.org', 'http://example.org:80/path', true];
        yield 'same custom port' => ['https://example.org:8443', 'https://example.org:8443/api', true];
        yield 'different custom port' => ['https://example.org:8443', 'https://example.org/api', false];
        yield 'different scheme' => ['http://example.org', 'https://example.org/api', false];
        yield 'different host' => ['https://other.example.org', 'https://example.org/api', false];
        yield 'ipv6 default port' => ['https://[::1]:443', 'https://[0:0:0:0:0:0:0:1]/api', true];
        yield 'ipv6 different port' => ['https://[::1]:444', 'https://[::1]/api', false];
        yield 'opaque origin' => ['null', 'https://example.org/api', false];
        yield 'multiple origins' => ['https://example.org https://other.example.org', 'https://example.org/api', false];
        yield 'comma separated' => ['https://example.org,https://other.example.org', 'https://example.org/api', false];
        yield 'credentials' => ['https://user@example.org', 'https://example.org/api', false];
        yield 'path' => ['https://example.org/', 'https://example.org/api', false];
        yield 'query' => ['https://example.org?x=1', 'https://example.org/api', false];
        yield 'fragment' => ['https://example.org#x', 'https://example.org/api', false];
        yield 'empty port' => ['https://example.org:', 'https://example.org/api', false];
        yield 'zero port' => ['https://example.org:0', 'https://example.org/api', false];
        yield 'out of range port' => ['https://example.org:65536', 'https://example.org/api', false];
        yield 'invalid ipv6' => ['https://[::::]', 'https://[::1]/api', false];
        yield 'invalid host' => ['https://-bad.example', 'https://-bad.example/api', false];
        yield 'malformed expected URL' => ['https://example.org', 'example.org/api', false];
        yield 'non-http expected URL' => ['https://example.org', 'ftp://example.org/api', false];
        yield 'malformed scheme' => ['https:/example.org', 'https://example.org/api', false];
        yield 'newline' => ["https://example.org\n", 'https://example.org/api', false];
    }

    #[DataProvider('origins')]
    public function testOriginMatchesOnlyTheEndpointOrigin(string $origin, string $requestUrl, bool $allowed): void
    {
        self::assertSame($allowed, OriginValidator::isAllowed($origin, $requestUrl));
    }
}
