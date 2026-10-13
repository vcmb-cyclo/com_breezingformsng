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
use Vcmb\Component\BreezingformsNG\Site\Service\Rendering\ContentBuilderPermissionChecker;
use Vcmb\Component\BreezingformsNG\Site\Service\Rendering\ContentBuilderPermissionGateway;
use Vcmb\Component\BreezingformsNG\Site\Service\Rendering\ContentBuilderPermissionServiceAdapter;

final class ContentBuilderPermissionCheckerTest extends TestCase
{
    public function testPermissionAdapterForwardsEveryOperation(): void
    {
        $service = new class {
            /** @var list<array{string, list<mixed>}> */
            public array $calls = [];

            public function setPermissions(int $formId, int $recordId, string $suffix): void
            {
                $this->calls[] = ['setPermissions', [$formId, $recordId, $suffix]];
            }

            public function authorize(string $action): bool
            {
                $this->calls[] = ['authorize', [$action]];

                return true;
            }

            public function authorizeFe(string $action): bool
            {
                $this->calls[] = ['authorizeFe', [$action]];

                return true;
            }

            public function checkPermissions(string $action, string $message, string $suffix): void
            {
                $this->calls[] = ['checkPermissions', [$action, $message, $suffix]];
            }
        };

        $adapter = new ContentBuilderPermissionServiceAdapter($service);

        $adapter->setPermissions(12, 44, '_fe');
        self::assertTrue($adapter->authorize('new'));
        self::assertTrue($adapter->authorizeFrontend('fullarticle'));
        $adapter->checkPermissions('edit', 'denied', '_fe');

        self::assertSame([
            ['setPermissions', [12, 44, '_fe']],
            ['authorize', ['new']],
            ['authorizeFe', ['fullarticle']],
            ['checkPermissions', ['edit', 'denied', '_fe']],
        ], $service->calls);
    }

    public function testAuthorizesEveryFrontendFormForNewRecords(): void
    {
        $permission = $this->createMock(ContentBuilderPermissionGateway::class);
        $calls = [];
        $permission->expects(self::exactly(2))->method('setPermissions')->willReturnCallback(
            static function (int $formId, int $recordId, string $suffix) use (&$calls): void {
                $calls[] = [$formId, $recordId, $suffix];
            }
        );
        $permission->expects(self::exactly(2))->method('authorizeFrontend')->with('new')->willReturn(true);

        (new ContentBuilderPermissionChecker(static fn(): ContentBuilderPermissionGateway => $permission))
            ->assertCanCreate([12, 13], true, 'frontend', 'administrator');

        self::assertSame([[12, 0, '_fe'], [13, 0, '_fe']], $calls);
    }

    public function testStopsAtTheFirstUnauthorizedAdministratorForm(): void
    {
        $permission = $this->createMock(ContentBuilderPermissionGateway::class);
        $permission->expects(self::once())->method('setPermissions')->with(12, 0, '');
        $permission->expects(self::once())->method('authorize')->with('new')->willReturn(false);

        $this->expectExceptionCode(403);
        $this->expectExceptionMessage('administrator');

        (new ContentBuilderPermissionChecker(static fn(): ContentBuilderPermissionGateway => $permission))
            ->assertCanCreate([12, 13], false, 'frontend', 'administrator');
    }

    public function testChecksAnExistingFrontendRecord(): void
    {
        $permission = $this->createMock(ContentBuilderPermissionGateway::class);
        $permission->expects(self::once())->method('setPermissions')->with(12, 44, '_fe');
        $permission->expects(self::once())->method('checkPermissions')->with('edit', 'edit denied', '_fe');

        $returned = (new ContentBuilderPermissionChecker(static fn(): ContentBuilderPermissionGateway => $permission))
            ->assertCanEditOrCreate(12, 44, true, 'edit denied', 'create denied');

        self::assertSame($permission, $returned);
    }

    public function testChecksANewAdministratorRecordAndFullArticleAccess(): void
    {
        $permission = $this->createMock(ContentBuilderPermissionGateway::class);
        $permission->expects(self::once())->method('setPermissions')->with(12, 0, '');
        $permission->expects(self::once())->method('checkPermissions')->with('new', 'create denied', '');
        $permission->expects(self::once())->method('authorize')->with('fullarticle')->willReturn(true);

        $checker = new ContentBuilderPermissionChecker(static fn(): ContentBuilderPermissionGateway => $permission);

        self::assertTrue($checker->canViewFullArticle(
            $checker->assertCanEditOrCreate(12, 0, false, 'edit denied', 'create denied'),
            false
        ));
    }
}
