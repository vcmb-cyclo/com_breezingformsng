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
 * Minimal stub for Joomla\CMS\Application\CMSApplication (plus the small
 * slice of Document/WebAssetManager/Input/Language it exposes), for renderer
 * characterization tests only. RuntimeAssetLoader::script()/style() and
 * several renderer field types type-hint or call methods on the real
 * CMSApplication - none of which exists in this pure-logic test bootstrap.
 *
 * All asset-registration methods are no-ops: what a live WebAssetManager
 * does with a registered script/style isn't observable in a snapshot of
 * process()'s echoed HTML, so faithfully reproducing it here would add
 * complexity without adding coverage. Configure the handful of behaviors
 * that DO affect output (isClient, the language tag, config values) via the
 * public properties before calling process().
 *
 * Shared across renderer characterization test files; each one
 * require_once's this conditionally, so it only gets declared once.
 */

namespace Joomla\CMS\Application {
    if (!class_exists(CMSApplication::class, false)) {
        class CMSApplication
        {
            public bool $isAdministrator = false;
            public string $languageTag = 'en-GB';

            /** @var array<string, mixed> */
            public array $config = [];

            private ?FakeWebAssetManager $webAssetManager = null;
            private ?FakeInput $input = null;
            private ?FakeSession $session = null;

            public function isClient(string $client): bool
            {
                return $client === 'administrator' ? $this->isAdministrator : !$this->isAdministrator;
            }

            public function get(string $key, mixed $default = null): mixed
            {
                return $this->config[$key] ?? $default;
            }

            public function getConfig(): FakeRegistry
            {
                return new FakeRegistry($this->config);
            }

            public function getLanguage(): FakeLanguage
            {
                return new FakeLanguage($this->languageTag);
            }

            public function getDocument(): FakeDocument
            {
                return new FakeDocument($this->webAssetManager ??= new FakeWebAssetManager());
            }

            public function getInput(): FakeInput
            {
                return $this->input ??= new FakeInput();
            }

            public function getSession(): FakeSession
            {
                return $this->session ??= new FakeSession();
            }

            public function setTitle(string $title): void
            {
            }
        }
    }

    if (!class_exists(FakeRegistry::class, false)) {
        final class FakeRegistry
        {
            /**
             * @param array<string, mixed> $values
             */
            public function __construct(private readonly array $values)
            {
            }

            public function get(string $key, mixed $default = null): mixed
            {
                return $this->values[$key] ?? $default;
            }
        }
    }

    if (!class_exists(FakeLanguage::class, false)) {
        final class FakeLanguage
        {
            public function __construct(private readonly string $tag)
            {
            }

            public function getTag(): string
            {
                return $this->tag;
            }
        }
    }

    if (!class_exists(FakeWebAssetManager::class, false)) {
        final class FakeWebAssetManager
        {
            /** @var array<string, true> */
            private array $registered = [];

            public function assetExists(string $type, string $name): bool
            {
                return isset($this->registered[$type . ':' . $name]);
            }

            public function registerScript(string $name, string $uri, array $options = [], array $attributes = []): static
            {
                $this->registered['script:' . $name] = true;

                return $this;
            }

            public function registerStyle(string $name, string $uri, array $options = [], array $attributes = []): static
            {
                $this->registered['style:' . $name] = true;

                return $this;
            }

            public function useScript(string $name): static
            {
                return $this;
            }

            public function useStyle(string $name): static
            {
                return $this;
            }

            public function addInlineScript(string $script): static
            {
                return $this;
            }

            public function addInlineStyle(string $style): static
            {
                return $this;
            }
        }
    }

    if (!class_exists(FakeDocument::class, false)) {
        final class FakeDocument
        {
            public function __construct(private readonly FakeWebAssetManager $webAssetManager)
            {
            }

            public function getWebAssetManager(): FakeWebAssetManager
            {
                return $this->webAssetManager;
            }

            public function setTitle(string $title): void
            {
            }

            public function loadRenderer(string $type): object
            {
                return new class {
                    public function render(mixed $position, array $options = [], mixed $params = null): string
                    {
                        return '';
                    }
                };
            }
        }
    }

    if (!class_exists(FakeInput::class, false)) {
        final class FakeInput
        {
            /** @var array<string, mixed> */
            public array $values = [];
            public FakeServer $server;

            public function __construct()
            {
                $this->server = new FakeServer();
            }

            public function get(string $name, mixed $default = null, string $filter = 'cmd'): mixed
            {
                return $this->values[$name] ?? $default;
            }

            public function getInt(string $name, int $default = 0): int
            {
                return (int) ($this->values[$name] ?? $default);
            }

            public function getString(string $name, string $default = ''): string
            {
                return (string) ($this->values[$name] ?? $default);
            }

            public function getCmd(string $name, string $default = ''): string
            {
                return (string) ($this->values[$name] ?? $default);
            }

            public function getWord(string $name, string $default = ''): string
            {
                return (string) ($this->values[$name] ?? $default);
            }

            public function getBool(string $name, bool $default = false): bool
            {
                return (bool) ($this->values[$name] ?? $default);
            }

            public function set(string $name, mixed $value): void
            {
                $this->values[$name] = $value;
            }
        }
    }

    if (!class_exists(FakeServer::class, false)) {
        final class FakeServer
        {
            /** @var array<string, mixed> */
            public array $values = [];

            public function getString(string $name, string $default = ''): string
            {
                return (string) ($this->values[$name] ?? $default);
            }

            public function getInt(string $name, int $default = 0): int
            {
                return (int) ($this->values[$name] ?? $default);
            }
        }
    }

    if (!class_exists(FakeSession::class, false)) {
        final class FakeSession
        {
            /** @var array<string, mixed> */
            public array $values = [];

            public function get(string $name, mixed $default = null): mixed
            {
                return $this->values[$name] ?? $default;
            }

            public function set(string $name, mixed $value): void
            {
                $this->values[$name] = $value;
            }

            public function clear(string $name): void
            {
                unset($this->values[$name]);
            }
        }
    }
}
