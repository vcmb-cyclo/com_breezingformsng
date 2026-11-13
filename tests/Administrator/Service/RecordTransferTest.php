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
use Vcmb\Component\BreezingformsNG\Administrator\Service\RecordTransfer;

final class RecordTransferTest extends TestCase
{
    public function testSuggestsTheSourceElementWithTheSameNameIgnoringCase(): void
    {
        $source = [
            ['id' => 3, 'name' => 'Email'],
            ['id' => 7, 'name' => 'lastname'],
        ];
        $target = [
            ['id' => 11, 'name' => 'EMAIL'],
            ['id' => 12, 'name' => 'LastName'],
        ];

        self::assertSame([11 => 3, 12 => 7], RecordTransfer::suggestMapping($source, $target));
    }

    public function testFirstSourceElementWinsWhenSeveralShareAName(): void
    {
        $source = [
            ['id' => 5, 'name' => 'phone'],
            ['id' => 9, 'name' => 'PHONE'],
        ];
        $target = [['id' => 20, 'name' => 'Phone']];

        self::assertSame([20 => 5], RecordTransfer::suggestMapping($source, $target));
    }

    public function testSuggestsZeroWhenNoSourceElementHasTheName(): void
    {
        $source = [['id' => 3, 'name' => 'email']];
        $target = [['id' => 11, 'name' => 'city']];

        self::assertSame([11 => 0], RecordTransfer::suggestMapping($source, $target));
    }

    public function testSuggestsAnEmptyMappingWithoutTargetElements(): void
    {
        self::assertSame([], RecordTransfer::suggestMapping([['id' => 3, 'name' => 'email']], []));
        self::assertSame([], RecordTransfer::suggestMapping([], []));
    }

    public function testSuggestsZeroForEveryTargetWithoutSourceElements(): void
    {
        $target = [
            ['id' => 11, 'name' => 'email'],
            ['id' => 12, 'name' => 'city'],
        ];

        self::assertSame([11 => 0, 12 => 0], RecordTransfer::suggestMapping([], $target));
    }

    public function testNormalizeKeepsValidSourceIdsAndDropsUnknownOnes(): void
    {
        $source = [['id' => 3, 'name' => 'a'], ['id' => 7, 'name' => 'b']];
        $target = [['id' => 11, 'name' => 'x'], ['id' => 12, 'name' => 'y'], ['id' => 13, 'name' => 'z']];

        $result = RecordTransfer::normalizeMapping(
            [11 => 7, 12 => 99, 13 => '3'],
            [],
            $source,
            $target
        );

        self::assertSame([11 => 7, 12 => 0, 13 => 3], $result['map']);
    }

    public function testNormalizeKeepsZeroWhenNoSourceIsChosen(): void
    {
        $source = [['id' => 3, 'name' => 'a']];
        $target = [['id' => 11, 'name' => 'x']];

        $result = RecordTransfer::normalizeMapping([11 => 0], [], $source, $target);

        self::assertSame([11 => 0], $result['map']);
    }

    public function testNormalizeKeepsOnlyTheTargetElements(): void
    {
        $source = [['id' => 3, 'name' => 'a']];
        $target = [['id' => 11, 'name' => 'x']];

        $result = RecordTransfer::normalizeMapping(
            [11 => 3, 50 => 3],
            [11 => 'ok', 50 => 'stray'],
            $source,
            $target
        );

        self::assertSame([11 => 3], $result['map']);
        self::assertSame([11 => ''], $result['defaults']);
    }

    public function testNormalizeTurnsNonScalarDefaultsIntoEmptyStrings(): void
    {
        $source = [];
        $target = [
            ['id' => 11, 'name' => 'a'],
            ['id' => 12, 'name' => 'b'],
            ['id' => 13, 'name' => 'c'],
            ['id' => 14, 'name' => 'd'],
        ];

        $result = RecordTransfer::normalizeMapping(
            [],
            [11 => ['nested'], 12 => null, 13 => new \stdClass(), 14 => ['x' => 1]],
            $source,
            $target
        );

        self::assertSame([11 => '', 12 => '', 13 => '', 14 => ''], $result['defaults']);
    }

