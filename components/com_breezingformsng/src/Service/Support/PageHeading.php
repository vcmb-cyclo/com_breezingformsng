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

namespace Vcmb\Component\BreezingformsNG\Site\Service\Support;

\defined('_JEXEC') or die;

/** Builds the escaped page heading shown above an inline form. */
final class PageHeading
{
    public static function html(string $menuTitle, string $menuPageHeading, string $formTitle): string
    {
        $heading = $menuTitle !== ''
            ? ($menuPageHeading !== '' ? $menuPageHeading : $menuTitle)
            : $formTitle;

        return '<h1>' . htmlspecialchars($heading, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</h1>';
    }
}
