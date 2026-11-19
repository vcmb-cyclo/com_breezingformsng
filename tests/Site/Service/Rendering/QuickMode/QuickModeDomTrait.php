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

/** Parses renderer output so the tests assert the structure, not the exact string. */
trait QuickModeDomTrait
{
    private function xpath(string $html): DOMXPath
    {
        $document = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        try {
            $document->loadHTML('<?xml encoding="UTF-8"><body>' . $html . '</body>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        return new DOMXPath($document);
    }

    /** A choice group is a fieldset whose first child is the legend naming it, and no label points at the group. */
    private function assertChoiceGroupIsAFieldset(string $html, string $groupLabel, int $choices): void
    {
        $xpath = $this->xpath($html);

        $fieldsets = $xpath->query('//fieldset[legend]');
        self::assertSame(1, $fieldsets->length);
        $fieldset = $fieldsets->item(0);
        self::assertSame('legend', $fieldset->firstElementChild?->nodeName);
        self::assertStringContainsString($groupLabel, $fieldset->firstElementChild->textContent);
        self::assertSame($choices, $xpath->query('.//input[@type="radio" or @type="checkbox"]', $fieldset)->length);

        // The legend is not a label, and no label designates the group's first input on the group's behalf.
        self::assertSame(0, $xpath->query('//label[@id="bfLabel48" or @id="bfLabel49"]')->length);
        foreach ($xpath->query('.//label[@for]', $fieldset) as $label) {
            self::assertStringStartsWith('bfGroupLabel', $label->getAttribute('id'));
        }
    }
}
