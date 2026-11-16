<?php

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Site\Service\Callback;

use PHPUnit\Framework\TestCase;

final class PayPalInvalidIpnRegressionTest extends TestCase
{
    public function testInvalidIpnDoesNotBlockAValidPaymentOrCauseDuplicateWrites(): void
    {
        $process = proc_open(
            [PHP_BINARY, __DIR__ . '/fixtures/paypal-ipn.php'],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes
        );
        self::assertIsResource($process);
        $output = stream_get_contents($pipes[1]);
        $error = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        self::assertSame(0, proc_close($process), $error);
        self::assertSame(
            [[0, ''], 1, 'PayPal: synthetic (VALID)'],
            json_decode($output, true, 512, JSON_THROW_ON_ERROR)
        );
    }
}
