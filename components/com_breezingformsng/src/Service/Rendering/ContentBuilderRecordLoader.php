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
use Exception;

/** Loads a BreezingForms record through a ContentBuilder form definition. */
final class ContentBuilderRecordLoader
{
    /**
     * @param Closure(string, int, bool, int, bool): array<int, mixed> $recordLoader
     */
    public function __construct(private readonly Closure $recordLoader)
    {
    }

    /**
     * @param array<string, mixed> $formData
     * @return array<int, mixed>
     */
    public function load(
        array $formData,
        int $recordId,
        bool $frontend,
        int $userId,
        bool $isNew,
        string $notFoundError
    ): array {
        $ownOnly = $frontend ? (bool) ($formData['own_only_fe'] ?? false) : (bool) ($formData['own_only'] ?? false);
        $ownerId = $ownOnly ? $userId : -1;
        $publishedOnly = (bool) ($formData['published_only'] ?? false);
        $showAllLanguages = $frontend ? (bool) ($formData['show_all_languages_fe'] ?? false) : true;
        $record = ($this->recordLoader)(
            (string) $formData['reference_id'],
            $recordId,
            $publishedOnly,
            $ownerId,
            $showAllLanguages
        );

        if ($record === [] && !$isNew) {
            throw new Exception($notFoundError, 404);
        }

        return $record;
    }
}
