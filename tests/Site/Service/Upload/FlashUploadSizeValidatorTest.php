<?php

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Site\Service\Upload;

use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Site\Service\Upload\FlashUploadSizeValidator;

final class FlashUploadSizeValidatorTest extends TestCase
{
    /** @return array<string, mixed> */
    private function form(int $bytes): array
    {
        return ['properties' => [], 'children' => [[
            'properties' => ['type' => 'element', 'bfType' => 'bfFile', 'bfName' => 'doc', 'label' => 'Document', 'flashUploaderBytes' => $bytes],
        ]]];
    }

    public function testConfiguredLimitIsUsed(): void
    {
        self::assertSame(['label' => 'Document', 'maxBytes' => 5000], (new FlashUploadSizeValidator())->limitFor($this->form(5000), 'doc'));
    }

    public function testUnlimitedFieldFallsBackToTheGlobalCeiling(): void
    {
        $validator = new FlashUploadSizeValidator();

        self::assertSame(FlashUploadSizeValidator::GLOBAL_MAX_BYTES, $validator->limitFor($this->form(0), 'doc')['maxBytes']);
        self::assertSame(FlashUploadSizeValidator::GLOBAL_MAX_BYTES, $validator->limitFor($this->form(5000), 'other')['maxBytes']);
        self::assertSame(FlashUploadSizeValidator::GLOBAL_MAX_BYTES, $validator->limitFor([], 'doc')['maxBytes']);
    }
}
