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

/**
 * Every sample form of the catalog: a package any site can import, with its script references bundled,
 * its visibility rules pointing at real fields, and nothing of the site it was made on.
 */
final class SamplePackagesTest extends TestCase
{
    private const DIRECTORY = __DIR__ . '/../../administrator/components/com_breezingformsng/packages/samples/';

    /** @return array<string, array{0: string, 1: string, 2: string}> */
    public static function samples(): array
    {
        $source = (string) file_get_contents(
            __DIR__ . '/../../administrator/components/com_breezingformsng/src/Service/SampleFormService.php'
        );
        preg_match_all(
            "/'(\w+)' => \[\s*'name' => (?:self::FORM_NAME|'(\w+)'),\s*'title' => (?:self::FORM_TITLE|'([^']+)'),"
            . "\s*'alias' => '([\w-]+)', 'file' => '([\w.-]+)'/",
            $source,
            $matches,
            PREG_SET_ORDER
        );
        $samples = [];
        foreach ($matches as $match) {
            $samples[$match[1]] = [
                $match[1],
                $match[2] !== '' ? $match[2] : 'Sample_Contact',
                $match[5],
            ];
        }

        return $samples;
    }

    /** @return array<string, mixed> */
    private function package(string $file): array
    {
        $payload = json_decode((string) file_get_contents(self::DIRECTORY . $file), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($payload);

        return $payload;
    }

    /** @return list<array<string, mixed>> */
    private function elements(array $tree): array
    {
        $found = [];
        $walk = static function (array $node) use (&$walk, &$found): void {
            if (($node['properties']['type'] ?? '') === 'element') {
                $found[] = $node['properties'];
            }
            foreach ($node['children'] ?? [] as $child) {
                $walk($child);
            }
        };
        $walk($tree);

        return $found;
    }

    public function testTheCatalogIsComplete(): void
    {
        self::assertSame(['contact', 'event', 'quote', 'survey'], array_keys(self::samples()));
    }

    #[DataProvider('samples')]
    public function testItIsAPackageOfOneFormInTheQuickModePackage(string $slug, string $name, string $file): void
    {
        $package = $this->package($file);

        self::assertSame('breezingformsng-package', $package['format']);
        self::assertSame('QuickModeForms', $package['package']);
        self::assertCount(1, $package['forms']);
        self::assertSame($name, $package['forms'][0]['data']['name']);
        self::assertSame('QuickModeForms', $package['forms'][0]['data']['package']);
        self::assertSame($name, $package['forms'][0]['data']['template_code']['properties']['name']);
        self::assertSame([], $package['pieces']);
        self::assertSame([], $package['menus']);
    }

    #[DataProvider('samples')]
    public function testEveryLibraryScriptIsBundled(string $slug, string $name, string $file): void
    {
        $package = $this->package($file);
        $bundled = array_map(static fn (array $script): int => (int) $script['source_id'], $package['scripts']);

        foreach ($package['forms'][0]['elements'] as $element) {
            foreach (['script1', 'script2', 'script3'] as $slot) {
                // 1 is a script of the library (it must travel with the form), 2 is code written in the form.
                if ((int) $element[$slot . 'cond'] === 1) {
                    self::assertContains((int) $element[$slot . 'id'], $bundled, $element['name'] . ' ' . $slot);
                }
            }
        }
        foreach ($package['scripts'] as $script) {
            self::assertMatchesRegularExpression('/^ff_[a-z_]+$/i', $script['data']['name']);
        }
    }

    #[DataProvider('samples')]
    public function testItHoldsNothingOfTheSiteItWasMadeOn(string $slug, string $name, string $file): void
    {
        $raw = (string) file_get_contents(self::DIRECTORY . $file);
        $withoutPlaceholders = str_replace('name@example.org', '', $raw);

        self::assertDoesNotMatchRegularExpression(
            '/vcmb|\bgn_[a-z_]+|<\?php|heolwenn|localhost|@[a-z0-9-]+\.[a-z]{2,}/i',
            $withoutPlaceholders
        );
        $data = $this->package($file)['forms'][0]['data'];
        self::assertSame('', $data['emailadr']);
        self::assertNull($data['checked_out']);
        // The notification goes to the address of the site that installs it, never to a stored one.
        self::assertSame(1, (int) $data['emailntf']);
    }

    #[DataProvider('samples')]
    public function testAThankYouPageReplacesTheFormAfterSending(string $slug, string $name, string $file): void
    {
        $tree = $this->package($file)['forms'][0]['data']['template_code'];
        $pages = $tree['children'];
        $last = end($pages);

        self::assertTrue($tree['properties']['lastPageThankYou']);
        self::assertSame([], $last['children']);
        self::assertStringContainsString('class="bf-sent"', $last['properties']['pageIntro']);
        self::assertStringNotContainsString('href=""', $last['properties']['pageIntro']);
        self::assertSame(2, (int) $tree['properties']['submittedScriptCondidtion']);
        self::assertSame(
            'function ff_' . $name . '_submitted(status, message){if(status==0){ff_switchpage(' . count($pages)
            . ');}else{alert(message);}}',
            $tree['properties']['submittedScriptCode']
        );
        self::assertSame(count($pages), (int) $this->package($file)['forms'][0]['data']['pages']);
    }

    #[DataProvider('samples')]
    public function testVisibilityRulesPointAtRealFieldsAndSections(string $slug, string $name, string $file): void
    {
        $tree = $this->package($file)['forms'][0]['data']['template_code'];
        $fields = array_column($this->elements($tree), 'bfName');
        $sections = [];
        $walk = static function (array $node) use (&$walk, &$sections): void {
            if (($node['properties']['type'] ?? '') === 'section') {
                $sections[] = $node['properties']['name'];
            }
            foreach ($node['children'] ?? [] as $child) {
                $walk($child);
            }
        };
        $walk($tree);

        foreach (array_filter(explode("\n", $tree['properties']['toggleFields'])) as $rule) {
            self::assertMatchesRegularExpression('/^turn (on|off) (element|section) \S+ if \S+ (is|isnot) .+$/', $rule);
            $tokens = explode(' ', $rule);
            self::assertContains($tokens[3], $tokens[2] === 'section' ? $sections : $fields, $rule);
            self::assertContains($tokens[5], $fields, $rule);
        }
    }

    #[DataProvider('samples')]
    public function testCustomCodeIsShortReadableAndSelfContained(string $slug, string $name, string $file): void
    {
        foreach ($this->package($file)['forms'][0]['elements'] as $element) {
            if ((int) $element['script2cond'] !== 2) {
                continue;
            }

            $code = (string) $element['script2code'];
            self::assertStringContainsString('function ff_' . $element['name'] . '_action(', $code);
            self::assertLessThan(2200, strlen($code), $element['name'] . ' keeps its code short');
            // No network, no storage, no dynamic evaluation: arithmetic on the fields of the form only.
            self::assertDoesNotMatchRegularExpression(
                '/\b(fetch|XMLHttpRequest|eval|document\.cookie|localStorage|sessionStorage|import\()\b|https?:/i',
                $code
            );
        }
    }

    public function testTheEventTotalIsCalculatedFromThePricesOfTheForm(): void
    {
        $elements = $this->package('bfng-sample-event.json')['forms'][0]['elements'];
        $byName = array_column($elements, null, 'name');

        self::assertStringContainsString('bfEventPrices', $byName['Training']['script2code']);
        foreach (['PricePerPerson', 'Discount', 'Total'] as $field) {
            self::assertSame(1, (int) $byName[$field]['flag2'], $field . ' cannot be typed in');
        }
        // Places are not counted here: availability is checked on the server by the organiser.
        self::assertStringNotContainsString('places', strtolower((string) $byName['Training']['script2code']));
    }

    public function testTheQuoteIsAFourStepFormWithAFileAndASummary(): void
    {
        $package = $this->package('bfng-sample-quote.json');
        $tree = $package['forms'][0]['data']['template_code'];
        $types = array_column($package['forms'][0]['elements'], 'type');

        self::assertCount(5, $tree['children'], 'four steps and the thank-you page');
        self::assertTrue($tree['properties']['pagingInclude']);
        foreach (['File Upload', 'Captcha', 'Checkbox Group', 'Radio Group'] as $type) {
            self::assertContains($type, $types);
        }
        $bfTypes = array_column($this->elements($tree), 'bfType');
        self::assertSame(5, count(array_keys($bfTypes, 'bfSummarize', true)));
        // The consent is asked before the summary: going back from a page validates it.
        $summary = array_column($this->elements(['children' => [$tree['children'][3]]]), 'bfName');
        self::assertNotContains('Consent', $summary);
    }

    public function testTheSurveyShowsAFollowUpOnlyForLowRatings(): void
    {
        $tree = $this->package('bfng-sample-survey.json')['forms'][0]['data']['template_code'];
        $rules = $tree['properties']['toggleFields'];

        self::assertStringContainsString('turn on element LowReason if Overall is 1', $rules);
        self::assertStringContainsString('turn on element LowReason if Overall is 2', $rules);
        self::assertStringContainsString('turn off element LowReason if Overall is 5', $rules);
    }
}
