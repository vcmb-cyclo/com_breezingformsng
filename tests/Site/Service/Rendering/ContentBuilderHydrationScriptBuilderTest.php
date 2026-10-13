<?php

/**
 * BreezingForms NG - A Joomla Forms Application
 *
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 *
 * SPDX-License-Identifier: GPL-2.0-or-later
 **/

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Site\Service\Rendering;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use stdClass;
use Vcmb\Component\BreezingformsNG\Site\Service\Rendering\ContentBuilderHydrationScriptBuilder;

final class ContentBuilderHydrationScriptBuilderTest extends TestCase
{
    public function testBuildsHydrationForEditableValueFamilies(): void
    {
        $text = new stdClass();
        $text->recElementId = 21;
        $text->recName = 'title';
        $text->recType = 'Text';
        $text->recValue = 'Hello';

        $checkbox = new stdClass();
        $checkbox->recElementId = 22;
        $checkbox->recName = 'terms';
        $checkbox->recType = 'Checkbox';
        $checkbox->recValue = 'yes';

        $result = $this->builder()->buildEditable(
            [$text, $checkbox],
            [],
            false,
            7,
            sys_get_temp_dir()
        );

        self::assertSame('', $result['contentBuilderScript']);
        self::assertStringContainsString('ff_nm_title[]', $result['javascript']);
        self::assertStringContainsString('ff_form7', $result['javascript']);
    }

    public function testDoesNotMutateRecordValuesWhileCleaningThem(): void
    {
        $record = $this->record(23, 'title', 'Text', '  Hello  ');
        $builder = new ContentBuilderHydrationScriptBuilder(
            static fn(string $value): string => trim($value),
            static fn(string $value, int $width, string $break, bool $cut): string =>
                wordwrap($value, $width, $break, $cut)
        );

        $result = $builder->buildEditable([$record], [], false, 7, sys_get_temp_dir());

        self::assertStringContainsString('Hello', $result['javascript']);
        self::assertSame('  Hello  ', $record->recValue);
    }

    public function testSkipsNonEditableRecordsAndNonQuickModeFiles(): void
    {
        $file = new stdClass();
        $file->recElementId = 31;
        $file->recName = 'document';
        $file->recType = 'File Upload';
        $file->recValue = 'document.pdf';

        $hidden = new stdClass();
        $hidden->recElementId = 32;
        $hidden->recName = 'ignored';
        $hidden->recType = 'Text';
        $hidden->recValue = 'ignored';

        $result = $this->builder()->buildEditable(
            [$file, $hidden],
            [32],
            false,
            7,
            sys_get_temp_dir()
        );

        self::assertSame('', $result['contentBuilderScript']);
        self::assertSame('', $result['javascript']);
    }

    public function testBuildsQuickModeFileUploadHydration(): void
    {
        $file = new stdClass();
        $file->recElementId = 31;
        $file->recName = 'document';
        $file->recType = 'File Upload';
        $file->recValue = "document.pdf\nsecond.jpg";

        $result = $this->builder()->buildEditable(
            [$file],
            [],
            true,
            7,
            sys_get_temp_dir()
        );

        self::assertSame(1, substr_count($result['contentBuilderScript'], 'function ff_flashupload_not_empty'));
        self::assertStringContainsString('cbFlashElemCnt["ff_elem31"] = 2;', $result['contentBuilderScript']);
        self::assertStringContainsString(
            'cbFlashElemCnt["ff_elem31"] = 2;' . "\n                                ",
            $result['contentBuilderScript']
        );
        self::assertStringNotContainsString(
            'cbFlashElemCnt["ff_elem31"] = 2;\\n',
            $result['contentBuilderScript']
        );
        self::assertStringContainsString('cb_delete_31[0]', $result['javascript']);
        self::assertStringContainsString('cb_delete_31[1]', $result['javascript']);
        self::assertStringContainsString('bfDeactivateField["ff_nm_document[]"]', $result['javascript']);
    }

