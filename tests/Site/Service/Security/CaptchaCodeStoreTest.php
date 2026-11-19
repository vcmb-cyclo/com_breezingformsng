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
use Vcmb\Component\BreezingformsNG\Site\Service\Security\CaptchaCodeStore;

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

/**
 * The browser checks the captcha code before sending the form and the
 * submission checks it again: only the submission may use it up.
 */
final class CaptchaCodeStoreTest extends TestCase
{
    /** @var array<string, mixed> */
    private array $data = [];

    private function store(): CaptchaCodeStore
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

        return new CaptchaCodeStore($session);
    }

    public function testTheBrowserCheckLeavesTheCodeForTheSubmission(): void
    {
        $store = $this->store();
        $store->store('bombes');

        self::assertTrue($store->matches('bombes', false));
        self::assertTrue($store->matches(' BOMBES ', false), 'Codes are not case-sensitive.');
        self::assertTrue($store->matches('bombes', true));
        self::assertFalse($store->matches('bombes', true), 'The submission uses the code up.');
    }

    public function testAWrongCodeIsRefusedAndKept(): void
    {
        $store = $this->store();
        $store->store('bombes');

        self::assertFalse($store->matches('bombe', true));
        self::assertFalse($store->matches('', true));
        self::assertTrue($store->matches('bombes', true));
    }

    public function testTooManyWrongEntriesDropTheCode(): void
    {
        $store = $this->store();
        $store->store('bombes');

        for ($attempt = 1; $attempt <= CaptchaCodeStore::MAX_ATTEMPTS; $attempt++) {
            self::assertFalse($store->matches('wrong', false));
        }

        self::assertNull($store->get());
        self::assertFalse($store->matches('bombes', true), 'The right code no longer works once the code is dropped.');
    }

    public function testNothingMatchesWithoutAShownImage(): void
    {
        self::assertFalse($this->store()->matches('', false));
        self::assertFalse($this->store()->matches('anything', true));
    }

    public function testAnExpiredCodeIsRefusedAndCleared(): void
    {
        $store = $this->store();
        $store->store('bombes');
        $key = array_key_first($this->data);
        self::assertIsString($key);
        $this->data[$key]['time'] = time() - CaptchaCodeStore::LIFETIME;

        self::assertFalse($store->matches('bombes', false));
        self::assertNull($store->get());
    }

    public function testAMultiWordCodeIgnoresRepeatedSpaces(): void
    {
        $store = $this->store();
        $store->store('blue sky');

        self::assertTrue($store->matches("blue   sky", false));
    }
}
