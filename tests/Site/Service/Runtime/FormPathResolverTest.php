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

namespace Vcmb\Component\BreezingformsNG\Tests\Site\Service\Runtime;

use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Site\Service\Runtime\FormPathResolver;

final class FormPathResolverTest extends TestCase
{
    protected function setUp(): void
    {
        defined('JPATH_SITE') || define('JPATH_SITE', '/srv/joomla');
    }

    public function testExpandsFormTokensAndPreservesLateTokens(): void
    {
        $context = (new FormPathResolver())->resolve(
            2,
            4,
            'contact',
            'Contact form',
            'home.php',
            '/var/www',
            'https://example.test',
            '{mospath}/images/{ff_name}/{ff_currentpage}',
            '{ff_images}/uploads/{ff_lastpage}/{CBSite}'
        );

        self::assertSame('/var/www/images/contact/2', $context->images);
        self::assertSame('/var/www/images/contact/2/uploads/4/{CBSite}', $context->uploads);
        self::assertSame(JPATH_SITE, $context->values[array_search('{CBSite}', $context->tokens, true)]);
    }

    public function testKeepsSiteTokenVariantsInTheReturnedReplacementContext(): void
    {
        $context = (new FormPathResolver())->resolve(
            1,
            1,
            'form',
            'Title',
            'index.php',
            '/site',
            'https://example.test',
            '{cbsite}/images',
            '{ff_uploads}/{cbsite}'
        );

        self::assertSame('{cbsite}/images', $context->images);
        self::assertSame('{ff_uploads}/{cbsite}', $context->uploads);
        self::assertSame(JPATH_SITE, $context->values[array_search('{cbsite}', $context->tokens, true)]);
    }
}
