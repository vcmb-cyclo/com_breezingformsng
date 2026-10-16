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

/**
 * ContentBuilder NG changes the constructors of its services between
 * releases: they are taken from its container, never built here.
 */
final class ContentBuilderFormSyncTest extends TestCase
{
    private const ROOT = __DIR__ . '/../../..';

    public function testTheServicesComeFromTheContentBuilderContainer(): void
    {
        $sync = $this->read('administrator/components/com_breezingformsng/src/Service/ContentBuilderFormSync.php');

        self::assertStringContainsString('->getContainer()->get(FormSupportService::class)', $sync);
        self::assertStringNotContainsString('new FormSupportService', $sync);
        self::assertStringNotContainsString('require_once', $sync);
    }

    public function testNoContentBuilderServiceIsConstructedInThisComponent(): void
    {
        foreach (['administrator/components/com_breezingformsng/src', 'components/com_breezingformsng/src'] as $dir) {
            $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(self::ROOT . '/' . $dir));

            foreach ($files as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    self::assertDoesNotMatchRegularExpression(
                        '/new\s+\\\\?CB\\\\Component\\\\Contentbuilderng\\\\[A-Za-z\\\\]*Service\\\\/',
                        (string) file_get_contents($file->getPathname()),
                        $file->getPathname()
                    );
                }
            }
        }

        self::assertStringContainsString(
            'ContentBuilderFormSync(',
            $this->read('administrator/components/com_breezingformsng/src/Controller/QuickmodeController.php')
        );
    }

    private function read(string $path): string
    {
        $source = file_get_contents(self::ROOT . '/' . $path);

        self::assertIsString($source);

        return $source;
    }
}
