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
use Vcmb\Component\BreezingformsNG\Site\Service\Runtime\FormDisplayContextResolver;

final class FormDisplayContextResolverTest extends TestCase
{
    public function testResolvesFrontendFormAndRunMode(): void
    {
        $context = (new FormDisplayContextResolver())->resolve(
            0,
            false,
            12,
            0,
            true,
            0,
            false,
            0,
            'https://example.test'
        );

        self::assertSame(0, $context->inline);
        self::assertSame(0, $context->template);
        self::assertSame('ff_form12', $context->formId);
        self::assertSame('https://example.test', $context->homepage);
        self::assertFalse($context->showGrid);
        self::assertTrue($context->canRun);
    }

    public function testFrontendRunsFormSavedForFrontendAndBackend(): void
    {
        $context = (new FormDisplayContextResolver())->resolve(
            0,
            false,
            12,
            2,
            true,
            0,
            false,
            0,
            'https://example.test'
        );

        self::assertTrue($context->canRun);
    }

    public function testFrontendRejectsFormSavedForBackendOnly(): void
    {
        $context = (new FormDisplayContextResolver())->resolve(
            0,
            false,
            12,
            1,
            true,
            0,
            false,
            0,
            'https://example.test'
        );

        self::assertFalse($context->canRun);
    }

    public function testResolvesBackendFrameAndRunModeRestriction(): void
    {
        $context = (new FormDisplayContextResolver())->resolve(
            1,
            true,
            7,
            1,
            true,
            0,
            false,
            0,
            'https://example.test'
        );

        self::assertSame(0, $context->inline);
        self::assertSame(0, $context->template);
        self::assertSame('https://example.test/administrator/index.php?tmpl=component', $context->homepage);
        self::assertFalse($context->canRun);
    }

    public function testResolvesPreviewGridAndRejectsUnpublishedForm(): void
    {
        $context = (new FormDisplayContextResolver())->resolve(
            2,
            false,
            9,
            0,
            false,
            1,
            true,
            2,
            'https://example.test'
        );

        self::assertSame(1, $context->inline);
        self::assertSame(1, $context->template);
        self::assertSame('adminForm', $context->formId);
        self::assertSame('index.php?tmpl=component', $context->homepage);
        self::assertTrue($context->showGrid);
        self::assertFalse($context->canRun);
    }
}
