<?php

/**
 * BreezingForms NG - A Joomla Forms Application
 *
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 *
 * SPDX-License-Identifier: GPL-2.0-or-later
 **/

declare(strict_types=1);

namespace Joomla\CMS\Environment {
    if (!class_exists(Browser::class)) {
        class Browser
        {
            public function getAgentString(): string
            {
                return 'Test Agent';
            }

            public function getPlatform(): string
            {
                return 'Test Platform';
            }
        }
    }
}

namespace Vcmb\Component\BreezingformsNG\Tests\Site\Service\Runtime {

use Joomla\CMS\Environment\Browser;
use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Site\Service\Runtime\RequestMetadataResolver;

final class RequestMetadataResolverTest extends TestCase
{
    public function testResolvesVisibleIpAndBrowserMetadataWithoutProviderLookup(): void
    {
        $metadata = (new RequestMetadataResolver(new Browser()))->resolve(
            '192.0.2.10',
            false,
            false,
            'Unknown'
        );

        self::assertSame('192.0.2.10', $metadata->ip);
        self::assertSame('Test Agent', $metadata->agent);
        self::assertSame('Test Agent', $metadata->browser);
        self::assertSame('Test Platform', $metadata->platform);
        self::assertSame('Unknown', $metadata->provider);
    }

    public function testMasksIpAndSkipsProviderResolution(): void
    {
        $metadata = (new RequestMetadataResolver(new Browser()))->resolve(
            '192.0.2.10',
            true,
            true,
            'Unknown'
        );

        self::assertSame('0', $metadata->ip);
        self::assertSame('Unknown', $metadata->provider);
    }

    public function testUsesFallbackForInvalidProviderAddress(): void
    {
        $metadata = (new RequestMetadataResolver(new Browser()))->resolve(
            'not-an-ip',
            false,
            true,
            'Unknown'
        );

        self::assertSame('Unknown', $metadata->provider);
    }
}
}
