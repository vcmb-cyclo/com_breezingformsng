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

namespace Vcmb\Component\BreezingformsNG\Site\Service\Callback;

\defined('_JEXEC') or die;

/**
 * The page a double opt-in link opens. Opening a link must not change anything, for a mail client may open it
 * before the reader does: the page only holds a form whose button, pressed by a person, posts the change.
 * It says nothing about the record, so that it is the same for every well-formed link.
 */
final class OptConfirmationPage
{
    public const IN  = 'opt_in';
    public const OUT = 'opt_out';

    /**
     * @param string $mode       self::IN or self::OUT
     * @param string $action     URL the form posts to
     * @param string $csrfField  the hidden token field of Joomla, already built (HTMLHelper form.token)
     */
    public function render(string $mode, string $action, string $recordId, string $token, string $text, string $button, string $csrfField): string
    {
        $mode = $mode === self::OUT ? self::OUT : self::IN;

        return '<form action="' . $this->escape($action) . '" method="post" class="bf-opt-confirmation" aria-labelledby="bf-opt-text">'
            . '<p id="bf-opt-text">' . $this->escape($text) . '</p>'
            . '<input type="hidden" name="' . $mode . '" value="true">'
            . '<input type="hidden" name="id" value="' . $this->escape($recordId) . '">'
            . '<input type="hidden" name="token" value="' . $this->escape($token) . '">'
            . $csrfField
            . '<button type="submit" class="btn btn-primary">' . $this->escape($button) . '</button>'
            . '</form>';
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
    }
}
