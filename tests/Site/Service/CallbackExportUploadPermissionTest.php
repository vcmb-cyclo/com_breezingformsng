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

namespace Vcmb\Component\BreezingformsNG\Tests\Site\Service;

require_once __DIR__ . '/Rendering/QuickMode/joomla-uri-stub.php';

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Vcmb\Component\BreezingformsNG\Site\Service\Export\ExportEngine;
use Vcmb\Component\BreezingformsNG\Site\Service\Upload\FlashUploadSizeValidator;
use Vcmb\Component\BreezingformsNG\Site\Service\Upload\ImageResizer;
use Vcmb\Component\BreezingformsNG\Site\Service\Upload\TokenizedDirectoryResolver;
use Vcmb\Component\BreezingformsNG\Site\Service\Upload\UploadError;
use Vcmb\Component\BreezingformsNG\Site\Service\Upload\UploadStorage;

final class CallbackExportUploadPermissionTest extends TestCase
{
    private const ROOT = __DIR__ . '/../../..';

    protected function setUp(): void
    {
        defined('JPATH_SITE') || define('JPATH_SITE', self::ROOT);
    }

    public function testExportHelpersGenerateUsableValues(): void
    {
        $engine = (new ReflectionClass(ExportEngine::class))->newInstanceWithoutConstructor();

        self::assertMatchesRegularExpression('/^[a-zA-Z0-9]{24}$/', $engine->random_str(24));
        self::assertTrue($engine->endsWith('submission.csv', '.csv'));
        self::assertFalse($engine->endsWith('submission.csv', '.xml'));
    }

    public function testExportHelpersHandleEmptySuffixAndRejectEmptyAlphabet(): void
    {
        $engine = (new ReflectionClass(ExportEngine::class))->newInstanceWithoutConstructor();

        self::assertTrue($engine->endsWith('submission.csv', ''));
        $this->expectException(\ValueError::class);
        $engine->random_str(1, '');
    }

    public function testExportEngineDoesNotRetainAnUnconditionalBranch(): void
    {
        $source = file_get_contents(self::ROOT . '/components/com_breezingformsng/src/Service/Export/ExportEngine.php');

        self::assertIsString($source);
        self::assertStringNotContainsString('if (true)', $source);
    }

    public function testUploadStorageReportsMissingDirectoryWithoutMovingFile(): void
    {
        $moved = false;
        $storage = new UploadStorage(
            new ImageResizer(),
            static function () use (&$moved): bool {
                $moved = true;

                return true;
            }
        );

        $result = $storage->store(
            '/tmp/upload.tmp',
            '/tmp/bfng-directory-that-does-not-exist-' . bin2hex(random_bytes(4)),
            'submission.txt',
            false,
            null,
            false
        );

        self::assertFalse($result->isSuccessful());
        self::assertSame(UploadError::DirectoryMissing, $result->error);
        self::assertFalse($moved);
    }

    public function testUploadStorageUsesInjectedMoverAndReturnsStoredPath(): void
    {
        $directory = sys_get_temp_dir() . '/bfng-upload-' . bin2hex(random_bytes(4));
        self::assertTrue(mkdir($directory));

        try {
            $storage = new UploadStorage(
                new ImageResizer(),
                static function (string $source, string $destination): bool {
                    return file_put_contents($destination, 'uploaded') !== false;
                }
            );

            $result = $storage->store('/tmp/upload.tmp', $directory, 'submission.txt', false, null, false);

            self::assertTrue($result->isSuccessful());
            self::assertSame($directory . '/submission.txt', $result->path);
            self::assertSame('uploaded', file_get_contents($result->serverPath));
        } finally {
            @unlink($directory . '/submission.txt');
            @rmdir($directory);
        }
    }

    public function testUploadStorageReportsMoverFailure(): void
    {
        $directory = sys_get_temp_dir() . '/bfng-upload-' . bin2hex(random_bytes(4));
        self::assertTrue(mkdir($directory));

        try {
            $storage = new UploadStorage(
                new ImageResizer(),
                static fn (string $source, string $destination): bool => false
            );

            $result = $storage->store('/tmp/upload.tmp', $directory, 'submission.txt', false, null, false);

            self::assertFalse($result->isSuccessful());
            self::assertSame(UploadError::MoveFailed, $result->error);
            self::assertFileDoesNotExist($directory . '/submission.txt');
        } finally {
            @rmdir($directory);
        }
    }