    public function testNormalizeCastsScalarDefaultsToStrings(): void
    {
        $source = [];
        $target = [
            ['id' => 11, 'name' => 'a'],
            ['id' => 12, 'name' => 'b'],
            ['id' => 13, 'name' => 'c'],
            ['id' => 14, 'name' => 'd'],
            ['id' => 15, 'name' => 'e'],
        ];

        $result = RecordTransfer::normalizeMapping(
            [],
            [11 => 42, 12 => 1.5, 13 => true, 14 => false, 15 => 'text'],
            $source,
            $target
        );

        self::assertSame([11 => '42', 12 => '1.5', 13 => '1', 14 => '', 15 => 'text'], $result['defaults']);
    }

    public function testNormalizeFillsMissingEntriesWithZeroAndEmptyString(): void
    {
        $source = [['id' => 3, 'name' => 'a']];
        $target = [['id' => 11, 'name' => 'x']];

        $result = RecordTransfer::normalizeMapping([], [], $source, $target);

        self::assertSame(['map' => [11 => 0], 'defaults' => [11 => '']], $result);
    }

    public function testNormalizeReturnsEmptyArraysWithoutTargetElements(): void
    {
        $source = [['id' => 3, 'name' => 'a']];

        self::assertSame(
            ['map' => [], 'defaults' => []],
            RecordTransfer::normalizeMapping([3 => 3], ['x' => 'y'], $source, [])
        );
    }

    public function testNormalizeReturnsIntegerIdsForStringInputs(): void
    {
        $source = [['id' => '3', 'name' => 'a']];
        $target = [['id' => '11', 'name' => 'x']];

        $result = RecordTransfer::normalizeMapping(['11' => '3'], ['11' => 'd'], $source, $target);

        self::assertSame([11 => 3], $result['map']);
        self::assertSame([11 => ''], $result['defaults']);
        self::assertIsInt(array_key_first($result['map']));
        self::assertIsInt($result['map'][11]);
    }

    public function testComposeValueGivesTheValueOfTheSourceField(): void
    {
        self::assertSame('Alice', RecordTransfer::composeValue(3, [3 => 'Alice'], 'fallback'));
    }

    public function testComposeValueIgnoresTheDefaultWhenASourceFieldIsChosen(): void
    {
        self::assertSame('', RecordTransfer::composeValue(3, [3 => ''], 'fallback'));
        self::assertSame('', RecordTransfer::composeValue(3, [], 'fallback'));
    }

    public function testComposeValueGivesTheDefaultWhenNoSourceFieldIsChosen(): void
    {
        self::assertSame('fallback', RecordTransfer::composeValue(0, [3 => 'Alice'], 'fallback'));
        self::assertSame('', RecordTransfer::composeValue(0, [3 => 'Alice'], ''));
    }

    public function testComposeValueKeepsZeroAndWhitespaceAsValues(): void
    {
        self::assertSame('0', RecordTransfer::composeValue(3, [3 => '0'], 'fallback'));
        self::assertSame(' ', RecordTransfer::composeValue(3, [3 => ' '], 'fallback'));
    }

