<?php

/**
 * BreezingForms NG - A Joomla Forms Application
 *
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 *
 * SPDX-License-Identifier: GPL-2.0-or-later
 **/

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Administrator\Service\SampleFormService;

/** The site menu entry of the sample form: "Samples", then the form, and nothing else. */
final class SampleFormServiceTest extends TestCase
{
    private function source(): string
    {
        return (string) file_get_contents(
            __DIR__ . '/../../administrator/components/com_breezingformsng/src/Service/SampleFormService.php'
        );
    }

    public function testTheMenuHasASamplesSeparatorWithTheFormUnderIt(): void
    {
        $source = $this->source();

        self::assertStringContainsString("MENU_PARENT_TITLE = 'Samples'", $source);
        self::assertStringContainsString("'type' => 'separator'", $source);
        self::assertStringContainsString("'ff_com_name' => \$this->formName()", $source);
        self::assertStringContainsString("'link' => self::FORM_LINK", $source);
    }

    public function testExistingEntriesAreReusedNeverDuplicated(): void
    {
        $source = $this->source();

        // The form entry is looked up by its link and form name before anything is created.
        self::assertLessThan(
            (int) strpos($source, '$menutype = $this->siteMenuType();'),
            (int) strpos($source, '$existing = $this->findFormMenuItem();')
        );
        self::assertStringContainsString("'published') . ' >= 0'", $source);
    }

    public function testTheShippedPackageMatchesTheServiceConstants(): void
    {
        $package = json_decode(
            (string) file_get_contents(
                __DIR__ . '/../../administrator/components/com_breezingformsng/packages/samples/bfng-sample-contact.json'
            ),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        self::assertSame('Sample_Contact', $package['forms'][0]['data']['name']);
        self::assertSame('Sample', $package['forms'][0]['data']['package']);
        // The import puts the form in the package named at the top of the file: the service looks for it there.
        self::assertSame('Sample', $package['package']);
        self::assertSame('Sample contact form', $package['forms'][0]['data']['title']);
        self::assertStringContainsString("FORM_NAME = 'Sample_Contact'", $this->source());
        self::assertStringContainsString("FORM_PACKAGE_NAME = 'Sample'", $this->source());
        // The samples installed by the earlier versions, in QuickModeForms, are still recognised.
        self::assertStringContainsString("LEGACY_FORM_PACKAGE_NAME = 'QuickModeForms'", $this->source());
        self::assertStringContainsString("quoteName('package') . ' IN (:package, :legacyPackage)'", $this->source());
        self::assertStringContainsString('getFormNameCount', $this->source());
        self::assertStringContainsString("FORM_TITLE = 'Sample contact form'", $this->source());
    }

    public function testOnlyTheSamplesOfTheCatalogExist(): void
    {
        self::assertSame(['contact', 'event', 'quote', 'survey', 'booking', 'widgets', 'demo'], SampleFormService::slugs());
        foreach (['', 'Contact', '../contact', 'contact.json', 'unknown', "contact\0"] as $slug) {
            self::assertFalse(SampleFormService::isSample($slug), $slug);
        }
        self::assertTrue(SampleFormService::isSample('quote'));
    }

    public function testEverySampleHasItsOwnFormNameAliasAndPackageFile(): void
    {
        $names = array_column(SampleFormService::SAMPLES, 'name');
        $aliases = array_column(SampleFormService::SAMPLES, 'alias');
        $files = array_column(SampleFormService::SAMPLES, 'file');

        foreach ([$names, $aliases, $files] as $values) {
            self::assertSame($values, array_values(array_unique($values)));
        }
        foreach (SampleFormService::SAMPLES as $slug => $sample) {
            self::assertMatchesRegularExpression('/^Sample_[A-Za-z]+$/', $sample['name'], $slug);
            self::assertMatchesRegularExpression('/^bf-sample-[a-z]+$/', $sample['alias'], $slug);
            self::assertFileExists(
                __DIR__ . '/../../administrator/components/com_breezingformsng/packages/samples/' . $sample['file']
            );
            self::assertContains($sample['category'], SampleFormService::CATEGORIES);
            self::assertSame($sample['category'], SampleFormService::category($slug));
        }
    }

    public function testAnUnknownSampleIsRefusedBeforeAnyPathIsBuilt(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        SampleFormService::packagePath('../../configuration');
    }

    /**
     * @param list<array<string, mixed>> $separators Separators already in the menu.
     * @param list<string>               $expected   Aliases of the items stored by the call.
     */
    #[RunInSeparateProcess]
    #[DataProvider('parentCases')]
    public function testTheSamplesSeparatorIsFoundByTitleOrByTheAliasItWouldTake(
        array $separators,
        int $expectedParent,
        array $expected
    ): void {
        class_alias(SampleMenuTableStub::class, 'Joomla\\CMS\\Table\\Menu');
        class_alias(SampleParameterTypeStub::class, 'Joomla\\Database\\ParameterType');
        class_alias(SampleDatabaseInterfaceStub::class, 'Joomla\\Database\\DatabaseInterface');
        SampleMenuTableStub::$stored = [];
        SampleMenuTableStub::$nextId = 100;
        $db = new SampleMenuDatabaseStub($separators);

        $id = (new SampleFormService($db))->ensureMenu();

        self::assertSame(100 + count($expected) - 1, $id);
        self::assertSame($expected, array_column(SampleMenuTableStub::$stored, 'alias'));
        $child = SampleMenuTableStub::$stored[count($expected) - 1];
        self::assertSame('bf-sample-contact', $child['alias']);
        self::assertSame('tests/bf-sample-contact', $child['path']);
        self::assertSame($expectedParent > 0 ? $expectedParent : 100, $child['parent_id']);
        // One alias under one parent: the separator is never stored twice.
        self::assertCount(count($expected), SampleMenuTableStub::$stored);
        self::assertSame(
            $expectedParent > 0 ? [] : ['tests'],
            array_column(array_filter(SampleMenuTableStub::$stored, static fn (array $row): bool => $row['type'] === 'separator'), 'alias')
        );
    }

    /** @return array<string, array{list<array<string, mixed>>, int, list<string>}> */
    public static function parentCases(): array
    {
        return [
            'legacy Tests separator reused' => [[['id' => 7, 'title' => 'Tests', 'alias' => 'tests']], 7, ['bf-sample-contact']],
            'Samples separator reused' => [[['id' => 9, 'title' => 'Samples', 'alias' => 'samples']], 9, ['bf-sample-contact']],
            'the one titled Samples comes first' => [
                [['id' => 7, 'title' => 'Tests', 'alias' => 'tests'], ['id' => 9, 'title' => 'Samples', 'alias' => 'x']],
                9,
                ['bf-sample-contact'],
            ],
            'no separator: created once, then the form' => [[], 0, ['tests', 'bf-sample-contact']],
        ];
    }
}

// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses
final class SampleParameterTypeStub
{
    public const STRING = 'string';
}

interface SampleDatabaseInterfaceStub
{
}

final class SampleMenuTableStub
{
    /** @var list<array<string, mixed>> */
    public static array $stored = [];
    public static int $nextId = 100;
    public int $id = 0;
    /** @var array<string, mixed> */
    private array $data = [];