    public function testBuildsSignatureHydrationFromAnExistingFile(): void
    {
        $signature = new stdClass();
        $signature->recElementId = 41;
        $signature->recName = 'drawn_signature';
        $signature->recType = 'Signature';
        $signature->recValue = basename(__FILE__);

        $result = $this->builder()->buildEditable(
            [$signature],
            [],
            true,
            7,
            __DIR__
        );

        self::assertStringContainsString('ff_nm_drawn_signature[]', $result['javascript']);
        self::assertStringContainsString('data:image', $result['javascript']);
        self::assertStringContainsString('base64,', $result['javascript']);
    }

    public function testSkipsMissingAndOutOfDirectorySignatures(): void
    {
        $missing = $this->record(42, 'missing_signature', 'Signature', 'missing.png');
        $outside = $this->record(43, 'outside_signature', 'Signature', '../' . basename(__FILE__));

        $result = $this->builder()->buildEditable(
            [$missing, $outside],
            [],
            true,
            7,
            __DIR__
        );

        self::assertSame('', $result['contentBuilderScript']);
        self::assertSame('', $result['javascript']);
    }

    public function testBuildsRadioSelectAndCalendarHydration(): void
    {
        $radio = $this->record(51, 'gender', 'Radio Group', 'female');
        $select = $this->record(52, 'country', 'Select List', 'France');
        $calendar = $this->record(53, 'birthdate', 'Calendar', '2026-08-31');

        $result = $this->builder()->buildEditable(
            [$radio, $select, $calendar],
            [],
            true,
            9,
            sys_get_temp_dir()
        );

        self::assertStringContainsString('type == "radio"', $result['javascript']);
        self::assertStringContainsString('ff_elem52', $result['javascript']);
        self::assertStringContainsString('ff_nm_birthdate[]', $result['javascript']);
    }

