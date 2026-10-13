<?php

/**
 * BreezingForms NG - A Joomla Forms Application
 *
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 *
 * SPDX-License-Identifier: GPL-2.0-or-later
 **/

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Site\Service\Notification;

use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Site\Service\Notification\MailTemplateResolver;

final class MailTemplateResolverTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/bfng-mailtpl-' . bin2hex(random_bytes(8));
        self::assertTrue(mkdir($this->directory));
    }

    protected function tearDown(): void
    {
        foreach (new \DirectoryIterator($this->directory) as $file) {
            if ($file->isFile()) {
                unlink($file->getPathname());
            }
        }

        rmdir($this->directory);
    }

    public function testPrefersHtmlThenTextFormTemplate(): void
    {
        self::assertNotFalse(file_put_contents($this->directory . '/contact.txt.php', 'text'));
        $resolver = new MailTemplateResolver();

        $result = $resolver->resolve($this->directory, 'contact', '', 'mailtpl');

        self::assertSame($this->directory . '/contact.txt.php', $result['file']);
        self::assertFalse($result['isHtml']);
    }

    public function testFallsBackToDefaultAndReportsHtml(): void
    {
        self::assertNotFalse(file_put_contents($this->directory . '/mailtpl.html.php', 'html'));
        $resolver = new MailTemplateResolver();

        $result = $resolver->resolve($this->directory, 'contact', '', 'mailtpl');

        self::assertSame($this->directory . '/mailtpl.html.php', $result['file']);
        self::assertTrue($result['isHtml']);
    }

    public function testReturnsEmptyResultWhenNoTemplateExists(): void
    {
        self::assertSame(
            ['file' => '', 'isHtml' => false],
            (new MailTemplateResolver())->resolve($this->directory, 'contact', '_mailback', 'mailbacktpl')
        );
    }
}
