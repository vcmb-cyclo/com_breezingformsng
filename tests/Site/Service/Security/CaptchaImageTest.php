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

namespace Vcmb\Component\BreezingformsNG\Tests\Site\Service\Security;

use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Site\Service\Security\CaptchaImage;

final class CaptchaImageTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        $autoload = __DIR__ . '/../../../../administrator/components/com_breezingformsng/vendor/autoload.php';

        if (!class_exists(\Gregwar\Captcha\CaptchaBuilder::class) && is_file($autoload)) {
            require_once $autoload;
        }
    }

    protected function setUp(): void
    {
        if (!class_exists(\Gregwar\Captcha\CaptchaBuilder::class) || !function_exists('imagecreatetruecolor')) {
            self::markTestSkipped('The Composer dependencies of the component or the GD extension are not installed.');
        }
    }

    public function testItGivesACodeOfFiveUnambiguousCharactersAndAJpegThatShowsIt(): void
    {
        $captcha = (new CaptchaImage())->create();

        self::assertMatchesRegularExpression('/^[abcdefghjkmnpqrstuvwxyz23456789]{5}$/', $captcha['phrase']);
        self::assertStringStartsWith("\xFF\xD8\xFF", $captcha['image']);
        $size = getimagesizefromstring($captcha['image']);
        self::assertIsArray($size);
        self::assertSame([CaptchaImage::WIDTH, CaptchaImage::HEIGHT], [$size[0], $size[1]]);
        self::assertSame('image/jpeg', $size['mime']);
    }

    public function testEveryImageHasItsOwnCode(): void
    {
        $codes = [];
        for ($i = 0; $i < 8; $i++) {
            $codes[] = (new CaptchaImage())->create()['phrase'];
        }

        self::assertGreaterThan(5, count(array_unique($codes)));
    }

    public function testNothingIsWrittenToThePhpErrorChannelOnPhp85(): void
    {
        $errors = [];
        set_error_handler(static function (int $number, string $message) use (&$errors): bool {
            $errors[] = $message;

            return true;
        });

        try {
            (new CaptchaImage())->create();
        } finally {
            restore_error_handler();
        }

        self::assertSame([], $errors, 'No notice, warning or deprecation while the image is drawn.');
    }

    public function testTheCharactersLeaveOutTheOnesThatAreEasyToConfuse(): void
    {
        foreach (['i', 'l', 'o', '0', '1'] as $character) {
            self::assertStringNotContainsString($character, CaptchaImage::CHARACTERS);
        }
    }
}
