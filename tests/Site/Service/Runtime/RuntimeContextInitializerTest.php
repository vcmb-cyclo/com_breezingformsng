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

namespace Joomla\CMS\Uri {
    if (!class_exists(Uri::class)) {
        final class Uri
        {
            public static function root(bool $pathOnly = false): string
            {
                return $pathOnly ? '/subsite' : 'https://example.test/subsite/';
            }
        }
    }
}

namespace Vcmb\Component\BreezingformsNG\Tests\Site\Service\Runtime {

require_once __DIR__ . '/../Rendering/QuickMode/joomla-cmsapplication-stub.php';

use Joomla\CMS\Application\CMSApplication;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Vcmb\Component\BreezingformsNG\Site\Configuration\FormConfiguration;
use Vcmb\Component\BreezingformsNG\Site\Service\Runtime\RuntimeContextInitializer;

final class RuntimeContextInitializerTest extends TestCase
{
    public function testNormalizesExplicitSiteUrlAndBuildsComponentUrl(): void
    {
        $initializer = new RuntimeContextInitializer(
            new CMSApplication(),
            $this->configuration(0)
        );

        self::assertSame(
            [
                'siteUrl' => 'https://example.test',
                'componentUrl' => 'https://example.test/components/com_breezingformsng',
                'otherParameters' => ['Itemid' => '42'],
            ],
            $initializer->initialize('https://example.test///', null, ['Itemid' => '42'])
        );
    }

    public function testResolvesForwardedHttpsServerUrlAndContentParameters(): void
    {
        $application = new CMSApplication();
        $input = $application->getInput();
        $input->values = [
            'option' => 'com_content',
            'Itemid' => '42',
            'task' => 'article',
            'sectionid' => '3',
            'id' => '99',
            'ignored' => 'value',
        ];
        $input->server->values = [
            'HTTPS' => 'off',
            'HTTP_X_FORWARDED_PROTO' => 'https',
            'HTTP_HOST' => 'example.test:8443',
            'SERVER_PORT' => 8443,
            'PHP_SELF' => '/index.php',
        ];

        $result = (new RuntimeContextInitializer($application, $this->configuration(1)))->initialize(
            null,
            '/components/custom',
            null
        );

        self::assertSame('https://example.test:8443', $result['siteUrl']);
        self::assertSame('/components/custom', $result['componentUrl']);
        self::assertSame(
            [
                'option' => 'com_content',
                'Itemid' => '42',
                'task' => 'article',
                'sectionid' => '3',
                'id' => '99',
            ],
            $result['otherParameters']
        );
    }

    public function testUsesJoomlaRootAndContactParameterSetByDefault(): void
    {
        $application = new CMSApplication();
        $application->getInput()->values = [
            'option' => 'com_contacts',
            'id' => '18',
            'Itemid' => '27',
            'task' => 'contact.display',
            'catid' => '4',
            'view' => 'contact',
            'contact_id' => '18',
        ];

        $result = (new RuntimeContextInitializer($application, $this->configuration(0)))->initialize(
            null,
            null,
            null
        );

        self::assertSame(rtrim(\Joomla\CMS\Uri\Uri::root(), '/'), $result['siteUrl']);
        self::assertSame(
            rtrim(\Joomla\CMS\Uri\Uri::root(), '/') . '/components/com_breezingformsng',
            $result['componentUrl']
        );
        self::assertSame(
            [
                'option' => 'com_contacts',
                'id' => '18',
                'Itemid' => '27',
                'task' => 'contact.display',
                'catid' => '4',
                'view' => 'contact',
                'contact_id' => '18',
            ],
            $result['otherParameters']
        );
    }

    public function testCollectsOnlyWeblinksParameters(): void
    {
        $application = new CMSApplication();
        $application->getInput()->values = [
            'option' => 'com_weblinks',
            'Itemid' => '11',
            'catid' => '6',
            'task' => 'links.display',
            'id' => '99',
        ];

        $result = (new RuntimeContextInitializer($application, $this->configuration(0)))->initialize(
            'https://example.test/',
            null,
            null
        );

        self::assertSame(
            [
                'option' => 'com_weblinks',
                'Itemid' => '11',
                'catid' => '6',
            ],
            $result['otherParameters']
        );
    }

    public function testUsesDefaultParameterSetAndIgnoresArrayValues(): void
    {
        $application = new CMSApplication();
        $application->getInput()->values = [
            'option' => ['com_content'],
            'Itemid' => ['11'],
        ];

        $result = (new RuntimeContextInitializer($application, $this->configuration(0)))->initialize(
            'https://example.test',
            '/component',
            null
        );

        self::assertSame([], $result['otherParameters']);
    }

    private function configuration(int $liveSite): FormConfiguration
    {
        $configuration = (new ReflectionClass(FormConfiguration::class))->newInstanceWithoutConstructor();
        $configuration->livesite = $liveSite;

        return $configuration;
    }
}
}
