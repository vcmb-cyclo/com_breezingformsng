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

namespace Vcmb\Component\BreezingformsNG\Tests\Site\Service\Widget;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Site\Service\Rendering\QuickMode\QuickModeCheckboxStrategy;
use Vcmb\Component\BreezingformsNG\Site\Service\Widget\ConsentProof;
use Vcmb\Component\BreezingformsNG\Site\Service\Widget\WidgetFieldBuilder;
use Vcmb\Component\BreezingformsNG\Site\Service\Widget\WidgetOptions;

final class ConsentTest extends TestCase
{
    public function testTheProofKeepsTheDateTheAcceptedTextAndThePolicy(): void
    {
        $now = new DateTimeImmutable('2026-11-26 21:05:09 +02:00');
        $template = 'Accepted on %1$s: "%2$s"';

        self::assertSame(
            'Accepted on 2026-11-26 19:05:09 UTC: "I agree to the policy." — https://example.org/privacy',
            ConsentProof::value('Yes', $template, ' <b>I agree</b> to the policy. ', 'https://example.org/privacy', $now)
        );
        self::assertSame(
            'Accepted on 2026-11-26 19:05:09 UTC: "I agree"',
            ConsentProof::value('Yes', $template, 'I agree', '', $now)
        );
        // An unchecked box proves nothing.
        self::assertSame('', ConsentProof::value('', $template, 'I agree', '', $now));
    }

    public function testOnlyAWebAddressOrAPageOfTheSiteIsLinked(): void
    {
        foreach (['https://example.org/p', 'http://example.org', '/privacy', 'index.php?option=com_content&view=article&id=3'] as $url) {
            self::assertSame($url, WidgetOptions::options('consent', ['consentUrl' => $url])['url'], $url);
        }

        foreach (['javascript:alert(1)', 'data:text/html,x', '//evil.example', 'privacy page', ' '] as $url) {
            self::assertSame('', WidgetOptions::options('consent', ['consentUrl' => $url])['url'], $url);
        }
    }

    public function testTheConsentBoxIsNeverPrecheckedAndLinksThePolicy(): void
    {
        $field = ['bfType' => 'bfCheckbox', 'bfName' => 'Consent', 'dbId' => 9, 'value' => 'Yes', 'checked' => true,
            'widget' => 'consent', 'consentUrl' => 'https://example.org/p?a=1&b="'];
        $html = (new QuickModeCheckboxStrategy())->build($field, 'ff_elem', '', false, 'Privacy <policy>');

        self::assertStringNotContainsString('checked="checked"', $html);
        self::assertStringNotContainsString('<a ', (new QuickModeCheckboxStrategy())->build(
            ['widget' => '', 'checked' => true] + $field,
            'ff_elem',
            ''
        ));
        self::assertFalse(WidgetFieldBuilder::hasWidget($field));
        // The text to accept sits next to the box, apart from the label of the field.
        $texts = ['consentText' => 'I <agree>', 'consentText_translationfr_FR' => 'J’accepte'] + $field;
        $strategy = new QuickModeCheckboxStrategy();
        self::assertStringContainsString(
            '<label class="bfng-consent-text" for="ff_elem9">I &lt;agree&gt;</label>',
            $strategy->build($texts, 'ff_elem', '')
        );
        self::assertStringContainsString('>J’accepte</label>', $strategy->build($texts, 'ff_elem', '', false, '', 'fr_FR'));
        // An address holding a quote is not linked at all.
        self::assertStringNotContainsString('<a ', $html);
        $linked = (new QuickModeCheckboxStrategy())->build(
            ['consentUrl' => 'https://example.org/p?a=1&b=2'] + $field,
            'ff_elem',
            '',
            false,
            'Privacy <policy>'
        );
        self::assertStringContainsString(
            '<a class="bfng-consent-link" href="https://example.org/p?a=1&amp;b=2" target="_blank" rel="noopener">'
            . 'Privacy &lt;policy&gt;</a>',
            $linked
        );
    }
}
