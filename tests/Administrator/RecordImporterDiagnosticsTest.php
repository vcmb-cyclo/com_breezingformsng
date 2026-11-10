<?php

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator;

use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Administrator\Service\RecordImporter;

final class RecordImporterDiagnosticsTest extends TestCase
{
    #[RunInSeparateProcess]
    public function testDiagnosticsAndIgnoredColumns(): void
    {
        class_alias(ImporterDatabaseInterface::class, 'Joomla\\Database\\DatabaseInterface');
        class_alias(ImporterUser::class, 'Joomla\\CMS\\User\\User');
        class_alias(ImporterRecordModel::class, 'Vcmb\\Component\\BreezingformsNG\\Administrator\\Model\\RecordModel');

        $database = new ImporterDatabase();
        $model = new ImporterRecordModel();
        $importer = new RecordImporter($database, $model, new ImporterUser());
        $path = tempnam(sys_get_temp_dir(), 'bfng-csv-');
        self::assertIsString($path);

        try {
            self::assertSame(0, $importer->import(0, $path));
            self::assertSame('COM_BREEZINGFORMSNG_RECORDS_IMPORT_FORM_MISSING', $importer->getError());
            self::assertSame(0, $importer->import(1, $path . '-missing'));
            self::assertSame('COM_BREEZINGFORMSNG_RECORDS_IMPORT_FILE_UNREADABLE', $importer->getError());
            self::assertSame(0, $importer->import(1, $path));
            self::assertSame('COM_BREEZINGFORMSNG_RECORDS_IMPORT_EMPTY', $importer->getError());

            $database->formExists = false;
            self::assertSame(0, $importer->import(1, $path));
            self::assertSame('COM_BREEZINGFORMSNG_RECORDS_IMPORT_FORM_MISSING', $importer->getError());
            $database->formExists = true;

            file_put_contents($path, "single\n");
            self::assertSame(0, $importer->import(1, $path));
            self::assertSame('COM_BREEZINGFORMSNG_RECORDS_IMPORT_HEADER_INVALID', $importer->getError());
            self::assertSame(0, $importer->import(1, $path, 'invalid-encoding'));
            self::assertSame('COM_BREEZINGFORMSNG_RECORDS_IMPORT_ENCODING_FAILED', $importer->getError());

            file_put_contents($path, "submitted;known\n");
            self::assertSame(0, $importer->import(1, $path));
            self::assertSame('COM_BREEZINGFORMSNG_RECORDS_IMPORT_EMPTY', $importer->getError());

            file_put_contents($path, "submitted;known;<b>unknown</b>\n2026-01-01 12:00:00;value;secret\n");
            self::assertSame(1, $importer->import(1, $path));
            self::assertNull($importer->getError());
            self::assertSame(['<b>unknown</b>'], $importer->getIgnoredColumns());
            self::assertSame(['value'], $model->saved);
            self::assertTrue($database->committed);
        } finally {
            unlink($path);
        }
    }
}

// The Joomla doubles are used only inside the isolated test process.
// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses
interface ImporterDatabaseInterface
{
}

class ImporterDatabase implements ImporterDatabaseInterface
{
    public bool $committed = false;
    public bool $formExists = true;
    public function createQuery(): self
    {
        return $this;
    }
    public function quoteName(string|array $name): string
    {
        return 'column';
    }
    public function select(string $name): self
    {
        return $this;
    }
    public function from(string $name): self
    {
        return $this;
    }
    public function where(string $condition): self
    {
        return $this;
    }
    public function setQuery(self $query): self
    {
        return $this;
    }
    public function loadObject(): ?object
    {
        return $this->formExists ? (object) ['name' => 'test', 'title' => 'Test'] : null;
    }
    public function transactionStart(): void
    {
    }
    public function transactionCommit(): void
    {
        $this->committed = true;
    }
    public function transactionRollback(): void
    {
    }
    public function insertObject(string $table, object $record, string $key): void
    {
        $record->id = 1;
    }
}

class ImporterUser
{
    public int $id = 1;
    public string $username = 'test';
    public string $name = 'Test';
}

class ImporterRecordModel
{
    /** @var list<string> */
    public array $saved = [];
    public function getExportConfig(): object
    {
        return (object) ['csvdelimiter' => ';', 'csvquote' => '"', 'cellnewline' => 0];
    }
    public function getEditableElements(int $id): array
    {
        return [['name' => 'known', 'type' => 'Text']];
    }
    public function saveElementValue(int $id, array $element, string $value): void
    {
        $this->saved[] = $value;
    }
}
// phpcs:enable PSR1.Classes.ClassDeclaration.MultipleClasses
