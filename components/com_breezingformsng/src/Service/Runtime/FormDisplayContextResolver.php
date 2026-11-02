<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Site\Service\Runtime;

\defined('_JEXEC') or die;

final class FormDisplayContextResolver
{
    // Value the form's run-mode option stored for "Backend only". The installer
    // unpublishes these forms; the site keeps refusing the value so a row it
    // could not convert never becomes public.
    private const FORM_OPTION_BACKEND_ONLY = 1;

    public function resolve(
        int $formId,
        int $formRunMode,
        bool $published,
        string $siteUrl
    ): FormDisplayContext {
        return new FormDisplayContext(
            0,
            0,
            'ff_form' . $formId,
            $siteUrl,
            false,
            $published && $formRunMode !== self::FORM_OPTION_BACKEND_ONLY
        );
    }
}
