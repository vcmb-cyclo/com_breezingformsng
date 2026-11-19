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

namespace Vcmb\Component\BreezingformsNG\Tests\Site\Service\Callback;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Site\Service\Callback\OptLinkPolicy;

if (!defined('_JEXEC')) {
    define('_JEXEC', 1);
}

final class OptLinkPolicyTest extends TestCase
{
    public function testEveryTokenTheMailGeneratorCanMakeIsUsable(): void
    {
        $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $policy = new OptLinkPolicy();

        for ($i = 0; $i < 300; $i++) {
            $token = '';
            for ($j = 0; $j < 20; $j++) {
                $token .= $keyspace[random_int(0, 61)];
            }

            // SubmissionEngine builds the token with randomString(20), then bf_b64enc().
            self::assertTrue($policy->isUsable('42', base64_encode($token)), $token);
        }
    }

    #[DataProvider('links')]
    public function testOnlyALinkWithARealTokenIsUsable(string $id, string $token, bool $expected): void
    {
        self::assertSame($expected, (new OptLinkPolicy())->isUsable($id, $token));
    }

    /** @return array<string, array{string, string, bool}> */
    public static function links(): array
    {
        return [
            'a token as the e-mail carries it' => ['42', base64_encode('aB3dE6gH9jK2mN5pQ8sT'), true],
            'a base64 token with two paddings' => ['42', base64_encode('aB3dE6gH9jK2mN5pQ8'), true],
            'a plain token of letters and digits' => ['42', 'aB3dE6gH9jK2mN5pQ8sT', true],
            'padding in the middle' => ['42', 'aB3dE6gH9jK2=N5pQ8sT', false],
            'three paddings' => ['42', 'aB3dE6gH9jK2mN5pQ8s===', false],
            'plus and slash are not in our tokens' => ['42', 'aB3dE6gH9jK2mN5pQ8+/', false],
            'empty token matches a record without one' => ['42', '', false],
            'blank token' => ['42', '   ', false],
            'short token' => ['42', 'abc', false],
            'token with punctuation' => ['42', "aB3dE6gH9jK2mN5pQ8s'", false],
            'no id' => ['', 'aB3dE6gH9jK2mN5pQ8sT', false],
            'zero id' => ['0', 'aB3dE6gH9jK2mN5pQ8sT', false],
            'id that is not a number' => ['4 OR 1=1', 'aB3dE6gH9jK2mN5pQ8sT', false],
            'negative id' => ['-1', 'aB3dE6gH9jK2mN5pQ8sT', false],
        ];
    }
}
