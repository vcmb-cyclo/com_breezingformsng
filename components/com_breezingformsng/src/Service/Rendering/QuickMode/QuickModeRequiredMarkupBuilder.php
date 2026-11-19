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

namespace Vcmb\Component\BreezingformsNG\Site\Service\Rendering\QuickMode;

use Joomla\CMS\Language\Text;

/**
 * Exposes the "required" flag of a QuickMode element to assistive technology.
 *
 * The visual asterisk is hidden from screen readers and replaced by a visually hidden text;
 * supporting controls also get aria-required (never the native required attribute, so the
 * built-in client-side validation and its aria-invalid/aria-describedby handling stay in charge).
 */
final class QuickModeRequiredMarkupBuilder
{
    /** Element types whose control accepts aria-required. Radio and checkbox groups are labelled by the hidden text instead. */
    private const ARIA_REQUIRED_TYPES = [
        'bfTextfield',
        'bfTextarea',
        'bfNumberInput',
        'bfSelect',
        'bfCheckbox',
        'bfCalendar',
        'bfCalendarResponsive',
        'bfFile',
    ];

    /** @param string $asteriskHtml The decorative asterisk element, without hidden-text markup. */
    public function marker(string $asteriskHtml): string
    {
        return $asteriskHtml . '<span class="visually-hidden">'
            . htmlspecialchars(Text::_('JREQUIRED'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            . '</span> ' . "\n";
    }

    public function ariaRequiredAttribute(string $type, bool $required): string
    {
        return $required && in_array($type, self::ARIA_REQUIRED_TYPES, true) ? 'aria-required="true" ' : '';
    }
}
