<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Administrator\Service;

\defined('_JEXEC') or die;

final class LegacyFormConversionException extends \DomainException
{
    public function __construct(
        string $reason,
        public readonly int $elementId,
        public readonly string $elementName,
        public readonly string $elementType,
    ) {
        parent::__construct($reason);
    }
}
