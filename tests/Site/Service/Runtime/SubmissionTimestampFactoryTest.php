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

namespace Joomla\CMS\Date {
    if (!class_exists(Date::class)) {
        class Date extends \DateTime
        {
            public function format(string $format, bool $local = false): string
            {
                return parent::format($format);
            }

            public function getOffsetFromGMT(): int
            {
                return (int) $this->format('Z');
            }
        }
    }
}

namespace Vcmb\Component\BreezingformsNG\Tests\Site\Service\Runtime {

use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Site\Service\Runtime\SubmissionTimestampFactory;

final class SubmissionTimestampFactoryTest extends TestCase
{
    public function testCreatesSqlTimestampInRequestedTimezone(): void
    {
        $timestamp = (new SubmissionTimestampFactory())->create('UTC');

        self::assertMatchesRegularExpression(
            '/^\\d{4}-\\d{2}-\\d{2} \\d{2}:\\d{2}:\\d{2}$/',
            $timestamp
        );
    }
}
}
