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

namespace Vcmb\Component\BreezingformsNG\Tests\Site\Service\Widget;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Site\Service\Widget\CountryCodes;
use Vcmb\Component\BreezingformsNG\Site\Service\Widget\WidgetValueValidator;

final class WidgetValueValidatorTest extends TestCase
{
    private const PHONE = 'COM_BREEZINGFORMSNG_WIDGET_INVALID_PHONE';
    private const DATETIME = 'COM_BREEZINGFORMSNG_WIDGET_INVALID_DATETIME';
    private const COUNTRY = 'COM_BREEZINGFORMSNG_WIDGET_INVALID_COUNTRY';

    /** @param array<string, mixed> $options */
    private function validate(string $widget, string $value, array $options = [], ?string $now = null): ?string
    {
        return (new WidgetValueValidator())->validate(
            $widget,
            $options,
            $value,
            $now === null ? null : new DateTimeImmutable($now, new DateTimeZone('UTC'))
        );
    }

    /** @return iterable<string, array{string, string, array<string, mixed>}> */
    public static function emptyValues(): iterable
    {
        foreach (['phone', 'datetime', 'country', 'address', 'unknown'] as $widget) {
            yield $widget => [$widget, '', []];
        }
    }

    /** @param array<string, mixed> $options */
    #[DataProvider('emptyValues')]
    public function testAnEmptyValueIsValid(string $widget, string $value, array $options): void
    {
        self::assertNull($this->validate($widget, $value, $options));
    }

    /** @return iterable<string, array{string}> */
    public static function validPhones(): iterable
    {
        yield 'france' => ['+33612345678'];
        yield 'shortest, 8 digits' => ['+12345678'];
        yield 'longest, 15 digits' => ['+123456789012345'];
    }

    #[DataProvider('validPhones')]
    public function testValidE164PhonesAreAccepted(string $phone): void
    {
        self::assertNull($this->validate('phone', $phone));
    }

    /** @return iterable<string, array{string}> */
    public static function invalidPhones(): iterable
    {
        yield 'plus zero' => ['+0612345678'];
        yield 'no plus' => ['33612345678'];
        yield 'too short' => ['+1234567'];
        yield 'too long' => ['+1234567890123456'];
        yield 'spaces' => ['+33 6 12 34 56 78'];
        yield 'leading space' => [' +33612345678'];
        yield 'letters' => ['+3361234abcd'];
        yield 'double plus' => ['++33612345678'];
        yield 'trailing newline' => ["+33612345678\n"];
        yield 'plain national' => ['0612345678'];
    }

    #[DataProvider('invalidPhones')]
    public function testMalformedPhonesAreRefused(string $phone): void
    {
        self::assertSame(self::PHONE, $this->validate('phone', $phone));
    }

    /** @return iterable<string, array{string, string}> */
    public static function validDatetimes(): iterable
    {
        yield 'datetime' => ['datetime', '2026-11-18T14:30'];
        yield 'datetime leap day' => ['datetime', '2028-02-29T00:00'];
        yield 'date' => ['date', '2026-11-18'];
        yield 'time' => ['time', '14:30'];
        yield 'time midnight' => ['time', '00:00'];
        yield 'time last minute' => ['time', '23:59'];
    }

    #[DataProvider('validDatetimes')]
    public function testValidShapesAreAccepted(string $mode, string $value): void
    {
        self::assertNull($this->validate('datetime', $value, ['mode' => $mode]));
    }

    public function testAPhoneIsNotCheckedWhenTheWidgetDoesNotValidate(): void
    {
        self::assertNull($this->validate('phone', '06 12 34 56 78', ['validate' => false]));
        self::assertNull($this->validate('phone', '06 12 34 56 78', ['validate' => '0']));
        self::assertSame(self::PHONE, $this->validate('phone', '06 12 34 56 78', ['validate' => true]));
        self::assertSame(self::PHONE, $this->validate('phone', '06 12 34 56 78'));
    }

    public function testTheModeDefaultsToDatetime(): void
    {
        self::assertNull($this->validate('datetime', '2026-11-18T14:30'));
        self::assertSame(self::DATETIME, $this->validate('datetime', '2026-11-18'));
    }

    /** @return iterable<string, array{string, string}> */
    public static function invalidDatetimes(): iterable
    {
        yield 'impossible day' => ['datetime', '2026-02-30T10:00'];
        yield 'impossible date mode' => ['date', '2026-02-30'];
        yield 'not a leap year' => ['date', '2026-02-29'];
        yield 'month 13' => ['date', '2026-13-01'];
        yield 'hour 24' => ['datetime', '2026-11-18T24:00'];
        yield 'minute 60' => ['time', '12:60'];
        yield 'time 24:00' => ['time', '24:00'];
        yield 'date in datetime mode' => ['datetime', '2026-11-18'];
        yield 'datetime in date mode' => ['date', '2026-11-18T14:30'];
        yield 'seconds' => ['datetime', '2026-11-18T14:30:15'];
        yield 'space separator' => ['datetime', '2026-11-18 14:30'];
        yield 'garbage' => ['date', 'tomorrow'];
        yield 'trailing newline' => ['date', "2026-11-18\n"];
    }

    #[DataProvider('invalidDatetimes')]
    public function testInvalidDatetimesAreRefused(string $mode, string $value): void
    {
        self::assertSame(self::DATETIME, $this->validate('datetime', $value, ['mode' => $mode]));
    }

