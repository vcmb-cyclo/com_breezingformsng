<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Any controller inheriting display() can show a view: the screens holding code
 * or submitted data check the right themselves, before reading anything.
 */
final class AdminScreenAccessTest extends TestCase
{
    /** @return iterable<string, array{string, string, string}> */
    public static function screens(): iterable
    {
        $byId = "\\? 'core\\.edit' : 'core\\.create';";

        yield 'form options' => ['Forms', "getInt\\('id', 0\\) > 0 " . $byId, 'getForm\\(\\$id\\)'];
        yield 'form editor' => ['Quickmode', "getInt\\('form', 0\\) > 0 " . $byId, 'getQuickmodeModel\\(\\)'];
        yield 'menu' => ['Menu', "getInt\\('id', 0\\);\\s+\\\$action = \\\$id > 0 " . $byId, 'getItem\\(\\)'];
        yield 'record' => ['Record', '', 'getItem\\(\\)'];
    }

    #[DataProvider('screens')]
    public function testTheRightIsCheckedBeforeTheDataIsRead(string $view, string $action, string $read): void
    {
        $source = file_get_contents(
            __DIR__ . '/../../administrator/components/com_breezingformsng/src/View/' . $view . '/HtmlView.php'
        );
        self::assertIsString($source);

        $right = $action === '' ? "'core\\.edit'" : '\\$action';

        self::assertMatchesRegularExpression(
            '/' . ($action === '' ? '' : $action . '.*?')
            . 'if \\(!\\$this->getCurrentUser\\(\\)->authorise\\(' . $right . ", 'com_breezingformsng'\\)\\) \\{\\s+"
            . 'throw new NotAllowed\\(.*?' . $read . '/s',
            $source
        );
    }
}
