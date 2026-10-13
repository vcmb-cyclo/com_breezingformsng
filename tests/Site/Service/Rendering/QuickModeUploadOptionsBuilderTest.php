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

namespace Vcmb\Component\BreezingformsNG\Tests\Site\Service\Rendering\QuickMode;

use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Site\Service\Rendering\QuickMode\QuickModeUploadOptionsBuilder;

final class QuickModeUploadOptionsBuilderTest extends TestCase
{
    public function testNormalizesExtensionsAndPositiveMaximumSize(): void
    {
        self::assertSame(
            [
                'extensions' => 'jpg,png',
                'maxFileSize' => "max_file_size : '2048',",
                'maxBytes' => '2048',
                'multiSelection' => 'false',
                'buttonWidth' => '96',
                'buttonHeight' => '48',
            ],
            (new QuickModeUploadOptionsBuilder())->build([
                'allowedFileExtensions' => 'jpg,png',
                'flashUploaderBytes' => '2048',
                'html5' => true,
                'flashUploader' => true,
                'flashUploaderWidth' => '96.7',
                'flashUploaderHeight' => 48,
            ])
        );
    }

    public function testLeavesMaximumSizeEmptyWhenMissingOrNotPositive(): void
    {
        $builder = new QuickModeUploadOptionsBuilder();

        self::assertSame(
            [
                'extensions' => '',
                'maxFileSize' => '',
                'maxBytes' => '0',
                'multiSelection' => 'false',
                'buttonWidth' => '64',
                'buttonHeight' => '64',
            ],
            $builder->build([])
        );
        self::assertSame(
            [
                'extensions' => 'pdf',
                'maxFileSize' => '',
                'maxBytes' => '0',
                'multiSelection' => 'true',
                'buttonWidth' => '64',
                'buttonHeight' => '64',
            ],
            $builder->build([
                'allowedFileExtensions' => 'pdf',
                'flashUploaderBytes' => 0,
                'flashUploaderMulti' => true,
                'flashUploader' => true,
            ])
        );

        self::assertSame(
            [
                'extensions' => '',
                'maxFileSize' => '',
                'maxBytes' => '0',
                'multiSelection' => 'false',
                'buttonWidth' => '72',
                'buttonHeight' => '80',
            ],
            $builder->build([
                'flashUploaderWidth' => 72,
                'flashUploaderHeight' => 80,
            ])
        );
    }

    public function testUsesDefaultButtonDimensionsWhenValuesAreNotPositive(): void
    {
        self::assertSame(
            [
                'extensions' => '',
                'maxFileSize' => '',
                'maxBytes' => '0',
                'multiSelection' => 'false',
                'buttonWidth' => '64',
                'buttonHeight' => '64',
            ],
            (new QuickModeUploadOptionsBuilder())->build([
                'flashUploaderWidth' => 0,
                'flashUploaderHeight' => -12,
            ])
        );
    }
}
