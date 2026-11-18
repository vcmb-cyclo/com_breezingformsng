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

/**
 * The sample form shipped with the component: a package file that every site
 * can import, with nothing of the site it was made on.
 */
final class SampleContactPackageTest extends TestCase
{
    private const FILE = __DIR__
        . '/../../administrator/components/com_breezingformsng/packages/samples/bfng-sample-contact.json';

    /** @return array<string, mixed> */
    private function package(): array
    {
        $payload = json_decode((string) file_get_contents(self::FILE), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($payload);

        return $payload;
    }

    public function testItIsAPackageOfOneFormWithItsLibraryScripts(): void
    {
        $package = $this->package();

        self::assertSame('breezingformsng-package', $package['format']);
        self::assertSame(3, $package['version']);
        self::assertSame([], $package['pieces']);
        self::assertSame([], $package['menus']);
        self::assertCount(1, $package['forms']);
        self::assertSame('Sample_Contact', $package['forms'][0]['data']['name']);
    }

    public function testEveryScriptTheFormReferencesIsInThePackage(): void
    {
        $package = $this->package();
        $form = $package['forms'][0];
        $bundled = array_map(static fn (array $script): int => (int) $script['source_id'], $package['scripts']);

        // The import remaps the ids of the bundled scripts: an id left outside would point to another item.
        self::assertNotSame([], $bundled);
        foreach ($form['elements'] as $element) {
            foreach (['script1', 'script2', 'script3'] as $slot) {
                if ((int) $element[$slot . 'cond'] > 0) {
                    self::assertContains((int) $element[$slot . 'id'], $bundled, $element['name'] . ' ' . $slot);
                }
            }
        }

        // No custom code: the form data holds no script or piece of its own, except
        // the one the QuickMode editor writes to show the thank-you page.
        foreach (['script1', 'piece1', 'piece2', 'piece3', 'piece4'] as $slot) {
            self::assertSame(0, (int) $form['data'][$slot . 'cond'], $slot);
        }
        self::assertSame(2, (int) $form['data']['script2cond']);
        self::assertSame(
            'function ff_Sample_Contact_submitted(status, message){if(status==0){ff_switchpage(2);}else{alert(message);}}',
            $form['data']['script2code']
        );

        // Only the library functions of BreezingForms are bundled.
        foreach ($package['scripts'] as $script) {
            self::assertMatchesRegularExpression('/^ff_[a-z_]+$/i', $script['data']['name']);
        }
    }

    public function testItHoldsNothingOfTheSiteItWasMadeOn(): void
    {
        $raw = (string) file_get_contents(self::FILE);

        // The only address is the placeholder of the email field, on the reserved example.org domain.
        $withoutPlaceholder = str_replace('name@example.org', '', $raw);
        self::assertDoesNotMatchRegularExpression('/vcmb|gn_[a-z_]+|<\?php|heolwenn|@[a-z0-9-]+\.[a-z]{2,}/i', $withoutPlaceholder);
        $form = $this->package()['forms'][0];
        self::assertSame('', $form['data']['emailadr']);
        self::assertNull($form['data']['checked_out']);
        // The notification goes to the address of the site that installs it (1), never to a stored one (2).
        self::assertSame(1, (int) $form['data']['emailntf']);
        self::assertSame(1, (int) $form['data']['mb_emailntf']);
    }

    public function testEveryFieldCanBeFilledIn(): void
    {
        $tree = $this->package()['forms'][0]['data']['template_code'];
        $elements = [];
        $walk = static function (array $node) use (&$walk, &$elements): void {
            if (($node['properties']['type'] ?? '') === 'element') {
                $elements[] = $node['properties'];
            }
            foreach ($node['children'] ?? [] as $child) {
                $walk($child);
            }
        };
        $walk($tree);

        self::assertCount(12, $elements);
        foreach ($elements as $properties) {
            // A read-only field cannot be typed in: the sample must not inherit it from the form it was made from.
            self::assertEmpty($properties['readonly'] ?? false, $properties['bfName'] . ' is read-only');
            self::assertEmpty($properties['size'] ?? '', $properties['bfName'] . ' has a fixed width');
        }
        self::assertTrue($tree['properties']['themebootstrapLabelTop']);
        // The buttons are the ones of the form itself (primary Send, secondary Reset).
        self::assertTrue($tree['properties']['submitInclude']);
        self::assertTrue($tree['properties']['cancelInclude']);
    }

    public function testItShowsHintsLimitsAndARequiredConsent(): void
    {
        $tree = $this->package()['forms'][0]['data']['template_code'];
        $byName = [];
        foreach ($tree['children'][0]['children'] as $section) {
            foreach ($section['children'] as $node) {
                $byName[$node['properties']['bfName']] = $node['properties'];
            }
        }

        foreach (['LastName', 'FirstName', 'Email', 'Subject', 'Message', 'Consent'] as $name) {
            self::assertTrue($byName[$name]['required'], $name . ' is required');
            self::assertSame(1, (int) $byName[$name]['validationCondition'], $name . ' is validated');
        }
        self::assertFalse($byName['Phone']['required']);
        self::assertSame('ff_validemail', $byName['Email']['validationFunctionName']);
        self::assertSame('bfCheckbox', $byName['Consent']['bfType']);
        self::assertSame('ff_checked', $byName['Consent']['validationFunctionName']);
        self::assertSame('2000', $byName['Message']['maxlength']);
        self::assertTrue($byName['Message']['showMaxlengthCounter']);
        self::assertSame('20', $byName['Phone']['maxLength']);
        self::assertNotSame('', $byName['Phone']['hint']);
        self::assertSame('bfCaptcha', $byName['Captcha']['bfType']);
    }

    public function testASentPageReplacesTheFormAfterSubmission(): void
    {
        $form = $this->package()['forms'][0];
        $tree = $form['data']['template_code'];

        self::assertTrue($tree['properties']['lastPageThankYou']);
        self::assertCount(2, $tree['children']);
        self::assertSame(2, (int) $form['data']['pages']);
        self::assertSame([], $tree['children'][1]['children']);
        self::assertStringContainsString('class="bf-sent"', $tree['children'][1]['properties']['pageIntro']);
        self::assertStringContainsString('Message sent', $tree['children'][1]['properties']['pageIntro']);
        // "Send another message" opens a new form on the page of the sample, never the home page
        // an empty href resolves to under Joomla's base tag.
        self::assertStringNotContainsString('href=""', $tree['children'][1]['properties']['pageIntro']);
        self::assertStringContainsString('href="tests/bf-sample-contact.html"', $tree['children'][1]['properties']['pageIntro']);
    }

    public function testItShowsTheMainKindsOfFields(): void
    {
        $types = array_column($this->package()['forms'][0]['elements'], 'type');

        foreach (['Text', 'Radio Group', 'Select List', 'Checkbox Group', 'Textarea', 'Checkbox', 'Captcha'] as $type) {
            self::assertContains($type, $types);
        }
    }
}
