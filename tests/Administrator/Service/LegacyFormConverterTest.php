<?php

/** @package BreezingFormsNG */

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator\Service;

require_once __DIR__ . '/../../Support/joomla-base-database-model-stub.php';

use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Administrator\Model\QuickmodeModel;
use Vcmb\Component\BreezingformsNG\Administrator\Service\LegacyFormConverter;
use Vcmb\Component\BreezingformsNG\Administrator\Service\LegacyFormConversionException;

final class LegacyFormConverterTest extends TestCase
{
    public function testConvertsPagesAndFieldsWithoutReusingDatabaseIds(): void
    {
        $tree = (new LegacyFormConverter())->convert(
            ['pages' => 2, 'title' => 'Contact', 'name' => 'contact', 'emailntf' => 1, 'emailadr' => 'admin@example.test'],
            [
                ['id' => 15, 'page' => 2, 'ordering' => 1, 'published' => 1, 'type' => 'Checkbox', 'name' => 'accept', 'title' => 'Accept', 'flag1' => 1, 'data1' => 'yes'],
                ['id' => 12, 'page' => 1, 'ordering' => 1, 'published' => 1, 'type' => 'Text', 'name' => 'email', 'title' => 'Email', 'data1' => 'initial', 'mailback' => 1],
            ]
        );

        self::assertSame('admin@example.test', $tree['properties']['mailRecipient']);
        self::assertTrue($tree['properties']['mailNotification']);
        $first = $tree['children'][0]['children'][0]['properties'];
        $second = $tree['children'][1]['children'][0]['properties'];
        self::assertSame('bfTextfield', $first['bfType']);
        self::assertSame('initial', $first['value']);
        self::assertTrue($first['mailback']);
        self::assertSame(0, $first['dbId']);
        self::assertSame('bfCheckbox', $second['bfType']);
        self::assertTrue($second['checked']);
        self::assertSame(0, $second['dbId']);
    }

    public function testUnsupportedElementsAreNotSilentlyDropped(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('unsupported_type');
        (new LegacyFormConverter())->convert(['pages' => 1], [
            ['id' => 1, 'page' => 1, 'published' => 1, 'type' => 'Graphic Button', 'name' => 'choice'],
        ]);
    }

    public function testElementScriptsAreRejected(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('unsupported_scripts');
        (new LegacyFormConverter())->convert(['pages' => 1], [
            ['id' => 1, 'page' => 1, 'published' => 1, 'type' => 'Text', 'name' => 'email', 'script3id' => 12],
        ]);
    }

    public function testDuplicateNamesAreRejected(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('invalid_element');
        $element = ['page' => 1, 'published' => 1, 'type' => 'Text', 'name' => 'duplicate'];
        (new LegacyFormConverter())->convert(['pages' => 1], [$element, $element]);
    }

    public function testGeneratedElementsAreAcceptedByQuickmodeAreaBuilder(): void
    {
        $elements = [];
        foreach (['Text', 'Textarea', 'Checkbox', 'Hidden Input'] as $index => $type) {
            $elements[] = ['id' => $index + 10, 'page' => 1, 'published' => 1, 'type' => $type,
                'name' => 'field' . $index, 'title' => $type, 'data1' => 'value'];
        }
        $tree = (new LegacyFormConverter())->convert(['pages' => 1], $elements);
        $model = (new \ReflectionClass(QuickmodeModel::class))->newInstanceWithoutConstructor();
        $areas = new \stdClass();
        $areas->container = [['elements' => [], 'elementCount' => 0]];
        (new \ReflectionMethod($model, 'createAreasFromTree'))->invoke($model, $tree, $areas);

        self::assertSame(['Text', 'Textarea', 'Checkbox', 'Hidden Input'], array_column($areas->container[0]['elements'], 'bfType'));
        self::assertSame([0, 0, 0, 0], array_column($areas->container[0]['elements'], 'dbId'));
    }

    public function testNonzeroScriptConditionIsRejected(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('unsupported_scripts');
        (new LegacyFormConverter())->convert(['pages' => 1], [
            ['page' => 1, 'published' => 1, 'type' => 'Text', 'name' => 'email', 'script3cond' => -1],
        ]);
    }

    public function testSelectOptionsAndMultipleSelectionReachTheQuickmodeAreaBuilder(): void
    {
        $tree = (new LegacyFormConverter())->convert(['pages' => 1], [[
            'id' => 42, 'page' => 1, 'published' => 1, 'type' => 'Select List', 'name' => 'colour',
            'data1' => '1', 'data2' => "1;Red;red\r\n0;Blue;blue", 'flag1' => 1, 'mailback' => 1,
        ]]);
        $properties = $tree['children'][0]['children'][0]['properties'];
        self::assertSame('bfSelect', $properties['bfType']);
        self::assertSame("1;Red;red\n0;Blue;blue", $properties['list']);
        self::assertTrue($properties['multiple']);
        self::assertTrue($properties['mailback']);
        $model = (new \ReflectionClass(QuickmodeModel::class))->newInstanceWithoutConstructor();
        $areas = new \stdClass();
        $areas->container = [['elements' => [], 'elementCount' => 0]];
        (new \ReflectionMethod($model, 'createAreasFromTree'))->invoke($model, $tree, $areas);
        $saved = $areas->container[0]['elements'][0];
        self::assertSame('Select List', $saved['bfType']);
        self::assertSame("1;Red;red\n0;Blue;blue", $saved['data2']);
        self::assertSame(1, $saved['flag1']);
        self::assertSame(1, $saved['mailback']);
        self::assertSame(0, $saved['dbId']);
    }

