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

namespace Vcmb\Component\BreezingformsNG\Tests\Site\Service\Rendering;

use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Site\Service\Rendering\ClassNameResolver;

final class ClassNameResolverTest extends TestCase
{
    private ClassNameResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new ClassNameResolver();
    }

    public function testResolvesPlainDefinitionWithSuffix(): void
    {
        $this->assertSame('bfInputField', $this->resolver->resolve('bfInput', 0, 'Field'));
    }

    public function testResolvesTemplateSpecificSegment(): void
    {
        $this->assertSame('bfDarkField', $this->resolver->resolve('bfLight;bfDark;bfHighContrast', 1, 'Field'));
    }

    public function testReturnsEmptyStringWhenTemplateIndexMissing(): void
    {
        $this->assertSame('', $this->resolver->resolve('bfLight;bfDark', 5, 'Field'));
    }

    public function testTrimsWhitespaceBeforeAppendingSuffix(): void
    {
        $this->assertSame('bfSpacedField', $this->resolver->resolve('  bfSpaced  ', 0, 'Field'));
    }

    public function testReturnsEmptyStringForEmptyDefinition(): void
    {
        $this->assertSame('', $this->resolver->resolve('', 0, 'Field'));
    }
}
