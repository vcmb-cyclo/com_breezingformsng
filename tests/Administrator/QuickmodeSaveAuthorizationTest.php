<?php

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class QuickmodeSaveAuthorizationTest extends TestCase
{
    #[DataProvider('permissions')]
    public function testAuthorizationBeforeProcessingChunks(
        int $form,
        string $allowed,
        string $status,
        string $action
    ): void {
        $process = proc_open(
            [PHP_BINARY, __DIR__ . '/fixtures/quickmode-save-authorization.php', (string) $form, $allowed],
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
            [$status, $action . ':com_breezingformsng'],
            json_decode($output, true, 512, JSON_THROW_ON_ERROR)
        );
    }

    public static function permissions(): iterable
    {
        yield 'editing denied' => [12, 'core.create', '403', 'core.edit'];
        yield 'editing allowed reaches validation' => [12, 'core.edit', '400', 'core.edit'];
        yield 'creation denied' => [0, 'core.edit', '403', 'core.create'];
        yield 'creation allowed reaches validation' => [0, 'core.create', '400', 'core.create'];
    }

    public function testMissingExistingFormCannotBeCreatedByEditing(): void
    {
        $process = proc_open(
            [PHP_BINARY, __DIR__ . '/fixtures/quickmode-missing-form.php'],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes
        );
        self::assertIsResource($process);
        $output = stream_get_contents($pipes[1]);
        $error = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        self::assertSame(0, proc_close($process), $error);
        self::assertSame('The form to update does not exist.', $output);
    }
}
