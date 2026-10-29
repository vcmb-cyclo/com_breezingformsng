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

namespace Vcmb\Component\BreezingformsNG\Tests\Site\Service;

use PHPUnit\Framework\TestCase;

final class EngineDispatcherContainerTest extends TestCase
{
    public function testComponentProviderRegistersEngineDispatcherWithJoomlaServices(): void
    {
        $source = file_get_contents(
            __DIR__ . '/../../../administrator/components/com_breezingformsng/services/provider.php'
        );

        self::assertIsString($source);
        self::assertStringContainsString('EngineDispatcher::class', $source);
        self::assertStringContainsString('$container->set(', $source);
        self::assertStringContainsString('DatabaseInterface::class', $source);
        self::assertStringContainsString('MailerFactoryInterface::class', $source);
        self::assertStringContainsString('CacheControllerFactoryInterface::class', $source);
        self::assertStringContainsString('FormRenderer::class', $source);
        self::assertStringContainsString('CaptchaCallback::class', $source);
        self::assertStringContainsString('PayPalCallback::class', $source);
        self::assertStringContainsString('StripeCallback::class', $source);
        self::assertStringNotContainsString('SofortCallback', $source);
        self::assertStringContainsString('FlashUploadCallback::class', $source);
        self::assertStringContainsString('OptCallback::class', $source);
        self::assertStringContainsString('RequestParameterParser::class', $source);
        self::assertStringContainsString('PaymentDownloadPolicy::class', $source);
        self::assertStringContainsString('RedirectHelper::class', $source);
        self::assertStringContainsString('FlashUploadSizeValidator::class', $source);
        self::assertStringContainsString('UploadFileCleaner::class', $source);
        self::assertStringContainsString(
            '$container->share(' . "\n            PaymentDownloadPolicy::class",
            $source
        );
        self::assertStringContainsString(
            '$container->share(' . "\n            PaymentDownloadService::class",
            $source
        );
        self::assertStringContainsString('PaymentFormLoader::class', $source);
        self::assertStringContainsString(
            '$container->share(' . "\n            RedirectHelper::class",
            $source
        );

        $dispatcherProvider = substr($source, strpos($source, 'EngineDispatcher::class'));
        self::assertIsString($dispatcherProvider);
        self::assertStringContainsString(
            'static function () use ($container): FormRenderer',
            $dispatcherProvider
        );
        self::assertStringContainsString(
            'static function () use ($container): PayPalCallback',
            $dispatcherProvider
        );
        self::assertStringNotContainsString(
            "                    \$container->get(FormRenderer::class),",
            $dispatcherProvider
        );
        self::assertStringNotContainsString(
            "                    \$container->get(PayPalCallback::class),",
            $dispatcherProvider
        );
        self::assertStringContainsString('(new HttpFactory())->getHttp()', $source);

        $dispatcher = file_get_contents(
            __DIR__ . '/../../../components/com_breezingformsng/src/Service/EngineDispatcher.php'
        );

        self::assertIsString($dispatcher);
        self::assertStringNotContainsString('(new FormRenderer(', $dispatcher);
        self::assertStringNotContainsString('(new CaptchaCallback(', $dispatcher);
        self::assertStringNotContainsString('(new PayPalCallback(', $dispatcher);
        self::assertStringNotContainsString('(new StripeCallback(', $dispatcher);
        self::assertStringNotContainsString('Sofort', $dispatcher);
        self::assertStringNotContainsString('(new FlashUploadCallback(', $dispatcher);
        self::assertStringNotContainsString('(new OptCallback(', $dispatcher);
    }

    public function testFrontendBootstrapResolvesDispatcherFromTheContainer(): void
    {
        $source = file_get_contents(
            __DIR__ . '/../../../components/com_breezingformsng/breezingformsng.php'
        );

        self::assertIsString($source);
        self::assertStringContainsString('->getEngineDispatcher()', $source);
        self::assertStringContainsString("\$mainframe->bootComponent('com_breezingformsng')", $source);
        self::assertStringNotContainsString('new EngineDispatcher(', $source);

        $extension = file_get_contents(
            __DIR__ . '/../../../administrator/components/com_breezingformsng/src/Extension/BreezingFormsNGComponent.php'
        );

        self::assertIsString($extension);
        self::assertStringContainsString('BootableExtensionInterface', $extension);
        self::assertStringContainsString('$container->get(EngineDispatcher::class)', $extension);
    }
}
