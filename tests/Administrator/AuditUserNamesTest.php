<?php

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator;

use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Administrator\Service\AuditUserNames;

final class AuditUserNamesTest extends TestCase
{
    #[RunInSeparateProcess]
    public function testResolvesUsernamesAndIdsInBatchesAndKeepsUnknownValues(): void
    {
        class_alias(AuditDatabaseInterface::class, 'Joomla\\Database\\DatabaseInterface');
        class_alias(AuditParameterType::class, 'Joomla\\Database\\ParameterType');
        $db = new AuditDatabase();

        $names = AuditUserNames::resolve($db, ['admin', '42', '17', '999', 'ancien', '']);

        self::assertSame('Alice <Admin>', $names['admin']);
        // Numeric username takes precedence over a different account with ID 42.
        self::assertSame('Numeric login', $names['42']);
        self::assertSame('17', $names['17']);
        self::assertSame('999', $names['999']);
        self::assertSame('ancien', $names['ancien']);
        self::assertSame(1, $db->queries);
    }

    #[RunInSeparateProcess]
    public function testResolvesNumericIdsOnlyWhenCallerDeclaresIdStorage(): void
    {
        class_alias(AuditDatabaseInterface::class, 'Joomla\\Database\\DatabaseInterface');
        class_alias(AuditParameterType::class, 'Joomla\\Database\\ParameterType');
        $db = new AuditDatabase();

        $names = AuditUserNames::resolve($db, ['17', '999'], true);

        self::assertSame('By ID', $names['17']);
        self::assertSame('999', $names['999']);
        self::assertSame(2, $db->queries);
    }

    #[RunInSeparateProcess]
    public function testEmptyInputDoesNotQueryTheDatabase(): void
    {
        class_alias(AuditDatabaseInterface::class, 'Joomla\\Database\\DatabaseInterface');
        class_alias(AuditParameterType::class, 'Joomla\\Database\\ParameterType');
        $db = new AuditDatabase();

        self::assertSame([], AuditUserNames::resolve($db, ['', '']));
        self::assertSame(0, $db->queries);
    }
}

// Minimal database double for the batch lookups.
// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses
interface AuditDatabaseInterface
{
}

final class AuditParameterType
{
    public const STRING = 'string';
    public const INTEGER = 'integer';
}

final class AuditDatabase implements AuditDatabaseInterface
{
    public int $queries = 0;
    private AuditQuery $query;

    public function createQuery(): AuditQuery
    {
        return new AuditQuery();
    }

    public function quoteName(string|array $name): string|array
    {
        return $name;
    }

    public function setQuery(AuditQuery $query): self
    {
        $this->query = $query;
        ++$this->queries;
        return $this;
    }

    public function loadAssocList(string $key): array
    {
        $users = [
            ['id' => 1, 'username' => 'admin', 'name' => 'Alice <Admin>'],
            ['id' => 2, 'username' => '42', 'name' => 'Numeric login'],
            ['id' => 42, 'username' => 'different', 'name' => 'Wrong account'],
            ['id' => 17, 'username' => 'other', 'name' => 'By ID'],
        ];
        $result = [];
        foreach ($users as $user) {
            if (in_array($user[$key], $this->query->values, true)) {
                $result[$user[$key]] = $user;
            }
        }
        return $result;
    }
}

final class AuditQuery
{
    public array $values = [];

    public function select(array $columns): self
    {
        return $this;
    }

    public function from(string $table): self
    {
        return $this;
    }

    public function whereIn(string $column, array $values, string $type): self
    {
        $this->values = $values;
        return $this;
    }
}
// phpcs:enable PSR1.Classes.ClassDeclaration.MultipleClasses
