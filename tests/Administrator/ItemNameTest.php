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

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator;

use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Administrator\Service\ItemName;

final class ItemNameTest extends TestCase
{
    public function testAnIdentifierIsAccepted(): void
    {
        foreach (['contact', 'Quote_2', '_x', '9'] as $name) {
            self::assertNull(ItemName::problem($name), $name);
        }
    }

    public function testAnEmptyNameIsRefused(): void
    {
        foreach (['', '   '] as $name) {
            self::assertSame('COM_BREEZINGFORMSNG_ERROR_ENTER_NAME', ItemName::problem($name));
        }
    }

    public function testOtherCharactersAreRefused(): void
    {
        foreach (['my form', 'a-b', 'é', "a\n", ' a', 'a.b'] as $name) {
            self::assertSame('COM_BREEZINGFORMSNG_ERROR_ENTER_NAME_CHARACTERS', ItemName::problem($name), $name);
        }
    }

    public function testALengthLimitIsOptional(): void
    {
        $name = str_repeat('a', 31);

        self::assertNull(ItemName::problem($name));
        self::assertSame('COM_BREEZINGFORMSNG_ERROR_ENTER_NAME_CHARACTERS', ItemName::problem($name, 30));
        self::assertNull(ItemName::problem(str_repeat('a', 30), 30));
    }

    public function testAStoredNameIsNotRevalidatedWhileItIsUnchanged(): void
    {
        self::assertNull(ItemName::problemOnSave('contact-us', 'contact-us'));
        self::assertNull(ItemName::problemOnSave("a b\n", "a b\n", 30));
        self::assertSame('COM_BREEZINGFORMSNG_ERROR_ENTER_NAME_CHARACTERS', ItemName::problemOnSave('contact.us', 'contact-us'));
        self::assertSame('COM_BREEZINGFORMSNG_ERROR_ENTER_NAME_CHARACTERS', ItemName::problemOnSave('contact-us', null));
        self::assertSame('COM_BREEZINGFORMSNG_ERROR_ENTER_NAME_CHARACTERS', ItemName::problemOnSave("abc\n", null));
        self::assertSame('COM_BREEZINGFORMSNG_ERROR_ENTER_NAME', ItemName::problemOnSave('', ''));
        self::assertNull(ItemName::problemOnSave('contact', 'contact-us'));
    }

    public function testTheModelsCheckTheNameOnTheServer(): void
    {
        $component = __DIR__ . '/../../administrator/components/com_breezingformsng';

        self::assertStringContainsString('ItemName::problem', (string) file_get_contents($component . '/src/Model/FormOptionsModel.php'));
        // QuickMode and the MCP writer use the same rule, not a copy of it.
        self::assertStringContainsString('ItemName::problemOnSave', (string) file_get_contents($component . '/src/Model/QuickmodeModel.php'));
        self::assertStringNotContainsString('preg_match(\'/^[A-Za-z0-9_]+$/\'', (string) file_get_contents($component . '/src/Model/QuickmodeModel.php'));
        self::assertStringContainsString('ItemName::problem(', (string) file_get_contents($component . '/src/Mcp/McpFormWriter.php'));
        self::assertStringContainsString('ItemName::problem', (string) file_get_contents($component . '/src/Model/PackageItemModel.php'));
        foreach (['PieceModel', 'ScriptModel'] as $model) {
            self::assertMatchesRegularExpression('/namedByIdentifier\(\): bool\s*\{\s*return true;/', (string) file_get_contents($component . '/src/Model/' . $model . '.php'));
        }
        // Menus keep a free name.
        self::assertStringNotContainsString('ItemName', (string) file_get_contents($component . '/src/Model/MenuModel.php'));
        self::assertStringContainsString('required pattern="[a-zA-Z0-9_]+"', (string) file_get_contents($component . '/layouts/forms/advanced_options.php'));
    }
}
