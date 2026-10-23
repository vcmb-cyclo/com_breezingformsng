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

// PHPStan symbol for Joomla's com_privacy, outside core libraries.

namespace Joomla\Component\Privacy\Administrator\Plugin;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Component\Privacy\Administrator\Export\Domain;
use Joomla\Component\Privacy\Administrator\Export\Item;
use Joomla\Database\DatabaseAwareTrait;

abstract class PrivacyPlugin extends CMSPlugin
{
    use DatabaseAwareTrait;

    /** @return Domain */
    protected function createDomain($name, $description = '')
    {
        return new Domain();
    }

    /**
     * @param array<string, mixed> $data
     * @return Item
     */
    protected function createItemFromArray(array $data, $itemId = null)
    {
        return new Item();
    }
}
