<?php

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Administrator\Helper\FormsAdvancedOptionsHtml;

final class FormsMailFieldNamesTest extends TestCase
{
    /**
     * @return iterable<string, array{\stdClass, int}>
     */
    public static function htmlSelections(): iterable
    {
        yield 'new empty template starts in HTML mode' => [(object) ['id' => 0], 1];
        yield 'existing default email preserves text preference' => [(object) [
            'id' => 4,
            'mb_email_type' => 0,
            'mb_email_custom_html' => 0,
            'mb_email_custom_template' => 'Plain text',
        ], 0];
        yield 'existing custom email preserves text preference' => [(object) [
            'id' => 4,
            'mb_email_type' => 1,
            'mb_email_custom_html' => 0,
            'mb_email_custom_template' => 'Plain text',
        ], 0];
        yield 'existing HTML preference is retained' => [(object) [
            'id' => 4,
            'mb_email_custom_html' => 1,
        ], 1];
        yield 'unsaved template keeps its preference' => [(object) [
            'id' => 0,
            'mb_email_custom_html' => 0,
            'mb_email_custom_template' => 'Plain text',
        ], 0];
    }

    #[DataProvider('htmlSelections')]
    public function testHtmlSelectionPreservesStoredPreference(\stdClass $form, int $expected): void
    {
        self::assertSame($expected, FormsAdvancedOptionsHtml::mailbackHtmlSelection($form));
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testOnlyLoggedMailCapableFieldsAreOffered(): void
    {
        eval('namespace Joomla\Database; interface DatabaseInterface {} final class ParameterType {
            public const STRING = "string";
            public const INTEGER = "integer";
        }');
        eval('namespace Joomla\CMS; final class Factory {
            public static object $container;
            public static function getContainer(): object { return self::$container; }
        }');

        $rows = [
            ['form' => 7, 'name' => 'first_name', 'type' => 'Text', 'logging' => 1],
            ['form' => 7, 'name' => 'private_note', 'type' => 'Text', 'logging' => 0],
            ['form' => 7, 'name' => 'attachment', 'type' => 'File Upload', 'logging' => 1],
            ['form' => 7, 'name' => 'submit', 'type' => 'Submit Button', 'logging' => 1],
            ['form' => 7, 'name' => 'bfFakeName', 'type' => 'Text', 'logging' => 1],
            ['form' => 8, 'name' => 'other_form', 'type' => 'Text', 'logging' => 1],
        ];

        $database = new class ($rows) implements \Joomla\Database\DatabaseInterface {
            /** @var array<int, array{form: int, name: string, type: string, logging: int}> */
            private array $rows;
            private ?object $query = null;

            public function __construct(array $rows)
            {
                $this->rows = $rows;
            }

            public function quoteName(string $name): string
            {
                return $name;
            }

            public function createQuery(): object
            {
                return new class {
                    /** @var list<string> */
                    public array $conditions = [];
                    /** @var array<string, list<string>> */
                    public array $included = [];
                    /** @var array<string, list<string>> */
                    public array $excluded = [];
                    /** @var array<string, int> */
                    public array $bindings = [];

                    public function select(string $column): self
                    {
                        return $this;
                    }

                    public function from(string $table): self
                    {
                        return $this;
                    }

                    public function where(string $condition): self
                    {
                        $this->conditions[] = $condition;
                        return $this;
                    }

                    public function whereIn(string $column, array $values, string $type): self
                    {
                        $this->included[$column] = $values;
                        return $this;
                    }

                    public function whereNotIn(string $column, array $values, string $type): self
                    {
                        $this->excluded[$column] = $values;
                        return $this;
                    }

                    public function order(string $column): self
                    {
                        return $this;
                    }

                    public function bind(string $name, int $value, string $type): self
                    {
                        $this->bindings[$name] = $value;
                        return $this;
                    }
                };
            }

            public function setQuery(object $query): self
            {
                $this->query = $query;
                return $this;
            }

            /** @return list<string> */
            public function loadColumn(): array
            {
                $query = $this->query;
                if ($query === null) {
                    throw new \LogicException('No query was set.');
                }

                $names = [];
                foreach ($this->rows as $row) {
                    if ($row['form'] !== ($query->bindings[':formId'] ?? null)) {
                        continue;
                    }
                    if (in_array('logging = 1', $query->conditions, true) && $row['logging'] !== 1) {
                        continue;
                    }
                    if (isset($query->included['type']) && !in_array($row['type'], $query->included['type'], true)) {
                        continue;
                    }
                    if (in_array($row['name'], $query->excluded['name'] ?? [], true)) {
                        continue;
                    }
                    $names[] = $row['name'];
                }

                return $names;
            }
        };

        \Joomla\CMS\Factory::$container = new class ($database) {
            public function __construct(private readonly object $database)
            {
            }

            public function get(string $id): object
            {
                return $this->database;
            }
        };

        self::assertSame([], FormsAdvancedOptionsHtml::mailFieldNames(7, 0));
        self::assertSame(['first_name', 'attachment'], FormsAdvancedOptionsHtml::mailFieldNames(7, 1));
        self::assertSame(['first_name', 'attachment'], FormsAdvancedOptionsHtml::mailFieldNames(7, 2));
    }
}
