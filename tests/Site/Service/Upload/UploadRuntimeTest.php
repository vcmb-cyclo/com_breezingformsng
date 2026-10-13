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

namespace Joomla\Input {
    if (!class_exists(Input::class)) {
        class Input
        {
        }
    }
}

namespace Vcmb\Component\BreezingformsNG\Tests\Site\Service\Upload {

use Joomla\Input\Input;
use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Site\Service\Upload\UploadRuntime;

final class UploadRuntimeTest extends TestCase
{
    public function testDelegatesByteSizeParsingAndImageDetection(): void
    {
        $runtime = new UploadRuntime(new Input());

        self::assertSame(2.0 * 1024, $runtime->parseByteSize('2k'));
        self::assertFalse($runtime->imageType('/tmp/missing-upload-image-' . bin2hex(random_bytes(4))));
    }

    public function testFindsQuickModeElementsThroughTheUploadRuntime(): void
    {
        $element = [
            'properties' => ['type' => 'element', 'bfName' => 'attachment'],
        ];
        $runtime = new UploadRuntime(new Input());

        self::assertSame(
            $element,
            $runtime->findQuickModeElement(['children' => [['children' => [$element]]]], 'attachment')
        );
        self::assertNull($runtime->findQuickModeElement([], 'missing'));
    }
}
}
