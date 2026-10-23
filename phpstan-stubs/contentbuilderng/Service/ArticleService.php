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

class ArticleService
{
    /**
     * @param array<mixed> $record
     * @param array<mixed> $elementsAllowed
     * @param array<mixed> $config
     * @return int|string Existing article IDs can retain the database driver's string type.
     */
    public function createArticle(
        int $contentbuilderngFormId,
        int $recordId,
        array $record,
        array $elementsAllowed,
        $titleField = '',
        $metadata = null,
        $config = [],
        bool $full = false,
        bool $limitedOptions = true,
        $menuCatId = null
    ): int|string {
        return 0;
    }
}
