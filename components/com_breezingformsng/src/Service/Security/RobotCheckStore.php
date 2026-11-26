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

namespace Vcmb\Component\BreezingformsNG\Site\Service\Security;

\defined('_JEXEC') or die;

use Joomla\Session\SessionInterface;

/**
 * Anti-robot check without a third-party service, on the proof-of-work principle: the page gets a
 * salt and the SHA-256 of that salt followed by a secret number; the browser searches the number
 * and sends it back with the form. Each challenge is kept in the session and serves once.
 */
final class RobotCheckStore
{
    /** Largest secret number: a browser finds it in a fraction of a second, a flood of sends pays each time. */
    public const MAX_NUMBER = 60000;

    /** Seconds a challenge stays valid. */
    public const LIFETIME = 3600;

    /** Challenges kept for one session, so that several tabs work. */
    public const KEPT = 10;

    private const KEY = 'com_breezingformsng.robot';

    public function __construct(private readonly SessionInterface $session)
    {
    }

    /** @return array{salt: string, target: string, max: int} */
    public function issue(?int $now = null): array
    {
        $salt = bin2hex(random_bytes(16));
        $target = hash('sha256', $salt . random_int(0, self::MAX_NUMBER));
        $challenges = $this->challenges($now ?? time());
        $challenges[$salt] = ['target' => $target, 'time' => $now ?? time()];
        $this->session->set(self::KEY, \array_slice($challenges, -self::KEPT, null, true));

        return ['salt' => $salt, 'target' => $target, 'max' => self::MAX_NUMBER];
    }

    /** Whether the answer "salt:number" solves a pending challenge, which is then used up. */
    public function accept(string $answer, ?int $now = null): bool
    {
        if (preg_match('/^([0-9a-f]{32}):(\d{1,6})\z/', $answer, $parts) !== 1) {
            return false;
        }

        $challenges = $this->challenges($now ?? time());
        $target = $challenges[$parts[1]]['target'] ?? null;

        if (!\is_string($target) || !hash_equals($target, hash('sha256', $parts[1] . $parts[2]))) {
            return false;
        }

        unset($challenges[$parts[1]]);
        $this->session->set(self::KEY, $challenges);

        return true;
    }

    /** @return array<string, array{target: string, time: int}> the challenges still valid */
    private function challenges(int $now): array
    {
        $stored = $this->session->get(self::KEY);
        $valid = [];

        foreach (\is_array($stored) ? $stored : [] as $salt => $challenge) {
            $target = \is_array($challenge) ? ($challenge['target'] ?? null) : null;
            $time = \is_array($challenge) ? ($challenge['time'] ?? null) : null;

            if (\is_string($target) && \is_int($time) && $now - $time < self::LIFETIME) {
                $valid[(string) $salt] = ['target' => $target, 'time' => $time];
            }
        }

        return $valid;
    }
}
