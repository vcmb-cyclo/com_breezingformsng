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

namespace Vcmb\Component\BreezingformsNG\Tests\Site\Service\Submission;

use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Site\Service\Submission\SofortOnlyFormDetector;

final class SofortOnlyFormDetectorTest extends TestCase
{
    public function testDetectsAFormWhoseOnlyPaymentIsSofort(): void
    {
        self::assertTrue((new SofortOnlyFormDetector())->isSofortOnly($this->areas(['bfTextfield', 'bfSofortueberweisung'])));
    }

    public function testAcceptsSofortNextToAnotherPayment(): void
    {
        $detector = new SofortOnlyFormDetector();

        self::assertFalse($detector->isSofortOnly($this->areas(['bfSofortueberweisung', 'bfStripe'])));
        self::assertFalse($detector->isSofortOnly($this->areas(['bfPayPal'], ['bfSofortueberweisung'])));
    }

    public function testIgnoresFormsWithoutSofort(): void
    {
        $detector = new SofortOnlyFormDetector();

        self::assertFalse($detector->isSofortOnly($this->areas(['bfTextfield', 'bfSubmitButton'])));
        self::assertFalse($detector->isSofortOnly(''));
        self::assertFalse($detector->isSofortOnly('{"broken"'));
        self::assertFalse($detector->isSofortOnly('[{"elements":null},"x"]'));
    }

    public function testTellsWhetherAFormKeepsAPayment(): void
    {
        $detector = new SofortOnlyFormDetector();

        self::assertTrue($detector->hasPayment($this->areas(['bfTextfield'], ['bfStripe'])));
        self::assertTrue($detector->hasPayment($this->areas(['bfPayPal'])));
        self::assertFalse($detector->hasPayment($this->areas(['bfTextfield', 'bfSofortueberweisung'])));
        self::assertFalse($detector->hasPayment(''));
    }

    /**
     * @param list<string> ...$areas internal element types, one list per area
     */
    private function areas(array ...$areas): string
    {
        return (string) json_encode(array_map(
            static fn (array $types): array => [
                'elements' => array_map(static fn (string $type): array => ['internalType' => $type], $types),
            ],
            $areas
        ));
    }
}
