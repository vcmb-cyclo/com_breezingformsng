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

use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Administrator\Service\SampleFormService;

/** The site menu entry of the sample form: "Tests", then the form, and nothing else. */
final class SampleFormServiceTest extends TestCase
{
    private function source(): string
    {
        return (string) file_get_contents(
            __DIR__ . '/../../administrator/components/com_breezingformsng/src/Service/SampleFormService.php'
        );
    }

    public function testTheMenuHasATestsSeparatorWithTheFormUnderIt(): void
    {
        $source = $this->source();

        self::assertStringContainsString("MENU_PARENT_TITLE = 'Tests'", $source);
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
        self::assertSame(['contact', 'event', 'quote', 'survey', 'widgets'], SampleFormService::slugs());
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
}
