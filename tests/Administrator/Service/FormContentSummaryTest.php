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

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator\Service;

use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Administrator\Service\FormContentSummary;

final class FormContentSummaryTest extends TestCase
{
    private const ROOT = __DIR__ . '/../../..';

    public function testAFieldIsOneLineWithoutItsUsualSettings(): void
    {
        $summary = $this->summarize([
            ['properties' => [
                'type' => 'element',
                'bfType' => 'bfTextfield',
                'bfName' => 'Email',
                'dbId' => 12,
                'labelPosition' => 'left',
                'label' => 'Adresse é-mail',
                'required' => true,
                'readonly' => false,
                'hint' => '',
                'orderNumber' => '-1',
                'logging' => true,
                'validationCondition' => 0,
                'validationId' => '48',
                'actionCondition' => 1,
                'actionId' => '3',
                'widgetMinChars' => 3,
            ]],
        ]);

        // Empty, off and usual settings, unused groups and identifiers are left out.
        self::assertSame(
            'bfTextfield | label: Adresse é-mail | required: true | actionCondition: 1 | actionId: 3',
            $summary['Email']
        );
        self::assertSame('Sample', $summary['form · name']);
        self::assertSame('page 1', $summary['form · content']);
        self::assertSame('Email', $summary['page 1 · content']);
        self::assertCount(4, $summary);
    }

    public function testAWidgetKeepsItsSettings(): void
    {
        $summary = $this->summarize([
            ['properties' => ['type' => 'element', 'bfName' => 'A', 'widget' => 'phone', 'widgetMinChars' => 3]],
        ]);

        self::assertSame('widget: phone | widgetMinChars: 3', $summary['A']);
    }

    public function testNamesakesAndMovesStayTellable(): void
    {
        $first = ['properties' => ['type' => 'element', 'bfName' => 'A', 'label' => 'One']];
        $second = ['properties' => ['type' => 'element', 'bfName' => 'A', 'label' => 'Two']];
        $summary = $this->summarize([$first, $second]);

        self::assertSame('A, A (2)', $summary['page 1 · content']);
        self::assertSame('label: One', $summary['A']);
        self::assertSame('label: Two', $summary['A (2)']);
    }

    public function testAFieldNamedByANumberKeepsItsLine(): void
    {
        $summary = $this->summarize([
            ['properties' => ['type' => 'element', 'bfName' => '2026', 'label' => 'Year']],
        ]);

        self::assertSame('label: Year', $summary['2026']);
        self::assertSame('2026', $summary['page 1 · content']);
    }

    public function testAnUnreadableCodeGivesNoSummary(): void
    {
        self::assertSame([], FormContentSummary::fromTemplateCode(''));
        self::assertSame([], FormContentSummary::fromTemplateCode('not base64 !'));
        self::assertSame([], FormContentSummary::fromTemplateCode(base64_encode('<form>')));
    }

    public function testTheComparisonShowsTheSummaryWithoutAddingVersions(): void
    {
        $script = (string) file_get_contents(self::ROOT . '/script.php');
        $form = substr($script, (int) strpos($script, "'com_breezingformsng.form' => ["));
        $form = substr($form, 0, (int) strpos($form, "'com_breezingformsng.script' => ["));
        $labels = (string) file_get_contents(
            self::ROOT . '/administrator/components/com_breezingformsng/forms/formhistory.xml'
        );

        self::assertStringContainsString('forms/formhistory.xml', $form);
        // Left out of the hash: the same form gives the same version as before.
        $field = "'" . FormContentSummary::FIELD . "'";
        self::assertMatchesRegularExpression("/'ignoreChanges' => \\[[^\\]]*" . $field . '/s', $form);
        self::assertDoesNotMatchRegularExpression("/'hideFields' => \\[[^\\]]*" . $field . '/s', $form);
        self::assertStringContainsString('name="' . FormContentSummary::FIELD . '"', $labels);
    }

    /**
     * @param list<array<string, mixed>> $elements
     *
     * @return array<string, string>
     */
    private function summarize(array $elements): array
    {
        return FormContentSummary::fromTemplateCode(base64_encode((string) json_encode([
            'properties' => ['type' => 'root', 'name' => 'Sample'],
            'children' => [
                ['properties' => ['type' => 'page', 'pageNumber' => 1], 'children' => $elements],
            ],
        ])));
    }
}
