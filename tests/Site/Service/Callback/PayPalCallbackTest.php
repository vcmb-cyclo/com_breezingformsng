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

namespace Vcmb\Component\BreezingformsNG\Tests\Site\Service\Callback;

use Joomla\CMS\Application\CMSApplication;
use Joomla\Http\Http;
use Joomla\Http\Response;
use Joomla\Database\DatabaseInterface;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use Vcmb\Component\BreezingformsNG\Site\Service\Callback\PayPalCallback;
use Vcmb\Component\BreezingformsNG\Site\Service\Callback\PaymentDownloadPolicy;
use Vcmb\Component\BreezingformsNG\Site\Service\Callback\PaymentDownloadService;
use Vcmb\Component\BreezingformsNG\Site\Service\Callback\PaymentFormLoader;
use Vcmb\Component\BreezingformsNG\Site\Service\Callback\PaymentRecordService;
use Vcmb\Component\BreezingformsNG\Site\Service\Support\RedirectHelper;

require_once __DIR__ . '/../Rendering/QuickMode/joomla-cmsapplication-stub.php';

if (!class_exists(Http::class)) {
    eval('namespace Joomla\\Http; class Http { public function post($url, $data, array $headers = [], $timeout = null) {} }');
}

if (!class_exists(Response::class)) {
    eval(
        'namespace Joomla\\Http;'
        . 'class Response {'
        . '  private string $bodyContents;'
        . '  private int $statusCode;'
        . '  public function __construct(string $body = "", int $status = 200) {'
        . '    $this->bodyContents = $body;'
        . '    $this->statusCode = $status;'
        . '  }'
        . '  public function getBody() { return $this; }'
        . '  public function getStatusCode(): int { return $this->statusCode; }'
        . '  public function __toString(): string { return $this->bodyContents; }'
        . '}'
    );
}

if (!class_exists(CMSApplication::class)) {
    eval('namespace Joomla\\CMS\\Application; class CMSApplication {}');
}

if (!interface_exists(DatabaseInterface::class)) {
    eval('namespace Joomla\\Database; interface DatabaseInterface {}');
}

final class PayPalCallbackTest extends TestCase
{
    public function testRequestVerificationPostsToPayPalAndTrimsResponse(): void
    {
        $http = $this->createMock(Http::class);
        $http->expects(self::once())
            ->method('post')
            ->with(
                'https://www.paypal.com/cgi-bin/webscr',
                'cmd=_notify-validate&txn_id=abc',
                ['Content-Type' => 'application/x-www-form-urlencoded']
            )
            ->willReturn(new Response(" VERIFIED \n"));

        self::assertSame(
            'VERIFIED',
            $this->invokeRequestVerification(
                $http,
                'https://www.paypal.com',
                'cmd=_notify-validate&txn_id=abc'
            )
        );
    }

    public function testRequestVerificationReturnsEmptyStringWhenHttpFails(): void
    {
        $http = $this->createMock(Http::class);
        $http->expects(self::once())
            ->method('post')
            ->willThrowException(new \RuntimeException('network failure'));

        self::assertSame(
            '',
            $this->invokeRequestVerification($http, 'https://www.paypal.com', 'payload')
        );
    }

    public function testHttpClientIsRequiredAsAnInjectedDependency(): void
    {
        $parameter = (new ReflectionMethod(PayPalCallback::class, '__construct'))->getParameters()[6];

        self::assertFalse($parameter->allowsNull());
        self::assertSame(Http::class, (string) $parameter->getType());
    }

    private function invokeRequestVerification(Http $http, string $paypalUrl, string $body): string
    {
        $application = new CMSApplication();
        $database = new class implements DatabaseInterface {
        };
        $callback = new PayPalCallback(
            $application,
            $database,
            new PaymentFormLoader($database),
            new PaymentRecordService($database),
            new RedirectHelper($application),
            new PaymentDownloadService(
                $application,
                $database,
                new PaymentFormLoader($database),
                new RedirectHelper($application),
                new PaymentDownloadPolicy()
            ),
            $http
        );

        return (string) (new ReflectionMethod($callback, 'requestVerification'))->invoke(
            $callback,
            $paypalUrl,
            $body
        );
    }
}
