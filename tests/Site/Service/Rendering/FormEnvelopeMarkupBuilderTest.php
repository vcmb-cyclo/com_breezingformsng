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
use Vcmb\Component\BreezingformsNG\Site\Service\Rendering\FormEnvelopeMarkupBuilder;

final class FormEnvelopeMarkupBuilderTest extends TestCase
{
    public function testBuildsOpeningWrapperWithOptionalClass(): void
    {
        $builder = new FormEnvelopeMarkupBuilder();

        self::assertSame('<div id="ff_formdiv12" class="bfFormDiv custom-form">', $builder->opening('12', 'custom-form'));
        self::assertSame('<div id="ff_formdiv12" class="bfFormDiv">', $builder->opening('12', ''));
    }

    public function testBuildsClosingWrapper(): void
    {
        self::assertSame("</div><!-- form end -->\n", (new FormEnvelopeMarkupBuilder())->closing("\n"));
    }

    public function testPreservesFrontendFieldOrder(): void
    {
        self::assertSame(
            "contextsubmissiontokenoptionaladditionaltechnicalrouting</form>\n",
            (new FormEnvelopeMarkupBuilder())->frontend('context', 'submission', 'token', 'optional', 'additional', 'technical', 'routing', "\n")
        );
    }
}
