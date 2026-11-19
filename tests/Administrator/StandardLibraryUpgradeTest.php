<?php

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator;

use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Administrator\Model\ImportModel;

final class StandardLibraryUpgradeTest extends TestCase
{
    #[RunInSeparateProcess]
    public function testAnItemOfTheStandardLibraryIsReplacedAfterItsCurrentVersionIsStored(): void
    {
        class_alias(StandardLibraryDatabaseInterface::class, 'Joomla\\Database\\DatabaseInterface');
        class_alias(StandardLibraryModelStub::class, 'Joomla\\CMS\\MVC\\Model\\BaseDatabaseModel');
        class_alias(StandardLibraryTextStub::class, 'Joomla\\CMS\\Language\\Text');
        class_alias(StandardLibraryDateStub::class, 'Joomla\\CMS\\Date\\Date');
        class_alias(StandardLibraryItemStub::class, 'Vcmb\\Component\\BreezingformsNG\\Administrator\\Model\\PackageItemModel');

        $model = new ImportModel();
        $model->reinstallOnlyIfChanged = true;
        $model->incomingVersion = '6.2.0';
        $db = new StandardLibraryDatabaseStub();
        (new \ReflectionProperty($model, 'db'))->setValue($model, $db);
        $itemModel = new StandardLibraryItemStub();
        $import = new \ReflectionMethod($model, 'importLibraryItem');
        $node = new \SimpleXMLElement('<piece><package>FF</package><name>ff_query</name><title>Incoming</title><code>new code</code></piece>');
        $touched = $created = $updated = [];
        $run = static function () use ($model, $import, $node, $itemModel, &$touched, &$created, &$updated): void {
            $import->invokeArgs($model, [$node, '#__facileforms_pieces', $itemModel, &$touched, &$created, &$updated]);
        };
        // Set as import() does, from the version being installed.
        $notes = new \ReflectionProperty($model, 'noteBefore');
        $notes->setValue($model, 'before 6.2.0');
        (new \ReflectionProperty($model, 'noteStandard'))->setValue($model, 'standard 6.2.0');

        // A piece changed on the site, or distributed earlier: its code is stored, then replaced, then the standard version is stored.
        $db->existing = ['id' => 7, 'name' => 'ff_query', 'package' => 'FF', 'title' => 'My title', 'published' => 0, 'code' => 'custom code'];
        $run();
        self::assertSame('new code', $db->existing['code']);
        self::assertSame('Incoming', $db->existing['title']);
        self::assertSame(['before:7:before 6.2.0', 'write', 'record:7:standard 6.2.0'], StandardLibraryItemStub::$log);
        self::assertSame(['ff_query'], $updated);

        // The same standard code again: nothing is stored or written.
        StandardLibraryItemStub::$log = [];
        $run();
        self::assertSame([], StandardLibraryItemStub::$log);
        self::assertCount(1, $db->updates);

        // A missing item is created, without any extra version beyond its first.
        $db->existing = null;
        StandardLibraryItemStub::$log = [];
        $updated = [];
        $run();
        self::assertSame('new code', $db->existing['code']);
        self::assertSame(['ff_query'], $created);
        self::assertSame([], $updated);
        self::assertSame(['record:8:'], StandardLibraryItemStub::$log);

        // An explicit import keeps its overwrite behaviour, with the same history.
        $db->existing = ['id' => 7, 'name' => 'ff_query', 'package' => 'FF', 'title' => 'T', 'published' => 1, 'code' => 'new code'];
        $model->reinstallOnlyIfChanged = false;
        StandardLibraryItemStub::$log = [];
        $run();
        self::assertSame('before:7:before 6.2.0', StandardLibraryItemStub::$log[0]);
    }

    #[RunInSeparateProcess]
    public function testTheNotesNameTheIncomingVersion(): void
    {
        class_alias(StandardLibraryDatabaseInterface::class, 'Joomla\\Database\\DatabaseInterface');
        class_alias(StandardLibraryModelStub::class, 'Joomla\\CMS\\MVC\\Model\\BaseDatabaseModel');
        class_alias(StandardLibraryTextStub::class, 'Joomla\\CMS\\Language\\Text');
        class_alias(StandardLibraryItemStub::class, 'Vcmb\\Component\\BreezingformsNG\\Administrator\\Model\\PackageItemModel');

        $model = new ImportModel();
        $notes = static function () use ($model): array {
            try {
                $model->import('/nonexistent.xml', new StandardLibraryItemStub(), new StandardLibraryItemStub());
            } catch (\RuntimeException) {
            }

            return [
                (new \ReflectionProperty($model, 'noteBefore'))->getValue($model),
                (new \ReflectionProperty($model, 'noteStandard'))->getValue($model),
            ];
        };

        $model->incomingVersion = '6.2.0';
        self::assertSame(['COM_BREEZINGFORMSNG_LIBRARY_VERSION_NOTE_BEFORE_STANDARD|6.2.0', 'COM_BREEZINGFORMSNG_LIBRARY_VERSION_NOTE_STANDARD|6.2.0'], $notes());

        // A key the language does not translate yet never ends up in the history: the note falls back to English.
        $model->incomingVersion = '';
        self::assertSame(['Before the update to the standard library', 'Standard library of the update'], $notes());
    }
}

// Minimal Joomla stand-ins for this isolated importer test.
// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses
interface StandardLibraryDatabaseInterface
{
}

class StandardLibraryModelStub
{
    public function getDatabase(): StandardLibraryDatabaseStub
    {
        return new StandardLibraryDatabaseStub();
    }

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
    public static function sprintf(string $key, string ...$arguments): string
    {
        return $key . '|' . implode('|', $arguments);
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
    /** @var list<string> What was asked of the model, in order, with the writes of the database. */
    public static array $log = [];
    public function keepVersionBeforeReplacement(int $id, string $note): void
    {
        self::$log[] = 'before:' . $id . ':' . $note;
    }
    public function recordVersion(int $id, string $note = ''): void
    {
        self::$log[] = 'record:' . $id . ':' . $note;
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
        StandardLibraryItemStub::$log[] = 'write';
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