    #[DataProvider('simpleValueTypes')]
    public function testBuildsHydrationForEachRemainingSimpleValueType(string $type, string $name): void
    {
        $result = $this->builder()->buildEditable(
            [$this->record(61, $name, $type, 'value')],
            [],
            false,
            9,
            sys_get_temp_dir()
        );

        self::assertStringContainsString('ff_nm_' . $name . '[]', $result['javascript']);
        self::assertStringContainsString('value', $result['javascript']);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function simpleValueTypes(): array
    {
        return [
            'textarea' => ['Textarea', 'description'],
            'number' => ['Number Input', 'quantity'],
            'hidden' => ['Hidden Input', 'token'],
        ];
    }

    public function testIgnoresUnsupportedRecordTypes(): void
    {
        $result = $this->builder()->buildEditable(
            [$this->record(62, 'unknown', 'Unsupported', 'value')],
            [],
            true,
            9,
            sys_get_temp_dir()
        );

        self::assertSame(['contentBuilderScript' => '', 'javascript' => ''], $result);
    }

    private function record(int $elementId, string $name, string $type, string $value): stdClass
    {
        $record = new stdClass();
        $record->recElementId = $elementId;
        $record->recName = $name;
        $record->recType = $type;
        $record->recValue = $value;

        return $record;
    }

    public function testBuildsReadonlyFieldDeactivationScript(): void
    {
        self::assertSame('', $this->builder()->buildReadonly([]));

        $script = $this->builder()->buildReadonly([21, 22]);

        self::assertStringContainsString('function bfContentBuilderFieldHasVisibleControl(fieldId){', $script);
        self::assertStringContainsString('function bfDisableContentBuilderFields(){', $script);
        self::assertStringContainsString('document.getElementById("ff_elem21")', $script);
        self::assertStringContainsString('document.getElementById("ff_elem22")', $script);
        self::assertSame(4, substr_count($script, 'bfDeactivateField[bfCbName]=true;'));
        self::assertStringContainsString('bfCbElements[i].disabled = true;', $script);
        self::assertStringContainsString('bfContentBuilderFieldHasVisibleControl("21")', $script);
        self::assertStringContainsString('bfContentBuilderFieldHasVisibleControl("22")', $script);
    }

    public function testBuildsQuickModeValidationCallback(): void
    {
        $record = $this->record(71, 'document', 'File Upload', 'document.pdf');

        $result = $this->builder()->buildEditable([$record], [], true, 7, sys_get_temp_dir());

        self::assertStringContainsString('function ff_flashupload_not_empty(element, message)', $result['contentBuilderScript']);
        self::assertStringContainsString('cbFlashElemCnt[element.id]', $result['contentBuilderScript']);
        self::assertStringContainsString('ff_validationFocus(element.name);', $result['contentBuilderScript']);
    }

    public function testBuildsFileControlsAndDeactivationForEachStoredFile(): void
    {
        $record = $this->record(72, 'documents', 'File Upload', "first.pdf\nsecond.pdf");

        $result = $this->builder()->buildEditable([$record], [], true, 7, sys_get_temp_dir());

        self::assertSame(4, substr_count($result['javascript'], 'type=\\"checkbox\\"'));
        self::assertStringContainsString('cb_delete_72[0]', $result['javascript']);
        self::assertStringContainsString('cb_delete_72[1]', $result['javascript']);
        self::assertStringContainsString('bfDeactivateField["ff_nm_documents[]"]', $result['javascript']);
    }

    public function testBuildsFileHydrationForTheAvailableQueues(): void
    {
        $record = $this->record(73, 'documents', 'File Upload', 'file.pdf');

        $result = $this->builder()->buildEditable([$record], [], true, 7, sys_get_temp_dir());

        self::assertStringContainsString('bfFlashFileQueue73', $result['javascript']);
        self::assertStringContainsString('#ff_elem73_files', $result['javascript']);
        self::assertStringContainsString('JQuery("#bfFlashFileQueue73").after(mydiv);', $result['javascript']);
    }

    public function testBuildsEscapedSignatureHydration(): void
    {
        $record = $this->record(74, 'signature', 'Signature', basename(__FILE__));

        $result = $this->builder()->buildEditable([$record], [], true, 7, __DIR__);

        self::assertStringContainsString('bf_signaturePad74', $result['javascript']);
        self::assertStringContainsString('data:image', $result['javascript']);
        self::assertStringContainsString('base64,', $result['javascript']);
    }

    public function testBuildsSimpleValueAndCalendarHydration(): void
    {
        $result = $this->builder()->buildEditable(
            [
                $this->record(75, 'title', 'Text', 'A "quoted" value'),
                $this->record(76, 'date', 'Calendar', '2026-08-30'),
            ],
            [],
            false,
            7,
            sys_get_temp_dir()
        );

        self::assertStringContainsString('JQuery("[name=\\"ff_nm_title[]\\"]").val("A \\"quoted\\" value")', $result['javascript']);
        self::assertStringStartsNotWith('setTimeout(function(){', $result['javascript']);
        self::assertStringContainsString('setTimeout(function(){', $result['javascript']);
        self::assertStringContainsString('}, 100);', $result['javascript']);
    }

    public function testBuildsChoiceHydrationForMultipleValuesAndEscapesThem(): void
    {
        $result = $this->builder()->buildEditable(
            [$this->record(77, 'topics', 'Checkbox', 'Cycling, A"B')],
            [],
            false,
            7,
            sys_get_temp_dir()
        );

        self::assertSame(2, substr_count($result['javascript'], 'document.ff_form7.elements.length'));
        self::assertStringContainsString('value == "A\\"B"', $result['javascript']);
        self::assertStringContainsString('.click();', $result['javascript']);
    }

    public function testBuildsSelectHydrationForMultipleValues(): void
    {
        $result = $this->builder()->buildEditable(
            [$this->record(78, 'country', 'Select List', 'one, two')],
            [],
            false,
            7,
            sys_get_temp_dir()
        );

        self::assertSame(2, substr_count($result['javascript'], 'value == "'));
        self::assertStringContainsString('value == "one"', $result['javascript']);
        self::assertStringContainsString('value == "two"', $result['javascript']);
        self::assertStringContainsString('.attr("selected", true).trigger("change")', $result['javascript']);
    }

    private function builder(): ContentBuilderHydrationScriptBuilder
    {
        return new ContentBuilderHydrationScriptBuilder(
            static fn(string $value): string => $value,
            static fn(string $value, int $width, string $break, bool $cut): string =>
                wordwrap($value, $width, $break, $cut)
        );
    }
}
