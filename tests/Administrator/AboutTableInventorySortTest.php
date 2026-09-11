<?php

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator;

use PHPUnit\Framework\TestCase;

final class AboutTableInventorySortTest extends TestCase
{
    public function testInventoryHeadersUseTheClientSideSortControls(): void
    {
        $template = file_get_contents(__DIR__ . '/../../administrator/components/com_breezingformsng/tmpl/about/default.php');
        $script = file_get_contents(__DIR__ . '/../../media/com_breezingformsng/js/admin/about.js');

        self::assertIsString($template);
        self::assertIsString($script);
        self::assertStringContainsString('data-bf-sortable-table', $template);
        self::assertStringContainsString('data-bf-table-sort="number"', $template);
        self::assertStringContainsString('data-bf-sort-value', $template);
        self::assertStringContainsString('initialiseTableInventorySorting', $script);
        self::assertStringContainsString('aria-sort', $script);
    }
}
