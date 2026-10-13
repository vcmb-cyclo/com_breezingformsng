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

/**
 * Minimal stub for Joomla\CMS\HTML\HTMLHelper, for renderer characterization
 * tests only. The real class dispatches to registered "behaviors" (JS/CSS
 * assets pushed onto the live WebAssetManager, or - for keys like
 * "calendar" - real widget HTML built from a template) - none of the
 * asset-loading side effects are observable in a snapshot of process()'s
 * echoed HTML, and reproducing the real widget markup isn't worth the
 * complexity here.
 *
 * For a key whose return value IS echoed directly into the page (e.g.
 * HTMLHelper::_('calendar', ...) in ClassicRenderer's bfCalendar case), a
 * silent '' would hide a real regression in the arguments being built - so
 * this instead returns an HTML comment encoding the key and every argument
 * passed, keeping it visible (and diffable) in the snapshot. For call sites
 * that discard the return value (e.g. HTMLHelper::_('bootstrap.tooltip',
 * '.hasTooltip')), that comment is never rendered and is harmless.
 *
 * Shared across renderer characterization test files; each one
 * require_once's this conditionally, so it only gets declared once.
 */

namespace Joomla\CMS\HTML;

if (!class_exists(HTMLHelper::class, false)) {
    final class HTMLHelper
    {
        public static function _(string $key, mixed ...$args): mixed
        {
            return '<!-- HTMLHelper::_(' . json_encode([$key, ...$args], JSON_UNESCAPED_SLASHES) . ') -->';
        }
    }
}
