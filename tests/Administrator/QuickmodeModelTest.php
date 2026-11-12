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

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator;

require_once __DIR__ . '/../Support/joomla-base-database-model-stub.php';

use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Administrator\Model\QuickmodeModel;

final class QuickmodeModelTest extends TestCase
{
    private string $themeDirectory;

    protected function setUp(): void
    {
        $this->themeDirectory = sys_get_temp_dir() . '/bfng-themes-' . bin2hex(random_bytes(8));
        self::assertTrue(mkdir($this->themeDirectory));
    }

    protected function tearDown(): void
    {
        foreach (new \DirectoryIterator($this->themeDirectory) as $file) {
            if ($file->isFile()) {
                unlink($file->getPathname());
            } elseif (!$file->isDot()) {
                rmdir($file->getPathname());
            }
        }

        rmdir($this->themeDirectory);
    }

    public function testScanThemeDirKeepsOnlyThemeDirectories(): void
    {
        mkdir($this->themeDirectory . '/clean');
        mkdir($this->themeDirectory . '/images');
        mkdir($this->themeDirectory . '/img');
        mkdir($this->themeDirectory . '/.svn');
        self::assertNotFalse(file_put_contents($this->themeDirectory . '/README.txt', 'ignore'));

        $themes = $this->scanThemeDir($this->themeDirectory);

        self::assertSame(['clean'], $themes);
    }

    public function testScanThemeDirReturnsEmptyForMissingDirectory(): void
    {
        self::assertSame([], $this->scanThemeDir($this->themeDirectory . '/missing'));
    }

    /**
     * The properties of a text element of the editor tree, as the editor of the
     * current version sends them, without the keys an old form never had.
     *
     * @return array<string, mixed>
     */
    private static function textProperties(): array
    {
        return [
            'type' => 'element', 'bfType' => 'bfTextfield', 'value' => '', 'placeholder' => '',
            'label' => 'First name', 'bfName' => 'firstname', 'orderNumber' => -1, 'tabIndex' => 0, 'logging' => 1,
            'validationId' => 0, 'validationCode' => '', 'validationMessage' => '', 'validationFunctionName' => '', 'validationCondition' => 0,
            'initId' => 0, 'initCode' => '', 'initFunctionName' => '', 'initCondition' => 0,
            'actionId' => 0, 'actionFunctionName' => '', 'dbId' => 0,
        ];
    }

    /**
     * @param array<string, mixed> $properties
     * @param list<string>         $warnings   Filled with the PHP warnings raised.
     * @return array<string, mixed> The element the model builds.
     */
    private function elementOf(array $properties, array &$warnings): array
    {
        $tree = ['attributes' => ['id' => 'bfQuickModeElement1'], 'properties' => $properties];
        $areas = new \stdClass();
        $areas->container = [['elementCount' => 0, 'elements' => []]];
        $model = (new \ReflectionClass(QuickmodeModel::class))->newInstanceWithoutConstructor();
        $method = new \ReflectionMethod($model, 'createAreasFromTree');

        set_error_handler(static function (int $level, string $message) use (&$warnings): bool {
            $warnings[] = $message;

            return true;
        });

        try {
            $method->invoke($model, $tree, $areas);
        } finally {
            restore_error_handler();
        }

        return $areas->container[0]['elements'][0];
    }

    public function testATextElementOfAnOldFormRaisesNoWarning(): void
    {
        $warnings = [];
        $element = $this->elementOf(self::textProperties(), $warnings);

        // Forms saved by earlier versions hold no password, mailback or mailbackAsSender key.
        self::assertSame([], $warnings);
        self::assertSame('Text', $element['bfType']);
        self::assertSame(0, $element['flag1']);
        self::assertSame(0, $element['mailback']);
        self::assertSame(0, $element['mailbackAsSender']);
        self::assertSame('', $element['mailbackfile']);
        self::assertFalse($element['options']['password']);
        self::assertFalse($element['options']['mailback']);
    }

    public function testATextElementKeepsItsPasswordAndMailbackSettings(): void
    {
        $warnings = [];
        $element = $this->elementOf(
            ['password' => true, 'mailback' => true, 'mailbackAsSender' => true, 'mailbackfile' => 'copy.pdf'] + self::textProperties(),
            $warnings
        );

        self::assertSame([], $warnings);
        self::assertSame(1, $element['flag1']);
        self::assertSame(1, $element['mailback']);
        self::assertSame(1, $element['mailbackAsSender']);
        self::assertSame('copy.pdf', $element['mailbackfile']);
        self::assertTrue($element['options']['password']);
        self::assertTrue($element['options']['mailback']);
    }

    private function scanThemeDir(string $folder): array
    {
        $model = (new \ReflectionClass(QuickmodeModel::class))->newInstanceWithoutConstructor();
        $method = new \ReflectionMethod($model, 'scanThemeDir');

        return $method->invoke($model, $folder);
    }
}
