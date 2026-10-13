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

namespace Vcmb\Component\BreezingformsNG\Site\Service\Rendering;

use Exception;
use Closure;

/** Coordinates ContentBuilder permission checks for a rendering request. */
final class ContentBuilderPermissionChecker
{
    /**
     * @param Closure(): ContentBuilderPermissionGateway $permissionServiceFactory
     */
    public function __construct(private readonly Closure $permissionServiceFactory)
    {
    }

    /**
     * @param array<int, mixed> $formIds
     */
    public function assertCanCreate(
        array $formIds,
        bool $frontend,
        string $frontendError,
        string $administratorError
    ): void {
        $permissionService = ($this->permissionServiceFactory)();
        $authorized = true;

        foreach ($formIds as $formId) {
            $permissionService->setPermissions((int) $formId, 0, $frontend ? '_fe' : '');
            $authorized = $frontend
                ? $permissionService->authorizeFrontend('new')
                : $permissionService->authorize('new');
            if (!$authorized) {
                break;
            }
        }

        if ($formIds !== [] && !$authorized) {
            throw new Exception($frontend ? $frontendError : $administratorError, 403);
        }
    }

    public function assertCanEditOrCreate(
        int $formId,
        int $recordId,
        bool $frontend,
        string $editError,
        string $createError
    ): ContentBuilderPermissionGateway {
        $permissionService = ($this->permissionServiceFactory)();
        $suffix = $frontend ? '_fe' : '';

        if ($recordId) {
            $permissionService->setPermissions($formId, $recordId, $suffix);
            $permissionService->checkPermissions('edit', $editError, $suffix);
        } else {
            $permissionService->setPermissions($formId, 0, $suffix);
            $permissionService->checkPermissions('new', $createError, $suffix);
        }

        return $permissionService;
    }

    public function canViewFullArticle(ContentBuilderPermissionGateway $permissionService, bool $frontend): bool
    {
        return $frontend
            ? $permissionService->authorizeFrontend('fullarticle')
            : $permissionService->authorize('fullarticle');
    }
}
