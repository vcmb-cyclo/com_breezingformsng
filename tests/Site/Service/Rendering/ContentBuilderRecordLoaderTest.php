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

namespace Vcmb\Component\BreezingformsNG\Tests\Site\Service\Rendering;

use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Site\Service\Rendering\ContentBuilderRecordLoader;

final class ContentBuilderRecordLoaderTest extends TestCase
{
    public function testLoadsAFrontendRecordWithItsConfiguredOwnerAndLanguageScope(): void
    {
        $arguments = [];
        $loader = new ContentBuilderRecordLoader(
            static function (
                string $referenceId,
                int $recordId,
                bool $publishedOnly,
                int $ownerId,
                bool $allLanguages
            ) use (&$arguments): array {
                $arguments = [$referenceId, $recordId, $publishedOnly, $ownerId, $allLanguages];
                return ['record'];
            }
        );

        self::assertSame(['record'], $loader->load([
            'reference_id' => 19,
            'published_only' => 1,
            'own_only_fe' => 1,
            'show_all_languages_fe' => 0,
        ], 44, true, 7, false, 'not found'));
        self::assertSame(['19', 44, true, 7, false], $arguments);
    }

    public function testUsesUnrestrictedOwnerForAFrontendFormThatAllowsAllOwners(): void
    {
        $arguments = [];
        $loader = new ContentBuilderRecordLoader(
            static function (
                string $referenceId,
                int $recordId,
                bool $publishedOnly,
                int $ownerId,
                bool $allLanguages
            ) use (&$arguments): array {
                $arguments = [$referenceId, $recordId, $publishedOnly, $ownerId, $allLanguages];
                return ['record'];
            }
        );

        $loader->load(['reference_id' => 19, 'own_only_fe' => 0], 44, true, 7, false, 'not found');

        self::assertSame(['19', 44, false, -1, false], $arguments);
    }

    public function testUsesAdministratorOwnerScopeAndAllLanguages(): void
    {
        $arguments = [];
        $loader = new ContentBuilderRecordLoader(
            static function (
                string $referenceId,
                int $recordId,
                bool $publishedOnly,
                int $ownerId,
                bool $allLanguages
            ) use (&$arguments): array {
                $arguments = [$referenceId, $recordId, $publishedOnly, $ownerId, $allLanguages];
                return ['record'];
            }
        );

        $loader->load([
            'reference_id' => 19,
            'published_only' => 1,
            'own_only' => 1,
        ], 44, false, 7, false, 'not found');

        self::assertSame(['19', 44, true, 7, true], $arguments);
    }

    public function testAllowsAnEmptyRecordOnlyForANewSubmission(): void
    {
        $loader = new ContentBuilderRecordLoader(static fn(): array => []);

        self::assertSame([], $loader->load(['reference_id' => 19], 0, true, 7, true, 'not found'));
        $this->expectExceptionCode(404);
        $this->expectExceptionMessage('not found');
        $loader->load(['reference_id' => 19], 44, true, 7, false, 'not found');
    }
}
