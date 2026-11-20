<?php

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator;

use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Administrator\Model\PackageTransferModel;

final class PackageDissolveTest extends TestCase
{
    #[RunInSeparateProcess]
    public function testOnlySelectedAssignmentsInTheChosenPackageAreDissolved(): void
    {
        class_alias(DissolveModelStub::class, 'Joomla\\CMS\\MVC\\Model\\BaseDatabaseModel');
        class_alias(DissolveParameterTypeStub::class, 'Joomla\\Database\\ParameterType');
        $db = DissolveModelStub::$database = new DissolveDatabaseStub();
        $model = new PackageTransferModel();

        self::assertFalse($model->deletePackage('alpha', []));
        self::assertSame('alpha', $db->rows['forms'][1]);
        self::assertTrue($model->deletePackage('alpha', ['forms' => [1, 3], 'scripts' => [4]]));
        self::assertSame('', $db->rows['forms'][1]);
        self::assertSame('alpha', $db->rows['forms'][2]);
        self::assertSame('other', $db->rows['forms'][3]);
        self::assertSame('', $db->rows['scripts'][4]);
        self::assertTrue($model->attachToPackage('alpha', ['forms' => [3]]));
        self::assertSame('alpha', $db->rows['forms'][3]);
        self::assertContains('alpha', $db->packages);
        self::assertTrue($model->deletePackage('alpha', ['forms' => [2]]));
        self::assertContains('alpha', $db->packages);
        self::assertTrue($model->removePackage('alpha'));
        self::assertSame('', $db->rows['forms'][3]);
        self::assertNotContains('alpha', $db->packages);
        self::assertFalse($model->deletePackage('empty', []));
        self::assertContains('empty', $db->packages);
    }
}

// Database double models assignments and package metadata independently.
// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses
class DissolveModelStub
{
    public static DissolveDatabaseStub $database;
    public function getDatabase(): DissolveDatabaseStub
    {
        return self::$database;
    }
}

final class DissolveParameterTypeStub
{
    public const INTEGER = 'integer';
}

final class DissolveDatabaseStub
{
    public array $rows = [
        'forms' => [1 => 'alpha', 2 => 'alpha', 3 => 'other'],
        'scripts' => [4 => 'alpha'],
        'pieces' => [],
        'compmenus' => [],
    ];
    public array $packages = ['alpha', 'other', 'empty'];
    private DissolveQueryStub $query;
    private int $affected = 0;

    public function createQuery(): DissolveQueryStub
    {
        return new DissolveQueryStub();
    }
    public function quoteName(string|array $name): string|array
    {
        return $name;
    }
    public function quote(string $value): string
    {
        return "'" . $value . "'";
    }
    public function setQuery(DissolveQueryStub $query): self
    {
        $this->query = $query;
        return $this;
    }
    public function transactionStart(): void
    {
    }
    public function transactionCommit(): void
    {
    }
    public function transactionRollback(): void
    {
    }
    public function getAffectedRows(): int
    {
        return $this->affected;
    }
    public function loadColumn(): array
    {
        if ($this->query->table === 'packages') {
            return $this->packages;
        }
        return array_values(array_unique(array_filter($this->rows[$this->query->table])));
    }
    public function loadAssocList(): array
    {
        $items = [];
        foreach ($this->rows[$this->query->table] as $id => $package) {
            if ($package === $this->query->package) {
                $items[] = ['id' => $id];
            }
        }
        return $items;
    }
    public function execute(): void
    {
        $this->affected = 0;
        if ($this->query->table === 'packages') {
            $this->packages = array_values(array_diff($this->packages, [$this->query->package]));
            return;
        }
        $ids = $this->query->ids !== [] ? $this->query->ids : array_keys($this->rows[$this->query->table]);
        foreach ($ids as $id) {
            if (
                isset($this->rows[$this->query->table][$id])
                && (!$this->query->filterPackage || $this->rows[$this->query->table][$id] === $this->query->package)
            ) {
                $this->rows[$this->query->table][$id] = $this->query->clearPackage ? '' : $this->query->package;
                ++$this->affected;
            }
        }
    }
}

final class DissolveQueryStub
{
    public string $table = '';
    public string $package = '';
    public array $ids = [];
    public bool $clearPackage = false;
    public bool $filterPackage = false;
    public function select(mixed $columns): self
    {
        return $this;
    }
    public function from(string $table): self
    {
        $this->table = substr($table, strlen('#__facileforms_'));
        return $this;
    }
    public function update(string $table): self
    {
        return $this->from($table);
    }
    public function delete(string $table): self
    {
        return $this->from($table);
    }
    public function set(string $set): self
    {
        $this->clearPackage = str_contains($set, "''");
        return $this;
    }
    public function where(string $where): self
    {
        $this->filterPackage = str_contains($where, ':package');
        return $this;
    }
    public function whereIn(string $column, array $ids, mixed $type): self
    {
        $this->ids = $ids;
        return $this;
    }
    public function bind(string $name, string &$value): self
    {
        $this->package = $value;
        return $this;
    }
    public function order(mixed $order): self
    {
        return $this;
    }
}
// phpcs:enable PSR1.Classes.ClassDeclaration.MultipleClasses
