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
 * Minimal stub for Joomla\CMS\Uri\Uri, for renderer characterization tests
 * only. root()/base() normally resolve against the live request; here they
 * return a fixed fake site root so URLs embedded in snapshots stay stable
 * from one test run to the next regardless of environment.
 *
 * Shared across renderer characterization test files; each one
 * require_once's this conditionally, so it only gets declared once.
 */

namespace Joomla\CMS\Uri;

if (!class_exists(Uri::class, false)) {
    final class Uri
    {
        public static string $currentUrl = 'http://example.test/form';

        public static function getInstance(): self
        {
            return new self();
        }

        public function toString(): string
        {
            return self::$currentUrl;
        }

        public static function root(bool $pathOnly = false): string
        {
            return $pathOnly ? '' : 'http://example.test/';
        }

        public static function base(bool $pathOnly = false): string
        {
            return $pathOnly ? '' : 'http://example.test/';
        }
    }
}
