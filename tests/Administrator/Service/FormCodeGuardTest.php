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
use Vcmb\Component\BreezingformsNG\Administrator\Service\FormCodeGuard;

if (!defined('_JEXEC')) {
    define('_JEXEC', 1);
}

final class FormCodeGuardTest extends TestCase
{
    private function stored(): object
    {
        return (object) [
            'piece1code' => "echo 'a';\n",
            'piece2code' => '',
            'piece3code' => '',
            'piece4code' => '',
            'script1code' => 'x();',
            'script2code' => '',
        ];
    }

    public function testAUserWithTheCodePermissionKeepsEverything(): void
    {
        $data   = ['title' => 'T', 'piece1code' => 'system("id");'];
        $result = (new FormCodeGuard())->apply($data, $this->stored(), true);

        self::assertSame($data, $result['data']);
        self::assertSame([], $result['ignored']);
    }

    public function testChangedCodeIsDroppedWithoutThePermission(): void
    {
        $data   = ['title' => 'T', 'piece1code' => 'system("id");', 'script2code' => 'evil();'];
        $result = (new FormCodeGuard())->apply($data, $this->stored(), false);

        self::assertSame(['title' => 'T'], $result['data']);
        self::assertSame(['script2code', 'piece1code'], $result['ignored']);
    }

    public function testUnchangedCodeIsNotReportedAsIgnored(): void
    {
        $data   = ['title' => 'T', 'piece1code' => "echo 'a';\r\n", 'script1code' => 'x();'];
        $result = (new FormCodeGuard())->apply($data, $this->stored(), false);

        self::assertSame(['title' => 'T'], $result['data']);
        self::assertSame([], $result['ignored']);
    }

    public function testAFieldTheScreenDidNotPostIsNoChange(): void
    {
        // The controller sets every code field it did not receive to null.
        $data   = ['title' => 'T', 'piece1code' => null, 'script1code' => null, 'piece2code' => 'evil();'];
        $result = (new FormCodeGuard())->apply($data, $this->stored(), false);

        self::assertSame(['piece2code'], $result['ignored']);
        self::assertSame(['title' => 'T'], $result['data']);
    }

    public function testANewFormWithoutThePermissionStartsWithoutCode(): void
    {
        $result = (new FormCodeGuard())->apply(['title' => 'T', 'piece3code' => 'evil();'], null, false);

        self::assertSame('', $result['data']['piece3code']);
        self::assertSame('', $result['data']['script1code']);
        self::assertSame(['piece3code'], $result['ignored']);
        foreach (FormCodeGuard::CODE_COLUMNS as $column) {
            self::assertSame('', $result['data'][$column]);
        }
    }

    public function testEveryColumnThatHoldsRunnableCodeIsGuarded(): void
    {
        self::assertSame(
            ['script1code', 'script2code', 'piece1code', 'piece2code', 'piece3code', 'piece4code'],
            FormCodeGuard::CODE_COLUMNS
        );
    }

    public function testWhichScriptOrPieceRunsIsNotGuarded(): void
    {
        $data   = ['title' => 'T', 'piece1id' => '3', 'script1cond' => '2', 'piece2id' => '8'];
        $result = (new FormCodeGuard())->apply($data, $this->stored(), false);

        self::assertSame($data, $result['data']);
        self::assertSame([], $result['ignored']);
    }

    public function testAnElementKeepsItsStoredCodeWithoutThePermission(): void
    {
        $stored = ['script1code' => "a();\n", 'script3id' => '9'];
        $row = ['name' => 'f', 'script1code' => "a();\r\n", 'script3id' => 4, 'script2code' => 'evil();', 'script3code' => null,
            'script3msg' => 'Message'];
        $result = (new FormCodeGuard())->applyToElement($row, $stored, false);

        self::assertSame(['script2code'], $result['ignored']);
        self::assertSame("a();\n", $result['data']['script1code']);
        self::assertSame('', $result['data']['script2code']);
        self::assertSame('', $result['data']['script3code']);
        // The choice of a library script and the validation message are not code.
        self::assertSame(4, $result['data']['script3id']);
        self::assertSame('Message', $result['data']['script3msg']);
    }

    public function testANewElementStartsWithoutCodeAndAUserWithThePermissionIsNotTouched(): void
    {
        $row = ['script1code' => 'x();', 'script3id' => 4];
        $result = (new FormCodeGuard())->applyToElement($row, null, false);

        self::assertSame(['script1code'], $result['ignored']);
        self::assertSame(4, $result['data']['script3id']);
        self::assertSame($row, (new FormCodeGuard())->applyToElement($row, null, true)['data']);
    }

    public function testEveryElementColumnThatHoldsCodeIsGuarded(): void
    {
        self::assertSame(['script1code', 'script2code', 'script3code'], FormCodeGuard::ELEMENT_CODE_COLUMNS);
    }

    public function testThePaymentFileOfAnImportedFormComesFromTheElementOfTheSameName(): void
    {
        $areas = (string) json_encode([['elements' => [
            ['name' => 'pay', 'options' => ['filepath' => '/etc/passwd']],
            ['name' => 'new', 'options' => ['filepath' => '/x']],
            ['name' => 'text', 'options' => ['placeholder' => 'p']],
        ]]]);
        $kept = json_decode((new FormCodeGuard())->applyToAreas($areas, ['pay' => '/srv/a.pdf']), true);

        self::assertSame('/srv/a.pdf', $kept[0]['elements'][0]['options']['filepath']);
        self::assertSame('', $kept[0]['elements'][1]['options']['filepath']);
        self::assertSame(['placeholder' => 'p'], $kept[0]['elements'][2]['options']);
        self::assertSame('not json', (new FormCodeGuard())->applyToAreas('not json', []));
    }
}
