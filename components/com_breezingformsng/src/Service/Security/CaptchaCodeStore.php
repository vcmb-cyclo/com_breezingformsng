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

namespace Vcmb\Component\BreezingformsNG\Site\Service\Security;

\defined('_JEXEC') or die;

use Joomla\Session\SessionInterface;

/**
 * The code of the captcha image last shown to the visitor, kept in the Joomla
 * session as plain values. The browser checks it once before sending the form
 * and the submission checks it again: only the submission uses it up.
 */
final class CaptchaCodeStore
{
    /** Captcha id given to Securimage: the session already keeps visitors apart. */
    public const CAPTCHA_ID = 'bfng';

    /** Seconds a code stays valid, as Securimage's default expiry time. */
    public const LIFETIME = 900;

    private const KEY = 'com_breezingformsng.captcha';

    public function __construct(private readonly SessionInterface $session)
    {
    }

    public function store(string $code): void
    {
        $this->session->set(self::KEY, ['code' => $code, 'time' => time()]);
    }

    /** @return array{code: string, time: int}|null */
    public function get(): ?array
    {
        $stored = $this->session->get(self::KEY);

        if (!is_array($stored) || !is_string($stored['code'] ?? null) || !is_int($stored['time'] ?? null)) {
            return null;
        }

        return ['code' => $stored['code'], 'time' => $stored['time']];
    }

    public function clear(): void
    {
        $this->session->remove(self::KEY);
    }

    /** Whether the code typed matches the image; a match uses the code up when $consume is true. */
    public function matches(string $entered, bool $consume): bool
    {
        $stored = $this->get();

        if ($stored === null) {
            return false;
        }

        if (time() - $stored['time'] >= self::LIFETIME) {
            $this->clear();

            return false;
        }

        $code = self::normalise($stored['code']);

        if ($code === '' || !hash_equals($code, self::normalise($entered))) {
            return false;
        }

        if ($consume) {
            $this->clear();
        }

        return true;
    }

    /** Securimage codes are not case-sensitive; a word list code may hold spaces. */
    private static function normalise(string $code): string
    {
        return strtolower(trim((string) preg_replace('/\s+/', ' ', $code)));
    }
}
