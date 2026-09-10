<?php

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator;

use PHPUnit\Framework\TestCase;

final class EditorTextareaResizeTest extends TestCase
{
    public function testScriptAndPieceEditorsResizeTextareasWithoutTheLegacyGlobalFunction(): void
    {
        foreach (['Scripts', 'Pieces'] as $view) {
            $source = file_get_contents(
                __DIR__ . '/../../administrator/components/com_breezingformsng/src/View/' . $view . '/Renderer.php'
            );

            self::assertIsString($source);
            self::assertStringNotContainsString('textAreaResize(', $source);
            self::assertStringContainsString("document.getElementById('unit_tests').rows", $source);
        }
    }
}
