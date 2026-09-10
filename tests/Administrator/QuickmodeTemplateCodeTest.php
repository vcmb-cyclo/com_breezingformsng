<?php

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator;

use PHPUnit\Framework\TestCase;

final class QuickmodeTemplateCodeTest extends TestCase
{
    public function testTemplateCodeIsCastBeforeBase64Decoding(): void
    {
        $model = file_get_contents(__DIR__ . '/../../administrator/components/com_breezingformsng/src/Model/QuickmodeModel.php');

        self::assertIsString($model);
        self::assertStringContainsString("base64_decode((string) (\$list[0]->template_code ?? ''))", $model);
    }
}
