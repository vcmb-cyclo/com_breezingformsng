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

namespace Vcmb\Component\BreezingformsNG\Tests\Site\Service\Security;

use Joomla\Session\SessionInterface;
use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Site\Service\Security\RobotCheckStore;

if (!interface_exists(SessionInterface::class)) {
    eval(
        'namespace Joomla\\Session;'
        . 'interface SessionInterface {'
        . ' public function get($name, $default = null);'
        . ' public function set($name, $value = null);'
        . ' public function remove($name);'
        . '}'
    );
}

/** A challenge of the anti-robot check is answered once, in its session and in time. */
final class RobotCheckStoreTest extends TestCase
{
    /** @var array<string, mixed> */
    private array $data = [];

    /** @param array{salt: string, target: string, max: int} $challenge */
    private function solve(array $challenge): string
    {
        for ($number = 0; $number <= $challenge['max']; $number++) {
            if (hash('sha256', $challenge['salt'] . $number) === $challenge['target']) {
                return $challenge['salt'] . ':' . $number;
            }
        }

        self::fail('The challenge has no answer.');
    }

    public function testASolvedChallengeServesOnce(): void
    {
        $store = $this->store();
        $first = $store->issue(1000);
        $second = $store->issue(1000);
        $answer = $this->solve($first);

        self::assertTrue($store->accept($answer, 1001));
        self::assertFalse($store->accept($answer, 1002));
        // Another tab keeps its own challenge.
        self::assertTrue($store->accept($this->solve($second), 1003));
    }

    public function testAWrongLateOrForeignAnswerIsRefused(): void
    {
        $store = $this->store();
        $challenge = $store->issue(1000);
        $answer = $this->solve($challenge);
        $number = (int) explode(':', $answer)[1];

        foreach (['', 'yes', $challenge['salt'] . ':' . ($number + 1), str_repeat('a', 32) . ':' . $number] as $wrong) {
            self::assertFalse($store->accept($wrong, 1001), $wrong);
        }

        self::assertFalse($store->accept($answer, 1000 + RobotCheckStore::LIFETIME));
    }

    public function testOnlyTheLastChallengesAreKept(): void
    {
        $store = $this->store();
        $oldest = $store->issue(1000);

        for ($i = 0; $i < RobotCheckStore::KEPT; $i++) {
            $store->issue(1000);
        }

        self::assertFalse($store->accept($this->solve($oldest), 1001));
    }

    private function store(): RobotCheckStore
    {
        $data = &$this->data;
        $session = new class ($data) implements SessionInterface {
            /** @param array<string, mixed> $data */
            public function __construct(private array &$data)
            {
            }

            public function get($name, $default = null)
            {
                return $this->data[$name] ?? $default;
            }

            public function set($name, $value = null)
            {
                $previous = $this->data[$name] ?? null;
                $this->data[$name] = $value;

                return $previous;
            }

            public function remove($name)
            {
                $previous = $this->data[$name] ?? null;
                unset($this->data[$name]);

                return $previous;
            }
        };

        return new RobotCheckStore($session);
    }
}
