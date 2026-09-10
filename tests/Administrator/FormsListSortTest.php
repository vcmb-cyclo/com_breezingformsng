<?php

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator;

use PHPUnit\Framework\TestCase;

final class FormsListSortTest extends TestCase
{
    public function testFormsListRegistersItsSortScriptBeforeTheTemplateIsRendered(): void
    {
        $source = file_get_contents(
            __DIR__ . '/../../administrator/components/com_breezingformsng/src/View/Forms/HtmlView.php'
        );

        self::assertIsString($source);
        self::assertStringContainsString("registerAndUseScript(\n                'com_breezingformsng.admin-sort'", $source);
        self::assertStringContainsString("'media/com_breezingformsng/js/admin/admin-sort.js'", $source);
    }
}
