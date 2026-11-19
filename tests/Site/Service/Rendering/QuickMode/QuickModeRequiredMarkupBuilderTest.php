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

namespace Vcmb\Component\BreezingformsNG\Tests\Site\Service\Rendering\QuickMode;

use DOMDocument;
use DOMXPath;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Site\Service\Rendering\QuickMode\QuickModeRequiredMarkupBuilder;

require_once __DIR__ . '/joomla-text-stub.php';

final class QuickModeRequiredMarkupBuilderTest extends TestCase
{
    public function testMarkerHidesTheAsteriskAndAddsVisuallyHiddenText(): void
    {
        $html = (new QuickModeRequiredMarkupBuilder())->marker('<i class="fa-solid fa-asterisk" aria-hidden="true"></i>');

        $document = new DOMDocument();
        $document->loadHTML('<!doctype html><meta charset="utf-8"><label>Name ' . $html . '</label>');
        $xpath = new DOMXPath($document);

        self::assertSame('true', $xpath->query('//label/i/@aria-hidden')->item(0)?->nodeValue);
        self::assertSame('JREQUIRED', $xpath->query('//label/span[@class="visually-hidden"]')->item(0)?->textContent);
    }

    #[DataProvider('supportedTypes')]
    public function testAriaRequiredIsSetOnSupportedControls(string $type): void
    {
        self::assertSame('aria-required="true" ', (new QuickModeRequiredMarkupBuilder())->ariaRequiredAttribute($type, true));
        self::assertSame('', (new QuickModeRequiredMarkupBuilder())->ariaRequiredAttribute($type, false));
    }

    /** @return array<string, array{string}> */
    public static function supportedTypes(): array
    {
        return [
            'text' => ['bfTextfield'], 'textarea' => ['bfTextarea'], 'select' => ['bfSelect'], 'checkbox' => ['bfCheckbox'],
        ];
    }

    public function testNoAriaRequiredOnGroupsOrButtons(): void
    {
        $builder = new QuickModeRequiredMarkupBuilder();

        self::assertSame('', $builder->ariaRequiredAttribute('bfRadioGroup', true));
        self::assertSame('', $builder->ariaRequiredAttribute('bfCheckboxGroup', true));
        self::assertSame('', $builder->ariaRequiredAttribute('bfSubmitButton', true));
    }
}
