<?php

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Site\Service\Export;

use PHPUnit\Framework\TestCase;

final class ContentBuilderCleanupRegressionTest extends TestCase
{
    private const SOURCES = '/components/com_breezingformsng/src/Service/';

    public function testEditableOverrideRemovesContentBuilderDataBeforeSourceRecord(): void
    {
        $source = (string) file_get_contents(dirname(__DIR__, 4) . self::SOURCES . 'Export/ExportEngine.php');

        $cleanup = strpos($source, "))->remove((int) \$editableFormValue, array_column(\$records, 'id'));");
        $sourceDelete = strpos($source, "->delete(\$this->processor->database->quoteName('#__facileforms_records'))");

        self::assertNotFalse($cleanup);
        self::assertNotFalse($sourceDelete);
        self::assertLessThan($sourceDelete, $cleanup);
    }

    public function testCleanupCoversEveryContentBuilderTable(): void
    {
        $source = (string) file_get_contents(
            dirname(__DIR__, 4) . self::SOURCES . 'Integration/ContentBuilderRecordCleanup.php'
        );

        foreach (['records', 'list_records', 'articles'] as $table) {
            self::assertStringContainsString("->delete(\$db->quoteName('#__contentbuilderng_" . $table . "'))", $source);
        }

        self::assertStringContainsString("->where(\$db->quoteName('reference_id') . ' = :formId')", $source);
        self::assertStringContainsString("=== 1", $source);
        self::assertStringContainsString("->createTable('Article', 'Administrator')", $source);
    }
}
