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
use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Administrator\Model\PackageFilter;

/**
 * The package filter of the lists of forms, menus, scripts and pieces offers
 * the items without package, and the forms list shows the package in a column
 * hidden by default.
 */
final class PackageFilterTest extends TestCase
{
    private const ADMIN = __DIR__ . '/../../administrator/components/com_breezingformsng';

    private const LANGUAGES = ['en-GB', 'fr-FR', 'de-DE', 'it-IT', 'es-ES', 'hu-HU', 'nl-NL', 'tr-TR'];

    private static function source(string $path): string
    {
        $source = file_get_contents($path);

        self::assertIsString($source, $path);

        return $source;
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function filterForms(): iterable
    {
        foreach (['forms', 'menus', 'pieces', 'scripts'] as $name) {
            yield $name => [$name];
        }
    }

    #[DataProvider('filterForms')]
    public function testEveryPackageFilterOffersTheItemsWithoutPackage(string $name): void
    {
        $xml = self::source(self::ADMIN . '/forms/filter_' . $name . '.xml');
        $all = strpos($xml, '<option value="">COM_BREEZINGFORMSNG_ALL_PACKAGES</option>');
        $none = strpos($xml, '<option value="__none__">COM_BREEZINGFORMSNG_NO_PACKAGE</option>');

        self::assertNotFalse($all);
        self::assertNotFalse($none);
        self::assertGreaterThan($all, $none, 'The "No package" option follows "All packages".');
        self::assertStringContainsString('type="Packagefilter"', $xml);
        self::assertStringContainsString('source="' . $name . '"', $xml);
        self::assertStringNotContainsString('type="sql"', $xml);
    }

    public function testTheListModelsShareOnePackageFilter(): void
    {
        $filter = self::source(self::ADMIN . '/src/Model/PackageFilter.php');

        self::assertStringContainsString("public const NONE = '__none__';", $filter);
        self::assertStringContainsString('PackageFilter::encode($package)', self::source(self::ADMIN . '/src/Field/PackagefilterField.php'));
        self::assertStringContainsString("' = ' . \$db->quote('')", $filter);
        self::assertStringContainsString("' = :package'", $filter);

        foreach (['FormsModel' => 'package', 'MenusModel' => 'menus.package', 'PackageListModel' => 'package'] as $model => $column) {
            $source = self::source(self::ADMIN . '/src/Model/' . $model . '.php');

            self::assertStringContainsString(
                "PackageFilter::apply(\$db, \$query, '" . $column . "', (string) \$this->getState('filter.package'));",
                $source,
                $model
            );
            self::assertStringNotContainsString("->bind(':package'", $source, $model);
        }
    }

    public function testTheNoPackageOptionIsTranslatedInEveryLanguage(): void
    {
        foreach (self::LANGUAGES as $language) {
            $ini = self::source(self::ADMIN . '/language/' . $language . '/com_breezingformsng.ini');

            self::assertSame(
                1,
                preg_match_all('/^COM_BREEZINGFORMSNG_NO_PACKAGE="-- [^"]+ --"\R/m', $ini),
                $language
            );
        }
    }

    public function testTheFormsListShowsThePackageInAColumnHiddenByDefault(): void
    {
        $template = self::source(self::ADMIN . '/tmpl/forms/default.php');
        $view = self::source(self::ADMIN . '/src/View/Forms/HtmlView.php');
        $model = self::source(self::ADMIN . '/src/Model/FormsModel.php');
        $script = self::source(__DIR__ . '/../../media/com_breezingformsng/js/admin/forms-columns.js');

        self::assertStringContainsString('data-name="bfng-forms"', $template);
        self::assertStringContainsString("'COM_BREEZINGFORMSNG_FORMS_PACKAGE', 'package'", $template);
        self::assertStringContainsString('<td class="d-none"><?= htmlspecialchars((string) $form->package); ?></td>', $template);
        // The Columns menu lists a first <th> as a column: the checkboxes header is a <td>.
        self::assertStringContainsString('<td class="w-1 text-center">', $template);
        self::assertStringContainsString("'name', 'package', 'pages'", $model);
        self::assertStringContainsString("'com_breezingformsng.forms-columns'", $view);

        // Package is the fifth cell of the row, index 4 for table-columns.js.
        self::assertStringContainsString("'joomla-tablecolumns-bfng-forms', '4'", $script);
    }

    public function testTheNoPackageValueIsNotThePackageOfANewForm(): void
    {
        $view = self::source(self::ADMIN . '/src/View/Forms/HtmlView.php');

        self::assertStringContainsString('$this->pkg = PackageFilter::selectedPackage($filterPackage);', $view);
    }

    public function testRealPackageIdentifiersNeverCollideWithTheNoPackageControl(): void
    {
        foreach (['__none__', '__new__', 'package:YQ', 'a package'] as $package) {
            $encoded = PackageFilter::encode($package);

            self::assertNotSame(PackageFilter::NONE, $encoded);
            self::assertSame($package, PackageFilter::selectedPackage($encoded));
        }

        self::assertSame('', PackageFilter::selectedPackage(PackageFilter::NONE));
        self::assertSame('legacy', PackageFilter::selectedPackage('legacy'));
        self::assertSame('package:not_base64!', PackageFilter::selectedPackage('package:not_base64!'));
    }

    public function testALegacyRawValueSelectsTheOptionOfThatPackage(): void
    {
        self::assertSame(PackageFilter::encode('demo'), PackageFilter::toSelectorValue('demo'));
        self::assertSame(PackageFilter::NONE, PackageFilter::toSelectorValue(PackageFilter::NONE));
        self::assertSame('', PackageFilter::toSelectorValue(''));
        self::assertSame(PackageFilter::encode('demo'), PackageFilter::toSelectorValue(PackageFilter::encode('demo')));
        self::assertSame(PackageFilter::encode('package:not_base64!'), PackageFilter::toSelectorValue('package:not_base64!'));

        foreach (['demo', 'a package', '__new__'] as $package) {
            self::assertSame($package, PackageFilter::selectedPackage(PackageFilter::toSelectorValue($package)));
        }
    }

    public function testAnEmptyPayloadIsARawValueNeverAnEmptyPackage(): void
    {
        foreach (['package:', 'package:=', 'package:===='] as $value) {
            self::assertSame($value, PackageFilter::selectedPackage($value));
            self::assertSame(PackageFilter::encode($value), PackageFilter::toSelectorValue($value));
            self::assertSame($value, PackageFilter::selectedPackage(PackageFilter::toSelectorValue($value)));
        }
    }

    public function testTheSelectorDisplaysTheLegacyValue(): void
    {
        $field = self::source(self::ADMIN . '/src/Field/PackagefilterField.php');

        self::assertStringContainsString('PackageFilter::toSelectorValue($this->value)', $field);
        self::assertStringContainsString("#[\\Override]\n    protected function getInput()", $field);
    }
}
