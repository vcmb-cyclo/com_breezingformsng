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

if (!defined('JPATH_SITE')) {
    define('JPATH_SITE', dirname(__DIR__, 2));
}

use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Administrator\Helper\QuickmodeHtml;

final class QuickmodeHtmlTest extends TestCase
{
    public function testDecodeJsonArrayReturnsTheDecodedObject(): void
    {
        self::assertSame(
            ['properties' => ['themebootstrap' => 'Azure']],
            QuickmodeHtml::decodeJsonArray('{"properties":{"themebootstrap":"Azure"}}')
        );
    }

    public function testDecodeJsonArrayNormalizesInvalidJsonToAnEmptyArray(): void
    {
        self::assertSame([], QuickmodeHtml::decodeJsonArray('{invalid'));
    }
}
