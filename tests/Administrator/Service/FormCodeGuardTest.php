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
}
