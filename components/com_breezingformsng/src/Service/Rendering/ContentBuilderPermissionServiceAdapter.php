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

/** Adapts the ContentBuilder permission service to the BFNG rendering contract. */
final class ContentBuilderPermissionServiceAdapter implements ContentBuilderPermissionGateway
{
    /**
     * @param \CB\Component\Contentbuilderng\Administrator\Service\PermissionService $permissionService
     */
    public function __construct(private readonly object $permissionService)
    {
    }

    public function setPermissions(int $formId, int $recordId, string $suffix): void
    {
        call_user_func([$this->permissionService, 'setPermissions'], $formId, $recordId, $suffix);
    }

    public function authorize(string $action): bool
    {
        return (bool) call_user_func([$this->permissionService, 'authorize'], $action);
    }

    public function authorizeFrontend(string $action): bool
    {
        return (bool) call_user_func([$this->permissionService, 'authorizeFe'], $action);
    }

    public function checkPermissions(string $action, string $errorMessage, string $suffix): void
    {
        call_user_func([$this->permissionService, 'checkPermissions'], $action, $errorMessage, $suffix);
    }
}
