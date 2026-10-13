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

class ListSupportService
{
    public static function createFromRuntimeContext(): self
    {
        return new self();
    }

    /**
     * @return array<mixed>
     */
    public function getListNonEditableElements(int $formId): array
    {
        return [];
    }
}
