<?php

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator;

use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Administrator\Model\PackageTransferModel;

final class PackageElementOrderTest extends TestCase
{
    #[RunInSeparateProcess]
    public function testReorderedElementsMatchTheirExportAndKeepTheirPositions(): void
    {
        class_alias(PackageOrderModelStub::class, 'Joomla\\CMS\\MVC\\Model\\BaseDatabaseModel');
        class_alias(PackageOrderParameterTypeStub::class, 'Joomla\\Database\\ParameterType');
        PackageOrderModelStub::$database = new PackageOrderDatabaseStub();
        $model = new PackageTransferModel();
        $elements = [
            ['source_id' => 20, 'name' => 'second', 'ordering' => 1],
            ['source_id' => 10, 'name' => 'first', 'ordering' => 2],
        ];

        $matches = new \ReflectionMethod($model, 'formMatches');
        self::assertTrue($matches->invoke($model, 5, ['name' => 'contact'], $elements, ['scripts' => [], 'pieces' => []], [], []));

        $load = new \ReflectionMethod($model, 'loadElements');
        self::assertSame([20, 10], array_column($load->invoke($model, [5]), 'id'));

        $elements[0]['name'] = 'changed';
        self::assertFalse($matches->invoke($model, 5, ['name' => 'contact'], $elements, ['scripts' => [], 'pieces' => []], [], []));
    }

    #[RunInSeparateProcess]
    public function testMissingActiveReferencesAreReportedWithoutChangingThePackage(): void
    {
        class_alias(PackageOrderModelStub::class, 'Joomla\\CMS\\MVC\\Model\\BaseDatabaseModel');
        $model = new PackageTransferModel();
        $check = new \ReflectionMethod($model, 'unresolvedReferences');
        $forms = [[
            'data' => ['name' => '<contact>', 'script1cond' => 1, 'script1id' => 42, 'piece1cond' => 1, 'piece1id' => 8],
            'elements' => [[
                'name' => 'email', 'script3cond' => '1', 'script3id' => '43',
                'script1cond' => 2, 'script1id' => 99, 'script2cond' => 0, 'script2id' => 98,
            ]],
        ]];

        self::assertSame(
            ['<contact>: script1id #42', '<contact> / email: script3id #43'],
            $check->invoke($model, $forms, [], [8 => 100])
        );
        self::assertSame([], $check->invoke($model, $forms, [42 => 1, 43 => 2], [8 => 100]));
        self::assertSame(42, $forms[0]['data']['script1id']);
    }
}

// Joomla is not loaded by the isolated unit-test bootstrap.
// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses
final class PackageOrderParameterTypeStub
{
    public const INTEGER = 'int';
}

class PackageOrderModelStub
{
    public static PackageOrderDatabaseStub $database;

    public function getDatabase(): PackageOrderDatabaseStub
    {
        return self::$database;
    }
}

final class PackageOrderDatabaseStub
{
    private PackageOrderQueryStub $query;

    public function createQuery(): PackageOrderQueryStub
    {
        return new PackageOrderQueryStub();
    }

    public function quoteName(string $name): string
    {
        return '`' . $name . '`';
    }

    public function setQuery(PackageOrderQueryStub $query): self
    {
        $this->query = $query;
        return $this;
    }

    public function loadAssocList(): array
    {
        $rows = [
            ['id' => 10, 'form' => 5, 'name' => 'first', 'ordering' => 2],
            ['id' => 20, 'form' => 5, 'name' => 'second', 'ordering' => 1],
        ];
        if (in_array('`ordering`', $this->query->order, true)) {
            usort($rows, static fn (array $a, array $b): int => [$a['ordering'], $a['id']] <=> [$b['ordering'], $b['id']]);
        }
        return $rows;
    }

    public function loadAssoc(): array
    {
        return ['id' => 5, 'name' => 'contact'];
    }

    public function getTableColumns(string $table): array
    {
        return array_fill_keys(['id', 'form', 'name', 'ordering'], 'int');
    }
}

final class PackageOrderQueryStub
{
    public array $order = [];

    public function select(string $columns): self
    {
        return $this;
    }
    public function from(string $table): self
    {
        return $this;
    }
    public function where(string $condition): self
    {
        return $this;
    }
    public function whereIn(string $column, array $values): self
    {
        return $this;
    }
    public function bind(string $key, mixed $value, mixed $type = null): self
    {
        return $this;
    }

    public function order(array|string $order): self
    {
        $this->order = (array) $order;
        return $this;
    }
}
// phpcs:enable PSR1.Classes.ClassDeclaration.MultipleClasses
