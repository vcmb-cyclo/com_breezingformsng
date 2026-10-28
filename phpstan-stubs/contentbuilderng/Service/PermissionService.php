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

// PHPStan stub, not a real implementation - see ../README.md.

namespace CB\Component\Contentbuilderng\Administrator\Service;

class PermissionService
{
    public static function createFromRuntimeContext(): self
    {
        return new self();
    }

    /**
     * @param int|string $formId
     * @param int|string $recordId
     */
    public function setPermissions($formId, $recordId = 0, string $suffix = ''): void
    {
    }

    /**
     * @param string $action
     * @param string $errorMsg
     */
    public function checkPermissions($action, $errorMsg, string $suffix = '', bool $auth = false)
    {
    }

    /**
     * @param string $action
     */
    public function authorize($action): bool
    {
        return false;
    }

    /**
     * @param string $action
     */
    public function authorizeFe($action): bool
    {
        return false;
    }
}
