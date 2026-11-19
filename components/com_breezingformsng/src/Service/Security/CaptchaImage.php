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

namespace Vcmb\Component\BreezingformsNG\Site\Service\Security;

\defined('_JEXEC') or die;

use Gregwar\Captcha\CaptchaBuilder;
use Gregwar\Captcha\PhraseBuilder;

/**
 * The security code image: a few characters on a light background, with a slight distortion
 * and only faint lines behind them, so that a person reads it at once. Characters
 * that look alike (i, l, o, 0, 1, u, v, w, y, s, z, 2, 5, g, q, b, 6) are left out.
 */
final class CaptchaImage
{
    public const CHARACTERS = 'acdefhjkmnprtx3479';
    public const LENGTH = 5;
    public const WIDTH = 230;
    public const HEIGHT = 80;
    public const MIME_TYPE = 'image/jpeg';

    private const QUALITY = 80;

    /** @return array{phrase: string, image: string} The code to keep and the JPEG that shows it. */
    public function create(): array
    {
        $builder = new CaptchaBuilder(null, new PhraseBuilder(self::LENGTH, self::CHARACTERS));
        $builder->setBackgroundColor(245, 245, 240)
            ->setTextColor(32, 31, 29)
            ->setMaxBehindLines(2)
            ->setMaxFrontLines(0)
            ->setDistortion(true)
            ->build(self::WIDTH, self::HEIGHT);

        return ['phrase' => (string) $builder->getPhrase(), 'image' => $builder->get(self::QUALITY)];
    }
}
