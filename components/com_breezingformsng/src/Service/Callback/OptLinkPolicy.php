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
 * Tells whether the link of a double opt-in e-mail can name a record. A
 * record that was not saved with a token holds an empty one: an empty token
 * in the link must never match it.
 */
final class OptLinkPolicy
{
    /** The tokens are made of letters and digits, 20 of them: a shorter one is not one of ours. */
    private const TOKEN = '/^[A-Za-z0-9]{16,64}$/';

    public function isUsable(string $recordId, string $token): bool
    {
        return preg_match('/^[1-9][0-9]{0,18}$/', $recordId) === 1
            && preg_match(self::TOKEN, $token) === 1;
    }
}