    public function testAmbiguousSelectOptionReportsTheElement(): void
    {
        try {
            (new LegacyFormConverter())->convert(['pages' => 1], [[
                'id' => 42, 'page' => 1, 'published' => 1, 'type' => 'Select List', 'name' => 'colour',
                'data2' => '1;Select a colour;',
            ]]);
            self::fail('Ambiguous option must be rejected');
        } catch (LegacyFormConversionException $exception) {
            self::assertSame('unsupported_options', $exception->getMessage());
            self::assertSame(42, $exception->elementId);
            self::assertSame('colour', $exception->elementName);
            self::assertSame('Select List', $exception->elementType);
        }
    }

    public function testDisabledLegacyFieldsAreRejected(): void
    {
        $this->expectExceptionMessage('unsupported_options');
        (new LegacyFormConverter())->convert(['pages' => 1], [[
            'id' => 5, 'page' => 1, 'published' => 1, 'type' => 'Text', 'name' => 'disabled', 'flag2' => 1,
        ]]);
    }

    public function testDynamicPhpFieldValuesAreRejected(): void
    {
        $this->expectExceptionMessage('unsupported_options');
        (new LegacyFormConverter())->convert(['pages' => 1], [[
            'id' => 5, 'page' => 1, 'published' => 1, 'type' => 'Text', 'name' => 'dynamic',
            'data1' => '<?php return "value"; ?>',
        ]]);
    }

    public function testClassicRadioButtonsBecomeAQuickmodeGroup(): void
    {
        $elements = [
            ['id' => 1, 'page' => 1, 'ordering' => 1, 'published' => 1, 'name' => 'choice',
                'type' => 'Radio Button', 'title' => 'unused', 'data1' => 'yes', 'data2' => 'Yes', 'flag1' => 1],
            ['id' => 2, 'page' => 1, 'ordering' => 2, 'published' => 1, 'name' => 'choice',
                'type' => 'Radio Button', 'title' => 'unused', 'data1' => 'no', 'data2' => 'No', 'flag1' => 0],
        ];
        $tree = (new LegacyFormConverter())->convert(['pages' => 1], $elements);
        $children = $tree['children'][0]['children'];
        self::assertCount(1, $children);
        self::assertSame('bfRadioGroup', $children[0]['properties']['bfType']);
        self::assertSame("1;Yes;yes\n0;No;no", $children[0]['properties']['group']);
        self::assertTrue($children[0]['properties']['hideLabel']);
        self::assertSame(0, $children[0]['properties']['dbId']);
        $model = (new \ReflectionClass(QuickmodeModel::class))->newInstanceWithoutConstructor();
        $areas = new \stdClass();
        $areas->container = [['elements' => [], 'elementCount' => 0]];
        (new \ReflectionMethod($model, 'createAreasFromTree'))->invoke($model, $tree, $areas);
        self::assertSame('Radio Group', $areas->container[0]['elements'][0]['bfType']);
        self::assertSame("1;Yes;yes\n0;No;no", $areas->container[0]['elements'][0]['data2']);
    }

    public function testAmbiguousRadioGroupsAreRejected(): void
    {
        $first = ['id' => 1, 'page' => 1, 'ordering' => 1, 'published' => 1, 'name' => 'choice',
            'type' => 'Radio Button', 'data1' => 'yes', 'data2' => 'Yes', 'flag1' => 1];
        $second = ['id' => 2, 'page' => 1, 'ordering' => 2, 'published' => 1, 'name' => 'choice',
            'type' => 'Radio Button', 'data1' => 'no', 'data2' => 'No', 'flag1' => 0];
        $cases = [
            [['pages' => 1, 'template_code_processed' => '<div>Easy</div>'], [$first, $second]],
            [['pages' => 1], [$first, array_replace($second, ['data2' => ''])]],
            [['pages' => 1], [$first, array_replace($second, ['data2' => '<b>No</b>'])]],
            [['pages' => 1], [$first, array_replace($second, ['data1' => 'a;b'])]],
            [['pages' => 1], [$first, array_replace($second, ['data1' => 'yes'])]],
            [['pages' => 1], [$first, array_replace($second, ['flag1' => 1])]],
            [['pages' => 1], [$first, array_replace($second, ['flag2' => 1])]],
            [['pages' => 1], [$first, array_replace($second, ['script2id' => 12])]],
            [['pages' => 1], [$first, array_replace($second, ['logging' => 0])]],
            [['pages' => 1], [array_replace($first, ['type' => '__classic_radio_group'])]],
            [['pages' => 1], [array_replace($first, ['type' => 'Radio Group'])]],
            [['pages' => 2], [$first, array_replace($second, ['page' => 2])]],
            [['pages' => 1], [$first, array_replace($second, ['name' => 'Choice'])]],
            [['pages' => 1], [$first, ['page' => 1, 'ordering' => 2, 'published' => 1, 'type' => 'Text',
                'name' => 'other'], array_replace($second, ['ordering' => 3])]],
        ];
        foreach ($cases as [$form, $elements]) {
            try {
                (new LegacyFormConverter())->convert($form, $elements);
                self::fail('Ambiguous group must be rejected');
            } catch (LegacyFormConversionException) {
                self::assertTrue(true);
            }
        }
    }
}
