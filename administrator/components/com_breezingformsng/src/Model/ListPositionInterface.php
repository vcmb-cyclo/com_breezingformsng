<?php

/**
 * BreezingForms NG - A Joomla Forms Application
 *
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 *
 * SPDX-License-Identifier: GPL-2.0-or-later
 **/

namespace Vcmb\Component\BreezingformsNG\Administrator\Model;

\defined('_JEXEC') or die;

/**
 * A list model whose items an item screen pages through (ListPositionTrait).
 */
interface ListPositionInterface
{
    /**
     * @return array{position: int, total: int}|null
     */
    public function getPosition(int $itemId): ?array;

    public function getIdAt(int $offset): ?int;
}
