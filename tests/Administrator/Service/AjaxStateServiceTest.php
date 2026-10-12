<?php

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator\Service;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Administrator\Service\AjaxStateService;

final class AjaxStateServiceTest extends TestCase
{
    public static function stateProvider(): array
    {
        return [[-1, 0], [0, 0], [1, 1], [2, 1]];
    }

    #[DataProvider('stateProvider')]
    public function testNormalizesState(int $input, int $expected): void
    {
        self::assertSame($expected, AjaxStateService::normalizeState($input));
    }

    public function testAcceptsRecordColumnsWithOrWithoutDomPrefix(): void
    {
        self::assertSame('viewed', AjaxStateService::normalizeRecordColumn('viewed'));
        self::assertSame('exported', AjaxStateService::normalizeRecordColumn('bfrecord_exported'));
    }

    public function testRejectsUnknownRecordColumn(): void
    {
        self::assertNull(AjaxStateService::normalizeRecordColumn('published'));
        self::assertNull(AjaxStateService::normalizeRecordColumn('bfrecord_state'));
    }

    public function testBuildsStableSuccessPayload(): void
    {
        self::assertSame(['Result' => 'OK', 'State' => 1], AjaxStateService::success(7));
    }

    public function testBuildsStableErrorPayload(): void
    {
        self::assertSame(['Result' => 'ERROR', 'Message' => 'Invalid state'], AjaxStateService::error('Invalid state'));
    }
}
