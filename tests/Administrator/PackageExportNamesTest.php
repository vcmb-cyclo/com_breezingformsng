<?php

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator;

use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Administrator\Model\PackageTransferModel;

final class PackageExportNamesTest extends TestCase
{
    #[RunInSeparateProcess]
    public function testExportRejectsNamesTheDatabaseGroupsTogether(): void
    {
        class_alias(PackageNamesTextStub::class, 'Joomla\\CMS\\Language\\Text');
        class_alias(PackageNamesModelStub::class, 'Joomla\\CMS\\MVC\\Model\\BaseDatabaseModel');
        class_alias(PackageNamesParameterTypeStub::class, 'Joomla\\Database\\ParameterType');

        $database = new PackageNamesDatabaseStub();
        PackageNamesModelStub::$database = $database;
        $model = new PackageTransferModel();
        $check = new \ReflectionMethod($model, 'assertImportableNames');
        $rows = [
            ['id' => 7, 'name' => 'Été'],
            ['id' => 9, 'name' => 'Ete'],
            ['id' => 11, 'name' => 'Hiver'],
        ];
        // Simulate the database's accent-insensitive collation, not PHP lowercasing.
        $database->rankedRows = [
            ['id' => 7, 'name' => 'Été', 'name_group' => 1],
            ['id' => 9, 'name' => 'Ete', 'name_group' => 1],
            ['id' => 11, 'name' => 'Hiver', 'name_group' => 2],
        ];

        try {
            $check->invoke($model, '#__facileforms_forms', $rows, true);
            self::fail('Names considered equal by the database must be rejected.');
        } catch (\RuntimeException $exception) {
            self::assertStringContainsString('#7, #9', $exception->getMessage());
            self::assertStringNotContainsString('#11', $exception->getMessage());
        }

        self::assertSame([7, 9, 11], $database->query->ids);
        // The PHP-trimmed names, bound, take the collation of the name column.
        $select = implode(' ', $database->query->select);
        self::assertStringContainsString('DENSE_RANK() OVER (ORDER BY CONCAT(LEFT(`name`, 0), CASE `id`', $select);
        self::assertSame(['Été', 'Ete', 'Hiver'], $database->query->bound[1]);

        $database->rankedRows[1]['name_group'] = 3;
        $check->invoke($model, '#__facileforms_forms', $rows, true);
    }
}

// Joomla is not loaded by the isolated unit-test bootstrap.
// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses
final class PackageNamesTextStub
{
    public static function sprintf(string $key, string $value): string
    {
        return $key . ': ' . $value;
    }
}

final class PackageNamesParameterTypeStub
{
    public const INTEGER = 'int';
    public const STRING = 'string';
}

class PackageNamesModelStub
{
    public static PackageNamesDatabaseStub $database;

    public function getDatabase(): PackageNamesDatabaseStub
    {
        return self::$database;
    }
}

final class PackageNamesDatabaseStub
{
    public PackageNamesQueryStub $query;
    public array $rankedRows = [];

    public function createQuery(): PackageNamesQueryStub
    {
        return $this->query = new PackageNamesQueryStub();
    }

    public function quoteName(string $name): string
    {
        return '`' . $name . '`';
    }

    public function setQuery(PackageNamesQueryStub $query): self
    {
        return $this;
    }

    public function loadAssocList(): array
    {
        return $this->rankedRows;
    }
}

final class PackageNamesQueryStub
{
    public array $select = [];
    public array $ids = [];

    public function select(array $columns): self
    {
        $this->select = $columns;
        return $this;
    }

    public function from(string $table): self
    {
        return $this;
    }

    public function whereIn(string $column, array $ids): self
    {
        $this->ids = $ids;
        return $this;
    }

    /** @var list<array<int, mixed>> */
    public array $bound = [];

    /** @return list<string> */
    public function bindArray(array $values, mixed $type = null): array
    {
        $set = count($this->bound);
        $this->bound[] = $values;

        return array_map(static fn (int $index): string => ':p' . $set . '_' . $index, array_keys($values));
    }
}
// phpcs:enable PSR1.Classes.ClassDeclaration.MultipleClasses
