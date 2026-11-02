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
    if (!class_exists(Browser::class, false)) {
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

namespace Joomla\CMS\Date {
    if (!class_exists(Date::class, false)) {
        class Date extends \DateTime
        {
            public function format(string $format, bool $local = false): string
            {
                return parent::format($format);
            }

            public function getOffsetFromGMT(): int
            {
                return (int) $this->format('Z');
            }
        }
    }
}

namespace Vcmb\Component\BreezingformsNG\Tests\Site\Service\Runtime {

require_once __DIR__ . '/../Rendering/QuickMode/joomla-cmsapplication-stub.php';
require_once __DIR__ . '/../Rendering/QuickMode/joomla-text-stub.php';

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Environment\Browser;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Vcmb\Component\BreezingformsNG\Site\Configuration\FormConfiguration;
use Vcmb\Component\BreezingformsNG\Site\Service\Runtime\FormDisplayContextResolver;
use Vcmb\Component\BreezingformsNG\Site\Service\Runtime\FormPathResolver;
use Vcmb\Component\BreezingformsNG\Site\Service\Runtime\ProcessorRuntimeContextInitializer;
use Vcmb\Component\BreezingformsNG\Site\Service\Runtime\RequestMetadataResolver;
use Vcmb\Component\BreezingformsNG\Site\Service\Runtime\SubmissionTimestampFactory;

final class ProcessorRuntimeContextInitializerTest extends TestCase
{
    protected function setUp(): void
    {
        defined('JPATH_SITE') || define('JPATH_SITE', '/srv/joomla');
    }

    public function testAssemblesRequestDisplayAndPathState(): void
    {
        $application = new CMSApplication();
        $application->config = ['offset' => 'UTC'];
        $application->getInput()->server->values = ['REMOTE_ADDR' => '192.0.2.10'];
        $configuration = $this->configuration();

        $context = $this->initializer()->initialize(
            $application,
            $configuration,
            $this->formrow(),
            12,
            2,
            '/var/www',
            'https://example.test'
        );

        self::assertSame('192.0.2.10', $context->requestMetadata->ip);
        self::assertSame('Test Agent', $context->requestMetadata->agent);
        self::assertSame('Test Platform', $context->requestMetadata->platform);
        self::assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/',
            $context->submitted
        );
        self::assertSame('ff_form12', $context->display->formId);
        self::assertSame('https://example.test', $context->display->homepage);
        self::assertTrue($context->display->canRun);
        self::assertSame('/var/www/images/contact/2', $context->paths->images);
        self::assertSame('/var/www/images/contact/2/uploads/4/{cbsite}', $context->paths->uploads);
    }

    public function testPreservesMaskedRequestState(): void
    {
        $application = new CMSApplication();
        $application->config = ['offset' => 'UTC'];
        $application->getInput()->server->values = ['REMOTE_ADDR' => '192.0.2.10'];
        $configuration = $this->configuration();
        $configuration->disable_ip = 1;

        $context = $this->initializer()->initialize(
            $application,
            $configuration,
            $this->formrow(),
            9,
            1,
            '/var/www',
            'https://example.test'
        );

        self::assertSame('0', $context->requestMetadata->ip);
        self::assertSame('ff_form9', $context->display->formId);
        self::assertSame('https://example.test', $context->display->homepage);
        self::assertFalse($context->display->showGrid);
    }

    public function testFacadeDelegatesRuntimeContextAssembly(): void
    {
        $source = file_get_contents(
            __DIR__ . '/../../../../components/com_breezingformsng/src/Support/processor_facade.php'
        );

        self::assertIsString($source);
        self::assertStringContainsString('new ProcessorRuntimeContextInitializer(', $source);
        self::assertStringNotContainsString('$ff_config->disable_ip', $source);
        self::assertStringNotContainsString('(new FormDisplayContextResolver())->resolve(', $source);
        self::assertStringNotContainsString('(new FormPathResolver())->resolve(', $source);
    }

    private function initializer(): ProcessorRuntimeContextInitializer
    {
        return new ProcessorRuntimeContextInitializer(
            new RequestMetadataResolver(new Browser()),
            new FormDisplayContextResolver(),
            new FormPathResolver(),
            new SubmissionTimestampFactory()
        );
    }

    private function configuration(): FormConfiguration
    {
        $configuration = (new ReflectionClass(FormConfiguration::class))->newInstanceWithoutConstructor();
        $configuration->disable_ip = 0;
        $configuration->getprovider = 0;
        $configuration->gridshow = 1;
        $configuration->gridsize = 10;
        $configuration->images = '{mospath}/images/{ff_name}/{ff_currentpage}';
        $configuration->uploads = '{ff_images}/uploads/{ff_lastpage}/{cbsite}';

        return $configuration;
    }

    private function formrow(): object
    {
        return (object) [
            'runmode' => 0,
            'published' => 1,
            'prevmode' => 1,
            'pages' => 4,
            'name' => 'contact',
            'title' => 'Contact form',
        ];
    }
}
}
