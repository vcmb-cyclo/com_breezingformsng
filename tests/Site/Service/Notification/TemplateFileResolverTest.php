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

namespace Vcmb\Component\BreezingformsNG\Tests\Site\Service\Notification;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Site\Service\Notification\MailTemplateResolver;
use Vcmb\Component\BreezingformsNG\Site\Service\Notification\TemplateFileResolver;

final class TemplateFileResolverTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/bfng-tpl-' . bin2hex(random_bytes(8));
        mkdir($this->root . '/tpl', 0777, true);
        file_put_contents($this->root . '/tpl/contact_x.php', '<?php');
        file_put_contents($this->root . '/outside_x.php', '<?php');
        file_put_contents($this->root . '/tpl/contact.html.php', '<?php');
        file_put_contents($this->root . '/outside.html.php', '<?php');
        mkdir($this->root . '/tpl/sub');
        file_put_contents($this->root . '/tpl/sub/a_x.php', '<?php');
    }

    protected function tearDown(): void
    {
        foreach (['/tpl/sub/a_x.php', '/tpl/contact_x.php', '/tpl/contact.html.php', '/outside_x.php', '/outside.html.php'] as $f) {
            @unlink($this->root . $f);
        }
        @rmdir($this->root . '/tpl/sub');
        @rmdir($this->root . '/tpl');
        @rmdir($this->root);
    }

    public function testLegitimateNameResolves(): void
    {
        self::assertSame(realpath($this->root . '/tpl/contact_x.php'), TemplateFileResolver::resolve($this->root . '/tpl', 'contact', '_x.php'));
    }

    #[DataProvider('unsafeNames')]
    public function testUnsafeNamesResolveNothing(string $name): void
    {
        self::assertSame('', TemplateFileResolver::resolve($this->root . '/tpl', $name, '_x.php'));
    }

    /** @return iterable<string, array{string}> */
    public static function unsafeNames(): iterable
    {
        yield 'parent' => ['../outside'];
        yield 'dotdot' => ['..'];
        yield 'subdir' => ['sub/a'];
        yield 'backslash' => ['sub\\a'];
        yield 'nul' => ["contact\0"];
        yield 'empty' => [''];
        yield 'space' => ['con tact'];
    }

    public function testMissingDirectoryResolvesNothing(): void
    {
        self::assertSame('', TemplateFileResolver::resolve($this->root . '/none', 'contact', '_x.php'));
    }

    public function testMailResolverIgnoresTraversalAndFallsBackToDefault(): void
    {
        file_put_contents($this->root . '/tpl/mailtpl.html.php', '<?php');
        $result = (new MailTemplateResolver())->resolve($this->root . '/tpl', '../outside', '', 'mailtpl');
        self::assertSame(realpath($this->root . '/tpl/mailtpl.html.php'), $result['file']);
        unlink($this->root . '/tpl/mailtpl.html.php');

        $result = (new MailTemplateResolver())->resolve($this->root . '/tpl', '../outside', '', 'mailtpl');
        self::assertSame('', $result['file']);
    }
}
