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

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Site\Service\Submission;

\defined('_JEXEC') or die;

/** What a signature field may write to the signatures folder: a PNG of reasonable size. */
final class SignatureImagePolicy
{
    public const MAX_BYTES = 1048576;

    private const PNG_HEADER = "\x89PNG\r\n\x1a\n";

    public function isStorable(string $decoded): bool
    {
        return $decoded !== ''
            && strlen($decoded) <= self::MAX_BYTES
            && str_starts_with($decoded, self::PNG_HEADER);
    }
}
