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

namespace Vcmb\Component\BreezingformsNG\Tests\Site\Service\Rendering\QuickMode;

use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Site\Service\Rendering\QuickMode\QuickModeUploadBasePathBuilder;

final class QuickModeUploadBasePathBuilderTest extends TestCase
{
    public function testPreservesFrontendBaseUri(): void
    {
        self::assertSame(
            'https://example.test/forms/',
            (new QuickModeUploadBasePathBuilder())->build('https://example.test/forms/')
        );
    }

    public function testRemovesAdministratorSegmentFromBackendBaseUri(): void
    {
        self::assertSame(
            'https://example.test/',
            (new QuickModeUploadBasePathBuilder())->build('https://example.test/administrator/')
        );
    }

    public function testDoesNotRemoveAnUnrelatedAdministratorPathSegment(): void
    {
        self::assertSame(
            'https://example.test/administrator/path/',
            (new QuickModeUploadBasePathBuilder())->build('https://example.test/administrator/path/')
        );
    }
}
