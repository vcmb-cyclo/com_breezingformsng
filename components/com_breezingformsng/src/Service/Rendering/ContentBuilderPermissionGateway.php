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

/** Minimal permission contract required by BreezingForms ContentBuilder flow. */
interface ContentBuilderPermissionGateway
{
    public function setPermissions(int $formId, int $recordId, string $suffix): void;

    public function authorize(string $action): bool;

    public function authorizeFrontend(string $action): bool;

    public function checkPermissions(string $action, string $errorMessage, string $suffix): void;
}