    public function testFieldsFromTemplateFindsElementsNestedAtAnyDepth(): void
    {
        $code = self::encodeTemplate([
            'properties' => ['type' => 'page'],
            'children' => [
                [
                    'properties' => ['type' => 'group'],
                    'children' => [
                        [
                            'properties' => ['type' => 'element', 'dbId' => 4, 'required' => '1', 'value' => 'a'],
                            'children' => [
                                ['properties' => ['type' => 'element', 'dbId' => 9, 'required' => false, 'value' => 'b']],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        self::assertSame(
            [
                4 => ['required' => true, 'value' => 'a'],
                9 => ['required' => false, 'value' => 'b'],
            ],
            RecordTransfer::fieldsFromTemplate($code)
        );
    }

    public function testFieldsFromTemplateIgnoresNodesThatAreNotElements(): void
    {
        $code = self::encodeTemplate([
            'properties' => ['type' => 'page'],
            'children' => [
                ['properties' => ['type' => 'text', 'dbId' => 5, 'required' => true, 'value' => 'x']],
                ['properties' => ['dbId' => 6, 'required' => true, 'value' => 'y']],
            ],
        ]);

        self::assertSame([], RecordTransfer::fieldsFromTemplate($code));
    }

    public function testFieldsFromTemplateIgnoresElementsWithoutAPositiveDbId(): void
    {
        $code = self::encodeTemplate([
            'properties' => ['type' => 'page'],
            'children' => [
                ['properties' => ['type' => 'element', 'dbId' => 0, 'value' => 'zero']],
                ['properties' => ['type' => 'element', 'value' => 'missing']],
                ['properties' => ['type' => 'element', 'dbId' => 'abc', 'value' => 'text']],
                ['properties' => ['type' => 'element', 'dbId' => -3, 'value' => 'negative']],
                ['properties' => ['type' => 'element', 'dbId' => 7, 'value' => 'kept']],
            ],
        ]);

        self::assertSame([7 => ['required' => false, 'value' => 'kept']], RecordTransfer::fieldsFromTemplate($code));
    }

    public function testFieldsFromTemplateReadsRequiredAsBoolean(): void
    {
        $flags = [
            1 => '1',
            2 => true,
            3 => 'true',
            4 => false,
            5 => '0',
            6 => 'false',
        ];
        $children = [];

        foreach ($flags as $dbId => $required) {
            $children[] = ['properties' => ['type' => 'element', 'dbId' => $dbId, 'required' => $required, 'value' => '']];
        }

        $children[] = ['properties' => ['type' => 'element', 'dbId' => 7, 'value' => '']];
        $fields = RecordTransfer::fieldsFromTemplate(self::encodeTemplate(['properties' => ['type' => 'page'], 'children' => $children]));

        self::assertSame(
            [1, 2, 3],
            array_keys(array_filter($fields, static fn (array $field): bool => $field['required']))
        );
        self::assertCount(7, $fields);
        self::assertFalse($fields[7]['required']);
    }

    public function testFieldsFromTemplateCastsValuesToStrings(): void
    {
        $code = self::encodeTemplate([
            'properties' => ['type' => 'page'],
            'children' => [
                ['properties' => ['type' => 'element', 'dbId' => 1, 'value' => 42]],
                ['properties' => ['type' => 'element', 'dbId' => 2, 'value' => 1.5]],
                ['properties' => ['type' => 'element', 'dbId' => 3, 'value' => true]],
                ['properties' => ['type' => 'element', 'dbId' => 4, 'value' => false]],
                ['properties' => ['type' => 'element', 'dbId' => 5, 'value' => 'text']],
                ['properties' => ['type' => 'element', 'dbId' => 6, 'value' => null]],
                ['properties' => ['type' => 'element', 'dbId' => 8]],
            ],
        ]);

        $values = array_map(static fn (array $field): string => $field['value'], RecordTransfer::fieldsFromTemplate($code));

        self::assertSame([1 => '42', 2 => '1.5', 3 => '1', 4 => '', 5 => 'text', 6 => '', 8 => ''], $values);
    }

    public function testFieldsFromTemplateReplacesNonScalarValuesWithAnEmptyString(): void
    {
        $code = self::encodeTemplate([
            'properties' => ['type' => 'page'],
            'children' => [
                ['properties' => ['type' => 'element', 'dbId' => 1, 'value' => ['nested']]],
                ['properties' => ['type' => 'element', 'dbId' => 2, 'value' => ['x' => 1]]],
            ],
        ]);

        self::assertSame(
            [
                1 => ['required' => false, 'value' => ''],
                2 => ['required' => false, 'value' => ''],
            ],
            RecordTransfer::fieldsFromTemplate($code)
        );
    }

    public function testFieldsFromTemplateReturnsEmptyArrayForInvalidBase64(): void
    {
        self::assertSame([], RecordTransfer::fieldsFromTemplate('%%% not base64 %%%'));
    }

    public function testFieldsFromTemplateReturnsEmptyArrayForInvalidJson(): void
    {
        self::assertSame([], RecordTransfer::fieldsFromTemplate(base64_encode('{"properties": ')));
    }

    public function testFieldsFromTemplateReturnsEmptyArrayForAnEmptyString(): void
    {
        self::assertSame([], RecordTransfer::fieldsFromTemplate(''));
    }

    public function testFieldsFromTemplateReturnsEmptyArrayForAScalarOrNullJsonDocument(): void
    {
        self::assertSame([], RecordTransfer::fieldsFromTemplate(base64_encode('"element"')));
        self::assertSame([], RecordTransfer::fieldsFromTemplate(base64_encode('42')));
        self::assertSame([], RecordTransfer::fieldsFromTemplate(base64_encode('null')));
    }

    public function testFieldsFromTemplateReturnsEmptyArrayForATreeWithoutElements(): void
    {
        self::assertSame([], RecordTransfer::fieldsFromTemplate(self::encodeTemplate(['properties' => ['type' => 'page']])));
    }

    public function testMissingRequiredReportsUnmappedFieldsWithoutDefault(): void
    {
        self::assertSame(
            [11, 12],
            RecordTransfer::missingRequired([11 => 0, 12 => 0], ['11' => '', 12 => ''], [11, 12])
        );
    }

    public function testMissingRequiredReportsRequiredFieldsAbsentFromTheMap(): void
    {
        self::assertSame([11], RecordTransfer::missingRequired([], [], [11]));
    }

    public function testMissingRequiredIgnoresFieldsMappedToASource(): void
    {
        self::assertSame([], RecordTransfer::missingRequired([11 => 3], [], [11]));
    }

    public function testMissingRequiredIgnoresFieldsWithANonBlankDefault(): void
    {
        self::assertSame([], RecordTransfer::missingRequired([], [11 => 'x', 12 => '0'], [11, 12]));
    }

    public function testMissingRequiredTreatsWhitespaceOnlyDefaultsAsMissing(): void
    {
        self::assertSame(
            [11, 12, 13],
            RecordTransfer::missingRequired([], [11 => '', 12 => '   ', 13 => " \t\n "], [11, 12, 13])
        );
    }

    public function testMissingRequiredKeepsTheInputOrder(): void
    {
        self::assertSame([13, 11, 12], RecordTransfer::missingRequired([], [], [13, 11, 12]));
    }

    public function testMissingRequiredReturnsAnEmptyListWithoutRequiredFields(): void
    {
        self::assertSame([], RecordTransfer::missingRequired([11 => 0], [11 => ''], []));
        self::assertSame([], RecordTransfer::missingRequired([], [], []));
    }

    public function testMissingRequiredMixesMappedDefaultedAndMissingFields(): void
    {
        $missing = RecordTransfer::missingRequired(
            [11 => 3, 12 => 0],
            [13 => ' ', 14 => 'value'],
            [11, 12, 13, 14, 15]
        );

        self::assertSame([12, 13, 15], $missing);
    }

    /**
     * @param array<string, mixed> $tree
     */
    private static function encodeTemplate(array $tree): string
    {
        return base64_encode((string) json_encode($tree));
    }

    public function testNonBreakingSpaceOnlyDefaultCountsAsEmpty(): void
    {
        self::assertSame([5], RecordTransfer::missingRequired([], [5 => "\u{00A0}"], [5]));
    }

    public function testIconsAreThoseOfTheQuickModeTree(): void
    {
        self::assertSame('icon_text-field.png', RecordTransfer::iconFor('Text'));
        self::assertSame('icon_text-field.png', RecordTransfer::iconFor('Number Input'));
        self::assertSame('icon_text-area.png', RecordTransfer::iconFor('Textarea'));
        self::assertSame('icon_radio.png', RecordTransfer::iconFor('Radio Group'));
        self::assertSame('icon_check-box.png', RecordTransfer::iconFor('Checkbox Group'));
        self::assertSame('icon_select.png', RecordTransfer::iconFor('Select List'));
        self::assertSame('icon_captcha.png', RecordTransfer::iconFor('ReCaptcha'));
        self::assertNull(RecordTransfer::iconFor('Static Text/HTML'));
    }

    public function testEveryIconIsAFileOfTheMedia(): void
    {
        foreach (['Text', 'Textarea', 'Radio Group', 'Signature', 'Checkbox', 'Select List', 'File Upload',
            'Regular Button', 'Hidden Input', 'Captcha', 'Calendar', 'PayPal', 'Summarize', 'Stripe'] as $type) {
            $icon = RecordTransfer::iconFor($type);

            self::assertNotNull($icon, $type);
            self::assertFileExists(dirname(__DIR__, 3) . '/media/com_breezingformsng/images/quickmode/' . $icon);
        }
    }

    public function testNormalizeKeepsTheDefaultOnlyOfTargetFieldsWithoutSource(): void
    {
        $source = [['id' => 3, 'name' => 'a']];
        $target = [['id' => 11, 'name' => 'x'], ['id' => 12, 'name' => 'y']];

        $result = RecordTransfer::normalizeMapping([11 => 3, 12 => 0], [11 => 'dropped', 12 => 'kept'], $source, $target);

        self::assertSame([11 => '', 12 => 'kept'], $result['defaults']);
    }
}

