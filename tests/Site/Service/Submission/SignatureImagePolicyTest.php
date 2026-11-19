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

namespace Vcmb\Component\BreezingformsNG\Tests\Site\Service\Submission;

use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Site\Service\Submission\SignatureImagePolicy;

if (!defined('_JEXEC')) {
    define('_JEXEC', 1);
}

final class SignatureImagePolicyTest extends TestCase
{
    public function testARealPngIsStorable(): void
    {
        $png = (string) base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==',
            true
        );

        self::assertTrue((new SignatureImagePolicy())->isStorable($png));
    }

    public function testAnythingElseIsNotWrittenUnderAPngName(): void
    {
        $policy = new SignatureImagePolicy();

        self::assertFalse($policy->isStorable(''));
        self::assertFalse($policy->isStorable('<?php system($_GET["c"]);'));
        self::assertFalse($policy->isStorable('<svg onload="alert(1)"></svg>'));
        self::assertFalse($policy->isStorable("\x89PNG\r\n\x1a\n" . str_repeat('A', SignatureImagePolicy::MAX_BYTES)));
    }
}
