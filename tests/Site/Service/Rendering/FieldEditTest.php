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

namespace Vcmb\Component\BreezingformsNG\Tests\Site\Service\Rendering;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;
use Vcmb\Component\BreezingformsNG\Site\Service\Rendering\Debug\FieldEdit;
use Vcmb\Component\BreezingformsNG\Site\Service\Rendering\Debug\FieldOrder;

final class FieldEditTest extends TestCase
{
    public function testTextsOfTheDefaultLanguageAreWrittenAsTheyAre(): void
    {
        $field = $this->field();
        $changes = ['label' => ' <b>Name</b> ', 'hint' => 'Help', 'required' => null];

        self::assertSame(
            ['label', 'hint'],
            FieldEdit::apply($field, $changes, FieldEdit::DEFAULT_LANGUAGE, 0, 'Fill it')
        );
        self::assertSame('<b>Name</b>', $field->label);
        self::assertSame('Help', $field->hint);
        self::assertSame([], FieldEdit::apply($field, $changes, FieldEdit::DEFAULT_LANGUAGE, 0, 'Fill it'));
    }

    public function testAnotherLanguageWritesItsTranslationsOnly(): void
    {
        $field = $this->field();
        $changes = ['label' => 'Nom', 'hint' => '', 'required' => null];

        self::assertSame(['label_translationfr-FR'], FieldEdit::apply($field, $changes, 'fr-FR', 0, ''));
        self::assertSame('Name', $field->label);
        self::assertSame('Nom', $field->{'label_translationfr-FR'});

        $tree = (object) ['children' => [(object) ['properties' => $field]]];
        $values = FieldEdit::values($tree, 'fr-FR')['Name'];

        self::assertSame(['Nom', 'Name'], [$values['label'], $values['labelDefault']]);
        // An empty translation falls back to the main text.
        self::assertSame(
            ['label_translationfr-FR'],
            FieldEdit::apply($field, ['label' => '', 'hint' => '', 'required' => null], 'fr-FR', 0, '')
        );
    }

    public function testRequiredSetsAndRemovesTheLibraryValidation(): void
    {
        $field = $this->field();
        $on = ['label' => 'Name', 'hint' => '', 'required' => true];

        self::assertNotNull(FieldEdit::apply($field, $on, FieldEdit::DEFAULT_LANGUAGE, 5, 'Fill it'));
        self::assertSame(
            [true, 5, 'ff_valuenotempty', 1, 'Fill it'],
            [
                $field->required, $field->validationId, $field->validationFunctionName,
                $field->validationCondition, $field->validationMessage,
            ]
        );

        $off = ['required' => false] + $on;
        self::assertNotNull(FieldEdit::apply($field, $off, FieldEdit::DEFAULT_LANGUAGE, 0, ''));
        self::assertSame(
            [false, 0, '', 0, ''],
            [
                $field->required, $field->validationId, $field->validationFunctionName,
                $field->validationCondition, $field->validationMessage,
            ]
        );
    }

    /** @return array<string, array{array<string, mixed>, array{label: string, hint: string, required: bool|null}}> */
    public static function refusedChanges(): array
    {
        $keep = ['label' => 'Name', 'hint' => '', 'required' => null];
        $require = ['required' => true] + $keep;

        return [
            'empty main label' => [[], ['label' => ' '] + $keep],
            'long label' => [[], ['label' => str_repeat('a', 256)] + $keep],
            'long help' => [[], ['hint' => str_repeat('a', 10001)] + $keep],
            'another validation' => [
                ['validationId' => 3, 'validationFunctionName' => 'ff_validemail', 'validationCondition' => 1],
                $require,
            ],
            'own validation script' => [['validationCondition' => 2, 'validationCode' => 'x'], $require],
            'another type' => [['bfType' => 'bfSelect'], $require],
            'no library script' => [[], $require],
        ];
    }

    /**
     * @param array<string, mixed>                                    $properties
     * @param array{label: string, hint: string, required: bool|null} $changes
     */
    #[DataProvider('refusedChanges')]
    public function testARefusedChangeLeavesTheField(array $properties, array $changes): void
    {
        $field = $this->field($properties);
        $before = clone $field;

        $check = ($properties === [] && $changes['required'] === true) ? 0 : 5;
        $result = FieldEdit::apply($field, $changes, FieldEdit::DEFAULT_LANGUAGE, $check, '');

        self::assertNull($result);

        // The texts are equal to the stored ones, or refused before being written.
        self::assertEquals($before, $field);
    }

    public function testTheElementListTakesTheNewTextsAndValidation(): void
    {
        $json = '[{"elements":[{"name":"Other","title":"O"},{"name":"Name","title":"Old","options":{"hint":""}}]}]';
        $field = $this->field(['label' => 'New', 'hint' => 'Help', 'validationFunctionName' => 'f']);

        self::assertSame(
            '[{"elements":[{"name":"Other","title":"O"},{"name":"Name","title":"New","options":{"hint":"Help"},'
            . '"functionNameScript3":"f"}]}]',
            FieldOrder::editArea($json, 'Name', $field, ['label', 'hint', 'validationFunctionName'])
        );
        // A translation is not in the element list.
        self::assertSame($json, FieldOrder::editArea($json, 'Name', $field, ['label_translationfr-FR']));
    }

    /** @param array<string, mixed> $properties */
    public function testPostedEditsAreReadByFieldName(): void
    {
        self::assertSame(
            ['A' => ['label' => 'a', 'hint' => '', 'required' => true, 'limits' => ['maxLength' => '4']],
                'B' => ['label' => 'b', 'hint' => 'h', 'required' => null, 'limits' => []]],
            FieldEdit::changes('[{"field":"A","label":"a","hint":"","required":"1","limits":{"maxLength":"4"}},'
                . '{"field":"B","label":"b","hint":"h","required":""}]')
        );
        self::assertSame([], FieldEdit::changes('[]'));
    }

    public function testUnreadableEditsAreRefused(): void
    {
        $edit = '{"field":"A","label":"a","hint":"","required":""}';

        foreach (
            [
                '', '1', '{"a":1}', '[1]', '[{"field":"A"}]', '[' . $edit . ',' . $edit . ']',
                '[{"field":"A","label":["a"],"hint":"","required":""}]',
                '[{"field":"A","label":"a","hint":"","required":true}]',
                '[{"field":"A","label":"a","hint":"","required":"","limits":{"maxLength":4}}]',
            ] as $json
        ) {
            self::assertNull(FieldEdit::changes($json), $json);
        }
    }

    private function field(array $properties = []): stdClass
    {
        return (object) ($properties + [
            'type' => 'element', 'bfType' => 'bfTextfield', 'bfName' => 'Name', 'label' => 'Name', 'hint' => '',
            'required' => false, 'validationId' => 0, 'validationCode' => '', 'validationFunctionName' => '',
            'validationCondition' => 0, 'validationMessage' => '',
        ]);
    }
}
