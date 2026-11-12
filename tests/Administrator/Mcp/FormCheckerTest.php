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

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator\Mcp;

use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Administrator\Mcp\FormChecker;

require_once __DIR__ . '/McpTestDoubles.php';

final class FormCheckerTest extends TestCase
{
    private const FORM = ['id' => 7, 'name' => 'contact', 'title' => 'Contact', 'pages' => 2];

    /** @var array<int, array<string, mixed>> */
    private const SCRIPTS = [
        5 => ['id' => 5, 'name' => 'ff_validemail', 'type' => 'Element Validation', 'published' => 1],
        6 => ['id' => 6, 'name' => 'ff_url_validation', 'type' => 'Element Validation', 'published' => 0],
        9 => ['id' => 9, 'name' => 'ff_init_x', 'type' => 'Element Init', 'published' => 1],
    ];

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private static function element(int $id, array $overrides = []): array
    {
        return $overrides + [
            'id' => $id, 'name' => 'field' . $id, 'title' => 'Field ' . $id, 'type' => 'Text', 'page' => 1, 'published' => 1,
            'script1cond' => 0, 'script1id' => 0, 'script2cond' => 0, 'script2id' => 0, 'script3cond' => 0, 'script3id' => 0,
            'script1custom' => 0, 'script2custom' => 0, 'script3custom' => 0,
        ];
    }

    /**
     * @param list<array<string, mixed>> $elements
     * @return list<string> "code:element_id" of every issue.
     */
    private static function codes(array $elements): array
    {
        $report = FormChecker::analyse(self::FORM, $elements, self::SCRIPTS);

        return array_map(static fn (array $issue): string => $issue['code'] . ':' . $issue['element_id'], $report['issues']);
    }

    public function testAConsistentFormHasNoIssue(): void
    {
        $report = FormChecker::analyse(self::FORM, [self::element(1), self::element(2, ['type' => 'Regular Button', 'name' => '', 'title' => 'Send'])], self::SCRIPTS);

        self::assertSame([], $report['issues']);
        self::assertSame(['elements' => 2, 'errors' => 0, 'warnings' => 0, 'suggestions' => 0], $report['summary']);
    }

    public function testAnInputWithoutNameOrLabelIsReported(): void
    {
        self::assertSame(['missing_name:1', 'missing_label:2'], self::codes([
            self::element(1, ['name' => '']),
            self::element(2, ['title' => '  ']),
        ]));
    }

    public function testAHiddenInputNeedsNoLabelAndAButtonNoName(): void
    {
        self::assertSame([], self::codes([
            self::element(1, ['type' => 'Hidden Input', 'title' => '']),
            self::element(2, ['type' => 'Regular Button', 'name' => '']),
        ]));
    }

    public function testADuplicateNameIsReportedOnceWithAllItsElements(): void
    {
        $report = FormChecker::analyse(self::FORM, [self::element(1, ['name' => 'a']), self::element(2, ['name' => 'a']), self::element(3, ['name' => 'a'])], self::SCRIPTS);

        self::assertCount(1, $report['issues']);
        self::assertSame('duplicate_name', $report['issues'][0]['code']);
        self::assertSame([1, 2, 3], $report['issues'][0]['element_ids']);
        self::assertSame(1, $report['summary']['errors']);
    }

    public function testAnElementOutsideThePagesOfTheFormIsReported(): void
    {
        self::assertSame(['page_out_of_range:1', 'page_out_of_range:2'], self::codes([
            self::element(1, ['page' => 3]),
            self::element(2, ['page' => 0]),
        ]));
    }

    public function testLibraryScriptsMustExistBePublishedAndHaveTheRightType(): void
    {
        $codes = self::codes([
            self::element(1, ['script3cond' => 1, 'script3id' => 99]),
            self::element(2, ['script3cond' => 1, 'script3id' => 6]),
            self::element(3, ['script3cond' => 1, 'script3id' => 9]),
            self::element(4, ['script1cond' => 1, 'script1id' => 9]),
            self::element(5, ['script3cond' => 1, 'script3id' => 5]),
        ]);

        self::assertSame(['library_script_validation:1', 'library_script_validation:2', 'library_script_validation:3'], $codes);
    }

    public function testACustomScriptWithoutCodeIsAWarning(): void
    {
        $report = FormChecker::analyse(self::FORM, [
            self::element(1, ['script2cond' => 2, 'script2custom' => 0]),
            self::element(2, ['script2cond' => 2, 'script2custom' => 1]),
        ], self::SCRIPTS);

        self::assertSame(['empty_custom_action'], array_column($report['issues'], 'code'));
        self::assertSame('warning', $report['issues'][0]['severity']);
    }

    public function testAnEmailFieldWithoutValidationGetsTheLibraryValidation(): void
    {
        $report = FormChecker::analyse(self::FORM, [
            self::element(1, ['name' => 'email', 'title' => 'Your e-mail']),
            self::element(2, ['name' => 'courriel', 'title' => 'Votre courriel', 'script3cond' => 1, 'script3id' => 5]),
            self::element(3, ['name' => 'nombre', 'title' => 'Nombre']),
        ], self::SCRIPTS);

        self::assertCount(1, $report['suggestions']);
        self::assertSame(1, $report['suggestions'][0]['element_id']);
        self::assertSame('ff_validemail', $report['suggestions'][0]['validation']);
        self::assertSame(5, $report['suggestions'][0]['script_id']);
    }

    public function testAnUnpublishedValidationIsNeverProposed(): void
    {
        $report = FormChecker::analyse(self::FORM, [self::element(1, ['name' => 'website', 'title' => 'Website'])], self::SCRIPTS);

        self::assertSame([], $report['suggestions']);
    }

    public function testTheIssuesAreCappedAndTheCountsStayComplete(): void
    {
        $elements = [];
        for ($i = 1; $i <= FormChecker::MAX_ISSUES + 25; $i++) {
            $elements[] = self::element($i, ['name' => '']);
        }
        $report = FormChecker::analyse(self::FORM, $elements, self::SCRIPTS);

        self::assertCount(FormChecker::MAX_ISSUES, $report['issues']);
        self::assertTrue($report['issues_truncated']);
        self::assertSame(FormChecker::MAX_ISSUES + 25, $report['summary']['errors']);
    }
}
