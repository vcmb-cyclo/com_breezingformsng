<?php

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator;

use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Administrator\Model\ImportModel;

final class StandardLibraryUpgradeTest extends TestCase
{
    #[RunInSeparateProcess]
    public function testAutomaticUpgradePreservesCustomCodeAndMetadata(): void
    {
        class_alias(StandardLibraryDatabaseInterface::class, 'Joomla\\Database\\DatabaseInterface');
        class_alias(StandardLibraryModelStub::class, 'Joomla\\CMS\\MVC\\Model\\BaseDatabaseModel');
        class_alias(StandardLibraryTextStub::class, 'Joomla\\CMS\\Language\\Text');
        class_alias(StandardLibraryDateStub::class, 'Joomla\\CMS\\Date\\Date');
        class_alias(StandardLibraryItemStub::class, 'Vcmb\\Component\\BreezingformsNG\\Administrator\\Model\\PackageItemModel');

        $model = new ImportModel();
        $model->reinstallOnlyIfChanged = true;
        $db = new StandardLibraryDatabaseStub();
        (new \ReflectionProperty($model, 'db'))->setValue($model, $db);
        $itemModel = new StandardLibraryItemStub();
        $import = new \ReflectionMethod($model, 'importLibraryItem');
        $node = new \SimpleXMLElement('<piece><package>FF</package><name>ff_query</name><title>Incoming</title><code>new code</code></piece>');
        $touched = $created = $updated = [];
        $run = static function () use ($model, $import, $node, $itemModel, &$touched, &$created, &$updated): void {
            $import->invokeArgs($model, [$node, '#__facileforms_pieces', $itemModel, &$touched, &$created, &$updated]);
        };

        // Source from the last distributed XML, decoded exactly as the importer does.
        $old = base64_decode('ZnVuY3Rpb24gZmZfcXVlcnkoJHNxbCwgJGluc2VydD1mYWxzZSwgJGVycm9yPUZGX0RJRSkNCnsNCiAgICBnbG9iYWwgJGZmX3Byb2Nlc3NvcjsNCiAgICAkZGF0YWJhc2UgPSBCRkZhY3Rvcnk6OmdldERCTygpOw0KICAgIGlmICgkZmZfcHJvY2Vzc29yLT5keWluZyAmJiAkZXJyb3IhPUZGX0lHTk9SRURJRSkgcmV0dXJuIC0xOw0KICAgICRkYXRhYmFzZS0+c2V0UXVlcnkoJHNxbCk7DQoNCiAgICB0cnkgew0KICAgICAgICAkZGF0YWJhc2UtPnF1ZXJ5KCk7DQogICAgICAgICRlcnJvciA9IG51bGw7DQogICAgICAgIGlmICgkaW5zZXJ0KQ0KICAgICAgICAgICAgIHJldHVybiAkZGF0YWJhc2UtPmluc2VydGlkKCk7DQogICAgfSBjYXRjaCAoXFJ1bnRpbWVFeGNlcHRpb24gJGUpIHsNCiAgICAgICAgJGRpZW5vdyA9ICRlcnJvcj09RkZfRElFOw0KICAgICAgICAkZXJyb3IgPSAkZS0+Z2V0TWVzc2FnZSgpOw0KICAgICAgICBpZiAoJGRpZW5vdykgZmZfZGllKCRlcnJvcik7DQogICAgfQ0KICANCiAgICByZXR1cm4gMDsNCn0gLy8gZmZfcXVlcnk=', true);
        self::assertIsString($old);
        $db->existing = ['id' => 7, 'name' => 'ff_query', 'package' => 'FF', 'title' => 'My title', 'published' => 0, 'code' => $old];
        $run();
        self::assertSame('new code', $db->existing['code']);
        self::assertSame('My title', $db->existing['title']);
        self::assertSame(0, $db->existing['published']);
        self::assertSame([7], $itemModel->origins);
        self::assertSame([7], $itemModel->versions);
        self::assertSame(['ff_query'], $updated);

        $run();
        self::assertCount(1, $db->updates, 'A repeated upgrade is a no-op.');
        self::assertSame([], $model->warnings);

        $db->existing['code'] = $old . "\n// customised";
        $customised = $db->existing;
        $run();
        self::assertSame($customised, $db->existing);
        self::assertSame(['ff_query'], $model->warnings);
        self::assertCount(1, $db->updates);

        // An explicit import keeps its existing overwrite behaviour.
        $model->reinstallOnlyIfChanged = false;
        $run();
        self::assertSame('new code', $db->existing['code']);
        self::assertSame('Incoming', $db->existing['title']);

        // A missing item is created using the ordinary installation path.
        $db->existing = null;
        $model->reinstallOnlyIfChanged = true;
        $run();
        self::assertSame('new code', $db->existing['code']);
        self::assertSame(['ff_query'], $created);
    }
}

// Minimal Joomla stand-ins for this isolated importer test.
// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses
interface StandardLibraryDatabaseInterface
{
}

class StandardLibraryModelStub
{
    public function getCurrentUser(): object
    {
        return (object) ['username' => 'test'];
    }
}

final class StandardLibraryTextStub
{
    public static function _(string $key): string
    {
        return $key;
    }
}

final class StandardLibraryDateStub
{
    public function toSql(): string
    {
        return '2026-01-01 00:00:00';
    }
}

final class StandardLibraryItemStub
{
    public array $origins = [];
    public array $versions = [];
    public function keepOrigin(int $id): void
    {
        $this->origins[] = $id;
    }
    public function recordVersion(int $id): void
    {
        $this->versions[] = $id;
    }
}

final class StandardLibraryDatabaseStub implements StandardLibraryDatabaseInterface
{
    public ?array $existing = null;
    public array $updates = [];
    public function createQuery(): StandardLibraryQueryStub
    {
        return new StandardLibraryQueryStub();
    }
    public function quoteName(string $name): string
    {
        return $name;
    }
    public function setQuery(StandardLibraryQueryStub $query, int $offset = 0, int $limit = 0): self
    {
        return $this;
    }
    public function loadAssoc(): ?array
    {
        return $this->existing;
    }
    public function updateObject(string $table, object $row, string $key): void
    {
        $this->updates[] = (array) $row;
        $this->existing = array_replace($this->existing, (array) $row);
    }
    public function insertObject(string $table, object $row, string $key): void
    {
        $row->id = 8;
        $this->existing = (array) $row;
    }
}

final class StandardLibraryQueryStub
{
    public function select(string $value): self
    {
        return $this;
    }
    public function from(string $value): self
    {
        return $this;
    }
    public function where(string $value): self
    {
        return $this;
    }
    public function order(string $value): self
    {
        return $this;
    }
    public function bind(string $key, mixed $value): self
    {
        return $this;
    }
}
// phpcs:enable PSR1.Classes.ClassDeclaration.MultipleClasses
