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

/**
 * Data returned when an editable BreezingForms record is found.
 */
final class EditableRecord
{
    /**
     * @param list<\stdClass> $entries
     */
    public function __construct(
        public readonly int $id,
        public readonly array $entries
    ) {
    }
}
