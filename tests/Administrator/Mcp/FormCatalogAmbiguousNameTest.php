<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator\Mcp;

use Joomla\Database\DatabaseInterface;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Administrator\Mcp\FormCatalog;
use Vcmb\Component\BreezingformsNG\Administrator\Mcp\Tool\GetFormTool;
use Vcmb\Component\BreezingformsNG\Administrator\Mcp\ToolException;

require_once __DIR__ . '/McpTestDoubles.php';

final class FormCatalogAmbiguousNameTest extends TestCase
{
    protected function setUp(): void
    {
        if (!class_exists(\Joomla\CMS\Language\Text::class)) {
            class_alias(FormCatalogAmbiguousText::class, \Joomla\CMS\Language\Text::class);
        }
        if (!class_exists(\Joomla\Database\ParameterType::class)) {
            class_alias(FormCatalogAmbiguousParameterType::class, \Joomla\Database\ParameterType::class);
        }
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testUnknownNameReturnsNoForm(): void
    {
        $db = new FormCatalogAmbiguousDatabase([]);

        self::assertNull((new FormCatalog($db))->getForm(null, 'contact', false));
        self::assertSame(2, $db->firstLimit);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testOneMatchingNameReturnsTheForm(): void
    {
        $db = new FormCatalogAmbiguousDatabase([self::form(1, 'package-a')]);
        $form = (new GetFormTool(new FormCatalog($db)))->call(
            ['name' => 'contact', 'include_code' => false],
            McpTestDoubles::user(['core.manage'])
        );

        self::assertSame(1, $form['id']);
        self::assertSame('package-a', $form['package']);
        self::assertSame(2, $db->firstLimit);
        self::assertSame('name = :name', $db->firstWhere);
        self::assertSame([':name' => 'contact'], $db->firstBound);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testDuplicateNamesAreRefusedBeforeReadingElements(): void
    {
        $db = new FormCatalogAmbiguousDatabase([self::form(1, 'package-a'), self::form(2, 'package-b')]);

        try {
            (new GetFormTool(new FormCatalog($db)))->call(
                ['name' => 'contact', 'include_code' => false],
                McpTestDoubles::user(['core.manage'])
            );
            self::fail('Two forms with the same name must be refused.');
        } catch (ToolException $exception) {
            self::assertSame('COM_BREEZINGFORMSNG_MCP_FORM_NAME_AMBIGUOUS', $exception->getMessage());
            self::assertSame(2, $db->firstLimit);
            self::assertSame(1, $db->queries);
        }
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testAnIdStillSelectsItsForm(): void
    {
        $db = new FormCatalogAmbiguousDatabase([self::form(2, 'package-b')]);
        $form = (new GetFormTool(new FormCatalog($db)))->call(
            ['id' => 2, 'include_code' => false],
            McpTestDoubles::user(['core.manage'])
        );

        self::assertSame(2, $form['id']);
        self::assertSame('package-b', $form['package']);
        self::assertSame(1, $db->firstLimit);
        self::assertSame('id = :id', $db->firstWhere);
        self::assertSame([':id' => 2], $db->firstBound);
    }

    private static function form(int $id, string $package): array
    {
        return [
            'id' => $id, 'name' => 'contact', 'title' => 'Contact', 'description' => '',
            'published' => 1, 'package' => $package, 'pages' => 1, 'created' => null,
            'modified' => null, 'quickmode' => 0, 'has_notification_address' => 0,
            'emailntf' => 0, 'emaillog' => 0, 'emailxml' => 0, 'dblog' => 1,
            'double_opt' => 0, 'prevmode' => 0, 'width' => 0, 'widthmode' => 0,
            'height' => 0, 'heightmode' => 0, 'autoheight' => 0,
        ];
    }
}

// Test doubles exercise the catalog's database query without a Joomla runtime.
// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses
final class FormCatalogAmbiguousText
{
    public static function _(string $key): string
    {
        return $key;
    }
}

final class FormCatalogAmbiguousParameterType
{
    public const INTEGER = 'integer';
}

final class FormCatalogAmbiguousDatabase implements DatabaseInterface
{
    public int $queries = 0;
    public ?int $firstLimit = null;
    public string $firstWhere = '';
    public array $firstBound = [];

    public function __construct(private array $forms)
    {
    }

    public function createQuery(): FormCatalogAmbiguousQuery
    {
        return new FormCatalogAmbiguousQuery();
    }

    public function quoteName(string|array $name): string|array
    {
        return $name;
    }

    public function setQuery(FormCatalogAmbiguousQuery $query): self
    {
        $this->queries++;
        if ($this->queries === 1) {
            $this->firstLimit = $query->limit;
            $this->firstWhere = $query->where;
            $this->firstBound = $query->bound;
        }
        return $this;
    }

    public function loadAssocList(): array
    {
        return $this->queries === 1 ? $this->forms : [];
    }
}

final class FormCatalogAmbiguousQuery
{
    public ?int $limit = null;
    public string $where = '';
    public array $bound = [];

    public function select(mixed $value): self
    {
        return $this;
    }
    public function from(mixed $value): self
    {
        return $this;
    }
    public function where(mixed $value): self
    {
        $this->where = $value;
        return $this;
    }
    public function whereIn(mixed $name, mixed $value): self
    {
        return $this;
    }
    public function bind(mixed $name, mixed $value, mixed $type = null): self
    {
        $this->bound[$name] = $value;
        return $this;
    }
    public function group(mixed $value): self
    {
        return $this;
    }
    public function order(mixed $value): self
    {
        return $this;
    }

    public function setLimit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }
}
// phpcs:enable PSR1.Classes.ClassDeclaration.MultipleClasses