    public function __construct(mixed $db)
    {
    }
    public function setLocation(int $parent, string $position): void
    {
    }
    /** @param array<string, mixed> $data */
    public function bind(array $data): bool
    {
        $this->data = $data;
        return true;
    }
    public function check(): bool
    {
        return true;
    }
    public function store(): bool
    {
        $this->id = self::$nextId++;
        self::$stored[] = $this->data;
        return true;
    }
}

/** Answers the separator lookup from the rows given, as the real query would: title OR alias, title first. */
final class SampleMenuDatabaseStub implements SampleDatabaseInterfaceStub
{
    private SampleMenuQueryStub $query;

    /** @param list<array<string, mixed>> $separators */
    public function __construct(private readonly array $separators)
    {
    }
    public function createQuery(): SampleMenuQueryStub
    {
        return new SampleMenuQueryStub();
    }
    public function quoteName(string|array $name): string|array
    {
        return $name;
    }
    public function quote(string $value): string
    {
        return "'" . $value . "'";
    }
    public function setQuery(SampleMenuQueryStub $query): self
    {
        $this->query = $query;
        return $this;
    }
    public function loadResult(): mixed
    {
        $query = $this->query;
        if ($query->table === '#__menu_types') {
            return 'mainmenu';
        }
        if ($query->table === '#__extensions') {
            return 5;
        }
        // Form entry lookup: the sample is not installed yet.
        if (!$query->isSeparatorLookup()) {
            return 0;
        }
        $matches = array_values(array_filter(
            $this->separators,
            static fn (array $row): bool => $row['title'] === $query->binds[':title'] || $row['alias'] === $query->binds[':alias']
        ));
        usort(
            $matches,
            static fn (array $a, array $b): int => [$a['title'] === $query->binds[':orderTitle'] ? 0 : 1, $a['id']]
                <=> [$b['title'] === $query->binds[':orderTitle'] ? 0 : 1, $b['id']]
        );
        return $matches[0]['id'] ?? 0;
    }
}

final class SampleMenuQueryStub
{
    public string $table = '';
    /** @var array<string, mixed> */
    public array $binds = [];
    private string $conditions = '';

    public function select(mixed $columns): self
    {
        return $this;
    }
    public function from(string $table): self
    {
        $this->table = $table;
        return $this;
    }
    public function where(string $where): self
    {
        $this->conditions .= ' ' . $where;
        return $this;
    }
    public function order(mixed $order): self
    {
        return $this;
    }
    public function setLimit(int $limit): self
    {
        return $this;
    }
    public function bind(string $name, mixed &$value, mixed $type = null): self
    {
        $this->binds[$name] = $value;
        return $this;
    }
    public function isSeparatorLookup(): bool
    {
        return $this->table === '#__menu' && str_contains($this->conditions, 'separator');
    }
}
// phpcs:enable PSR1.Classes.ClassDeclaration.MultipleClasses