    public function testMinAndMaxAreRespected(): void
    {
        $options = ['mode' => 'datetime', 'min' => '2026-11-18T10:00', 'max' => '2026-11-18T18:00'];

        self::assertNull($this->validate('datetime', '2026-11-18T10:00', $options));
        self::assertNull($this->validate('datetime', '2026-11-18T18:00', $options));
        self::assertSame(self::DATETIME, $this->validate('datetime', '2026-11-18T09:59', $options));
        self::assertSame(self::DATETIME, $this->validate('datetime', '2026-11-18T18:01', $options));
    }

    public function testMinAndMaxOfTheWrongShapeAreIgnored(): void
    {
        self::assertNull($this->validate('datetime', '2026-11-18', ['mode' => 'date', 'min' => '10:00', 'max' => 'junk']));
    }

    public function testTimeMinAndMax(): void
    {
        $options = ['mode' => 'time', 'min' => '09:00', 'max' => '17:30'];

        self::assertNull($this->validate('datetime', '09:00', $options));
        self::assertNull($this->validate('datetime', '17:30', $options));
        self::assertSame(self::DATETIME, $this->validate('datetime', '08:59', $options));
        self::assertSame(self::DATETIME, $this->validate('datetime', '17:31', $options));
    }

    public function testNowIsTheServerTimeWidenedByTheLargestTimeZoneOffset(): void
    {
        $now = '2026-11-18 12:00:00';

        // Minimum "now": two days ago is refused, yesterday tolerated, today and later accepted (14 h tolerance).
        $min = ['mode' => 'date', 'min' => 'now'];
        self::assertSame(self::DATETIME, $this->validate('datetime', '2026-11-16', $min, $now));
        self::assertNull($this->validate('datetime', '2026-11-17', $min, $now));
        self::assertNull($this->validate('datetime', '2026-11-18', $min, $now));
        self::assertNull($this->validate('datetime', '2027-01-01', $min, $now));

        $minDatetime = ['mode' => 'datetime', 'min' => 'now'];
        self::assertNull($this->validate('datetime', '2026-11-17T22:00', $minDatetime, $now));
        self::assertSame(self::DATETIME, $this->validate('datetime', '2026-11-17T21:59', $minDatetime, $now));

        $max = ['mode' => 'datetime', 'max' => 'now'];
        self::assertNull($this->validate('datetime', '2026-11-19T02:00', $max, $now));
        self::assertSame(self::DATETIME, $this->validate('datetime', '2026-11-19T02:01', $max, $now));
    }

    public function testNowIsNotEnforcedForATimeOfDay(): void
    {
        self::assertNull($this->validate('datetime', '03:00', ['mode' => 'time', 'min' => 'now'], '2026-11-18 12:00:00'));
    }

    public function testCountryCodes(): void
    {
        self::assertNull($this->validate('country', 'FR'));
        self::assertNull($this->validate('country', 'US'));
        self::assertSame(self::COUNTRY, $this->validate('country', 'fr'));
        self::assertSame(self::COUNTRY, $this->validate('country', 'XX'));
        self::assertSame(self::COUNTRY, $this->validate('country', 'FRA'));
        self::assertSame(self::COUNTRY, $this->validate('country', 'F'));
        self::assertSame(self::COUNTRY, $this->validate('country', 'FR '));
    }

    public function testACountryListRestrictsTheChoice(): void
    {
        $options = ['countries' => ['fr', 'BE']];

        self::assertNull($this->validate('country', 'FR', $options));
        self::assertNull($this->validate('country', 'BE', $options));
        self::assertSame(self::COUNTRY, $this->validate('country', 'DE', $options));
        self::assertNull($this->validate('country', 'DE', ['countries' => []]));
    }

    public function testAddressAndUnknownWidgetsAcceptAnyText(): void
    {
        self::assertNull($this->validate('address', '12 rue de la Paix, <Paris>'));
        self::assertNull($this->validate('unknown', 'anything'));
    }

    public function testTheCountryListHolds249UniqueUpperCaseCodes(): void
    {
        self::assertCount(249, CountryCodes::ALL);
        self::assertCount(249, array_unique(CountryCodes::ALL));
        foreach (CountryCodes::ALL as $code) {
            self::assertMatchesRegularExpression('/^[A-Z]{2}$/', $code);
        }
        self::assertTrue(CountryCodes::isValid('fr'));
        self::assertFalse(CountryCodes::isValid('xx'));
    }

    public function testValidateAreasFindsTheWidgetElementByName(): void
    {
        $areas = [[
            'elements' => [
                ['name' => 'plain', 'options' => []],
                ['name' => 'tel', 'options' => ['widget' => 'phone', 'widgetOptions' => []]],
                ['name' => 'when', 'options' => ['widget' => 'datetime', 'widgetOptions' => ['mode' => 'date']]],
            ],
        ]];
        $validator = new WidgetValueValidator();

        self::assertNull($validator->validateAreas($areas, 'plain', ['not a phone']));
        self::assertNull($validator->validateAreas($areas, 'missing', ['x']));
        self::assertNull($validator->validateAreas($areas, 'tel', ['+33612345678']));
        self::assertSame(self::PHONE, $validator->validateAreas($areas, 'tel', ['+33612345678', '0612']));
        self::assertSame(self::DATETIME, $validator->validateAreas($areas, 'when', ['2026-02-30']));
        self::assertNull($validator->validateAreas($areas, 'when', ['']));
        self::assertNull($validator->validateAreas([], 'tel', ['0612']));
        self::assertNull($validator->validateAreas(['garbage'], 'tel', ['0612']));
    }
}
