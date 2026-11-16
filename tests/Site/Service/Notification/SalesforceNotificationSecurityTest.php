<?php

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Site\Service\Notification;

use PHPUnit\Framework\TestCase;

final class SalesforceNotificationSecurityTest extends TestCase
{
    public function testSuccessDoesNotCreatePublicLog(): void
    {
        [$output, $log, $publicLog] = $this->runFixture('success');
        self::assertSame('', $output);
        self::assertFalse($log);
        self::assertFalse($publicLog);
    }

    public function testFailureDoesNotExposeRemoteResponse(): void
    {
        [$output, $log, $publicLog] = $this->runFixture('failure');
        self::assertSame('Generic error', $output);
        self::assertSame('Salesforce notification failed.', $log);
        self::assertFalse($publicLog);
    }

    private function runFixture(string $scenario): array
    {
        $directory = sys_get_temp_dir() . '/bf-salesforce-' . bin2hex(random_bytes(8));
        mkdir($directory);
        try {
            $command = [PHP_BINARY, __DIR__ . '/fixtures/salesforce-notification.php', $directory, $scenario];
            $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
            self::assertIsResource($process);
            $output = stream_get_contents($pipes[1]);
            $error = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            self::assertSame(0, proc_close($process), $error);

            return [
                $output,
                is_file($directory . '/test-log') ? file_get_contents($directory . '/test-log') : false,
                is_file($directory . '/sf.log'),
            ];
        } finally {
            if (is_file($directory . '/test-log')) {
                unlink($directory . '/test-log');
            }
            if (is_file($directory . '/sf.log')) {
                unlink($directory . '/sf.log');
            }
            rmdir($directory);
        }
    }
}
