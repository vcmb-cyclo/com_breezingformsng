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

// PHPStan stub, not a real implementation - see ../README.md.

namespace CB\Component\Contentbuilderng\Administrator\types;

class contentbuilderng_com_breezingformsng
{
    /**
     * @return mixed
     */
    public function getAllElements()
    {
    }

    /**
     * @return mixed
     */
    public function getReferenceId()
    {
    }

    /**
     * @param int|string $record_id
     * @return mixed
     */
    public function getRecordMetadata($record_id)
    {
    }

    /**
     * @param int|string $record_id
     * @param bool|int $published_only
     * @param int $own_only
     * @param bool $show_all_languages
     * @return mixed
     */
    public function getRecord(
        $record_id,
        $published_only = false,
        $own_only = -1,
        $show_all_languages = false
    ) {
    }

    /**
     * @return mixed
     */
    public function getElementLabels()
    {
    }

    /**
     * @return mixed
     */
    public function getPageTitle()
    {
    }

    /**
     * @param int|string $record_id
     * @param array<mixed> $cleaned_values
     * @return mixed
     */
    public function saveRecord($record_id, array $cleaned_values)
    {
    }
}
