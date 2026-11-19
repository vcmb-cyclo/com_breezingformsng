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
    /** Seconds a code stays valid. */
    public const LIFETIME = 900;

    /** Wrong entries tolerated before the code is dropped and a new image is needed. */
    public const MAX_ATTEMPTS = 5;

    /** Shortest pause, in seconds, between two images issued to the same session. */
    public const ISSUE_INTERVAL = 0.5;

    /** Images a session may be issued within ISSUE_WINDOW seconds. */
    public const ISSUE_LIMIT = 30;

    /** Length, in seconds, of the window the issue limit applies to. */
    public const ISSUE_WINDOW = 600;

    private const KEY = 'com_breezingformsng.captcha';

    private const ISSUE_KEY = 'com_breezingformsng.captcha_issues';

    public function __construct(private readonly SessionInterface $session)
    {
    }

    public function store(string $code): void
    {
        $this->session->set(self::KEY, ['code' => $code, 'time' => time(), 'attempts' => 0]);
    }

    /**
     * Whether a new image may be issued to this session now; when it may, the issue is recorded.
     * A refused request is not recorded, so a visitor is served again once the pause or window has passed.
     */
    public function allowIssue(?float $now = null): bool
    {
        $now ??= microtime(true);
        $state = $this->session->get(self::ISSUE_KEY);
        $last = is_array($state) && is_numeric($state['last'] ?? null) ? (float) $state['last'] : 0.0;
        $times = [];

        if (is_array($state) && is_array($state['times'] ?? null)) {
            foreach ($state['times'] as $time) {
                if ((is_float($time) || is_int($time)) && $now - $time < self::ISSUE_WINDOW) {
                    $times[] = (float) $time;
                }
            }
        }

        if (($last > 0.0 && $now - $last < self::ISSUE_INTERVAL) || count($times) >= self::ISSUE_LIMIT) {
            return false;
        }

        $times[] = $now;
        $this->session->set(self::ISSUE_KEY, ['last' => $now, 'times' => $times]);

        return true;
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
            // The code cannot be guessed by trying: too many wrong entries drop it.
            $attempts = (int) ($this->session->get(self::KEY)['attempts'] ?? 0) + 1;
            if ($attempts >= self::MAX_ATTEMPTS) {
                $this->clear();
            } else {
                $this->session->set(self::KEY, ['code' => $stored['code'], 'time' => $stored['time'], 'attempts' => $attempts]);
            }

            return false;
        }

        if ($consume) {
            $this->clear();
        }

        return true;
    }

    /** A code is typed without regard to case or to the spaces around it. */
    private static function normalise(string $code): string
    {
        return strtolower(trim((string) preg_replace('/\s+/', ' ', $code)));
    }
}
