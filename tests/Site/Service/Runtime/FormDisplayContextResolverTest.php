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
    public function testResolvesFrontendForm(): void
    {
        $context = (new FormDisplayContextResolver())->resolve(12, 0, true, 'https://example.test');

        self::assertSame(0, $context->inline);
        self::assertSame(0, $context->template);
        self::assertSame('ff_form12', $context->formId);
        self::assertSame('https://example.test', $context->homepage);
        self::assertFalse($context->showGrid);
        self::assertTrue($context->canRun);
    }

    public function testRunsFormSavedForFrontendAndBackend(): void
    {
        self::assertTrue((new FormDisplayContextResolver())->resolve(12, 2, true, 'https://example.test')->canRun);
    }

    public function testRejectsFormSavedForBackendOnly(): void
    {
        self::assertFalse((new FormDisplayContextResolver())->resolve(12, 1, true, 'https://example.test')->canRun);
    }

    public function testRejectsUnpublishedForm(): void
    {
        self::assertFalse((new FormDisplayContextResolver())->resolve(9, 0, false, 'https://example.test')->canRun);
    }
}
