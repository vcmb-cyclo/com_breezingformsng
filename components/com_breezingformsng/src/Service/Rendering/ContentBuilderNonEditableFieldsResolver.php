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

use Closure;

/** Resolves the ContentBuilder fields that must remain non-editable. */
final class ContentBuilderNonEditableFieldsResolver
{
    /**
     * @param Closure(int): array<int|string> $loadFields
     */
    public function __construct(private readonly Closure $loadFields)
    {
    }

    /**
     * @return array<int|string>
     */
    public function resolve(int $contentBuilderId): array
    {
        return ($this->loadFields)($contentBuilderId);
    }
}