    public function testUploadStoragePreservesExistingFileWithUniqueDestination(): void
    {
        $directory = sys_get_temp_dir() . '/bfng-upload-' . bin2hex(random_bytes(4));
        self::assertTrue(mkdir($directory));
        self::assertSame(3, file_put_contents($directory . '/submission.txt', 'old'));

        try {
            $storage = new UploadStorage(
                new ImageResizer(),
                static function (string $source, string $destination): bool {
                    return file_put_contents($destination, 'new') !== false;
                }
            );

            $result = $storage->store('/tmp/upload.tmp', $directory, 'submission.txt', true, null, false);

            self::assertTrue($result->isSuccessful());
            self::assertNotSame($directory . '/submission.txt', $result->path);
            self::assertSame('old', file_get_contents($directory . '/submission.txt'));
            self::assertSame('new', file_get_contents($result->serverPath));
        } finally {
            foreach (glob($directory . '/*') ?: [] as $file) {
                @unlink($file);
            }
            @rmdir($directory);
        }
    }

    public function testUploadStorageReturnsPublicUrlAndServerPath(): void
    {
        $directory = self::ROOT . '/.tmp-bfng-upload-' . bin2hex(random_bytes(4));
        self::assertTrue(mkdir($directory));

        try {
            $storage = new UploadStorage(
                new ImageResizer(),
                static function (string $source, string $destination): bool {
                    return file_put_contents($destination, 'uploaded') !== false;
                }
            );

            $result = $storage->store('/tmp/upload.tmp', $directory, 'submission.txt', false, null, true);

            self::assertTrue($result->isSuccessful());
            $relativeDirectory = str_replace(JPATH_SITE . '/', '', $directory);
            self::assertSame('http://example.test/' . $relativeDirectory . '/submission.txt', $result->path);
            self::assertSame($directory . '/submission.txt', $result->serverPath);
        } finally {
            @unlink($directory . '/submission.txt');
            @rmdir($directory);
        }
    }

    public function testTokenizedUploadPathsSanitizeUnsafeFolderCharacters(): void
    {
        $resolver = (new ReflectionClass(TokenizedDirectoryResolver::class))->newInstanceWithoutConstructor();

        self::assertSame(
            'uploads/user_name/{field}/file_.txt',
            $resolver->makeSafeFolder('uploads/user name/{field}/file?.txt')
        );
    }

    public function testImageTypeReturnsFalseForMissingFile(): void
    {
        self::assertFalse((new ImageResizer())->imageType('/tmp/bfng-missing-image-' . bin2hex(random_bytes(4))));
    }

    public function testFlashUploadValidationTraversesNestedElements(): void
    {
        $validator = new FlashUploadSizeValidator();
        $file = tempnam(sys_get_temp_dir(), 'bfng-upload-test-');
        self::assertNotFalse($file);
        self::assertSame(4, file_put_contents($file, 'test'));

        try {
            $result = $validator->findOversizedLabel([
                'children' => [[
                    'properties' => [
                        'type' => 'element',
                        'bfType' => 'bfFile',
                        'flashUploaderBytes' => 4,
                        'bfName' => 'attachment',
                    ],
                ]],
            ], $file, 'attachment');

            self::assertNull($result);
        } finally {
            @unlink($file);
        }
    }

    public function testFlashUploadValidationReturnsNestedOversizedLabel(): void
    {
        $validator = new FlashUploadSizeValidator();
        $file = tempnam(sys_get_temp_dir(), 'bfng-upload-test-');
        self::assertNotFalse($file);
        self::assertSame(5, file_put_contents($file, 'large'));

        try {
            self::assertSame('Attachment', $validator->findOversizedLabel([
                'children' => [[
                    'properties' => [
                        'type' => 'element',
                        'bfType' => 'bfFile',
                        'flashUploaderBytes' => 4,
                        'bfName' => 'attachment',
                        'label' => 'Attachment',
                    ],
                ]],
            ], $file, ' attachment '));
        } finally {
            @unlink($file);
        }
    }

    public function testCallbacksKeepBoundDatabaseQueriesAndInputAuthorizationChecks(): void
    {
        foreach (['StripeCallback', 'PayPalCallback', 'SofortCallback'] as $callback) {
            $source = $this->read("components/com_breezingformsng/src/Service/Callback/{$callback}.php");

            self::assertStringContainsString('getInput()', $source, $callback);
        }

        foreach (['PaymentFormLoader.php', 'PaymentRecordService.php'] as $service) {
            $source = $this->read("components/com_breezingformsng/src/Service/Callback/{$service}");

            self::assertStringContainsString('->bind(', $source, $service);
        }

        $controllers = [
            'AboutController.php',
            'DisplayController.php',
            'ScriptsController.php',
            'PiecesController.php',
        ];
        foreach ($controllers as $controller) {
            $source = $this->read("administrator/components/com_breezingformsng/src/Controller/{$controller}");

            self::assertStringContainsString("authorise('core.manage', 'com_breezingformsng')", $source, $controller);
        }
    }

    private function read(string $path): string
    {
        $source = file_get_contents(self::ROOT . '/' . $path);

        self::assertNotFalse($source, "Unable to read {$path}");

        return $source;
    }
}
