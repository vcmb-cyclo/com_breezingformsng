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
use Vcmb\Component\BreezingformsNG\Site\Service\Rendering\QuickMode\QuickModeThemeName;

final class QuickModeThemeNameTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/bfng-theme-' . bin2hex(random_bytes(8));
        mkdir($this->root . '/themes/Azure', 0777, true);
        mkdir($this->root . '/x', 0777, true);
        file_put_contents($this->root . '/themes/a_file', '');
    }

    protected function tearDown(): void
    {
        @unlink($this->root . '/themes/a_file');
        @rmdir($this->root . '/themes/Azure');
        @rmdir($this->root . '/themes');
        @rmdir($this->root . '/x');
        @rmdir($this->root);
    }

    public function testInstalledThemeIsKept(): void
    {
        self::assertSame('Azure', QuickModeThemeName::resolve($this->root . '/themes', 'Azure'));
    }

    public function testShippedAndNoneThemesAreKept(): void
    {
        self::assertSame('carte', QuickModeThemeName::resolve($this->root . '/themes', 'carte', ['carte']));
        self::assertSame('none', QuickModeThemeName::resolve($this->root . '/themes', 'none'));
        self::assertSame('', QuickModeThemeName::resolve($this->root . '/themes', ''));
    }

    #[DataProvider('invalidThemes')]
    public function testInvalidThemeFallsBackToNoTheme(mixed $theme): void
    {
        self::assertSame('', QuickModeThemeName::resolve($this->root . '/themes', $theme));
        self::assertSame('none', QuickModeThemeName::resolve($this->root . '/themes', $theme, [], 'none'));
    }

    /** @return iterable<string, array{mixed}> */
    public static function invalidThemes(): iterable
    {
        yield 'traversal' => ['../x'];
        yield 'deep traversal' => ['../../x'];
        yield 'dot' => ['.'];
        yield 'dotdot' => ['..'];
        yield 'slash' => ['Azure/x'];
        yield 'missing' => ['Nope'];
        yield 'file not dir' => ['a_file'];
        yield 'array' => [['Azure']];
        yield 'null' => [null];
    }
}
