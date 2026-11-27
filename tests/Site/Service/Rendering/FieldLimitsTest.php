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
use Vcmb\Component\BreezingformsNG\Site\Service\Rendering\Debug\FieldEdit;
use Vcmb\Component\BreezingformsNG\Site\Service\Rendering\Debug\FieldLimits;
use Vcmb\Component\BreezingformsNG\Site\Service\Rendering\Debug\FieldOrder;

final class FieldLimitsTest extends TestCase
{
    private const STARS = ['bfType' => 'bfNumberInput', 'widget' => 'rating', 'ratingStyle' => 'stars'];

    private const SCALE = ['bfType' => 'bfNumberInput', 'widget' => 'rating', 'ratingStyle' => 'scale'];

    private const DATE = ['bfType' => 'bfTextfield', 'widget' => 'datetime', 'widgetMode' => 'date'];

    /** @return array<string, array{array<string, string>, list<string>}> */
    public static function types(): array
    {
        return [
            'text' => [['bfType' => 'bfTextfield'], ['maxLength']],
            'email' => [['bfType' => 'bfTextfield', 'widget' => 'email'], ['maxLength']],
            'textarea' => [['bfType' => 'bfTextarea'], ['maxlength']],
            'number or slider' => [['bfType' => 'bfNumberInput'], ['min', 'max']],
            'stars' => [self::STARS, ['max']],
            'scale' => [self::SCALE, ['min', 'max']],
            'date' => [self::DATE, ['widgetMin', 'widgetMax']],
            'phone' => [['bfType' => 'bfTextfield', 'widget' => 'phone'], []],
            'choice' => [['bfType' => 'bfSelect'], []],
        ];
    }

    /**
     * @param array<string, string> $properties
     * @param list<string>          $expected
     */
    #[DataProvider('types')]
    public function testEachTypeHasItsOwnLimits(array $properties, array $expected): void
    {
        self::assertSame($expected, array_column(FieldLimits::of((object) $properties), 'key'));
    }

    /** @return array<string, array{array<string, string>, array<string, string>, bool}> */
    public static function values(): array
    {
        $number = ['bfType' => 'bfNumberInput'];
        $text = ['bfType' => 'bfTextfield'];

        return [
            'length' => [$text, ['maxLength' => '40'], true],
            'no length' => [['maxLength' => '5'] + $text, ['maxLength' => ''], true],
            'negative length' => [$text, ['maxLength' => '-1'], false],
            'not a length' => [$text, ['maxLength' => '4 or 5'], false],
            'limit of another type' => [$text, ['max' => '4'], false],
            'bounds' => [$number, ['min' => '-2.5', 'max' => '10'], true],
            'one bound' => [$number, ['min' => '3'], true],
            'bounds crossed' => [$number, ['min' => '5', 'max' => '4'], false],
            'bound crossing the stored one' => [['max' => '4'] + $number, ['min' => '5'], false],
            'not a number' => [$number, ['max' => '1e3'], false],
            'stars' => [self::STARS, ['max' => '7'], true],
            'too many stars' => [self::STARS, ['max' => '11'], false],
            'scale' => [self::SCALE, ['min' => '1', 'max' => '21'], true],
            'long scale' => [self::SCALE, ['min' => '1', 'max' => '22'], false],
            'dates' => [self::DATE, ['widgetMin' => 'now', 'widgetMax' => '2027-01-31'], true],
            'dates crossed' => [self::DATE, ['widgetMin' => '2027-02-01', 'widgetMax' => '2027-01-31'], false],
            'time in a date' => [self::DATE, ['widgetMin' => '10:00'], false],
        ];
    }

    /**
     * @param array<string, string> $properties
     * @param array<string, string> $changes
     */
    #[DataProvider('values')]
    public function testALimitIsCheckedBeforeBeingWritten(array $properties, array $changes, bool $accepted): void
    {
        $field = (object) $properties;
        $result = FieldLimits::apply($field, $changes);

        if (!$accepted) {
            self::assertNull($result);
            self::assertEquals((object) $properties, $field);

            return;
        }

        self::assertSame(array_keys($changes), $result);

        foreach ($changes as $key => $value) {
            self::assertSame($value, $field->{$key});
        }
    }

    public function testARefusedLimitRefusesTheTextsToo(): void
    {
        $field = (object) ['bfType' => 'bfTextfield', 'label' => 'Name'];
        $changes = ['label' => 'Other', 'hint' => '', 'required' => null, 'limits' => ['maxLength' => 'x']];

        self::assertNull(FieldEdit::apply($field, $changes, FieldEdit::DEFAULT_LANGUAGE, 0, ''));
        self::assertSame('Name', $field->label);
    }

    public function testTheElementListTakesTheNewLimits(): void
    {
        $json = '[{"elements":[{"name":"A","options":{"maxLength":""}},'
            . '{"name":"B","options":{"widget":"rating","widgetOptions":{}}}]}]';
        $text = (object) ['maxLength' => '9'];
        $stars = (object) (self::STARS + ['max' => '7']);

        self::assertStringContainsString('"maxLength":"9"', FieldOrder::editArea($json, 'A', $text, ['maxLength']));
        self::assertStringContainsString(
            '"widgetOptions":{"style":"stars","min":1,"max":7,',
            FieldOrder::editArea($json, 'B', $stars, ['max'])
        );
    }
}
