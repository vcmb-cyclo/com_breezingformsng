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
    private const RUNMODE_FRONTEND = 0;
    private const RUNMODE_BACKEND = 1;
    private const RUNMODE_PREVIEW = 2;
    private const FORM_RUNMODE_ALL = 0;
    private const FORM_RUNMODE_BACKEND = 2;
    // Value the form's run-mode option stores for "Backend only"; its labels
    // (0 Frontend only, 1 Backend only, 2 Frontend & Backend) never matched
    // the constants above.
    private const FORM_OPTION_BACKEND_ONLY = 1;

    public function resolve(
        int $runMode,
        bool $inFrame,
        int $formId,
        int $formRunMode,
        bool $published,
        int $previewMode,
        bool $gridEnabled,
        int $gridSize,
        string $siteUrl
    ): FormDisplayContext {
        $inline = 0;
        $template = 0;
        $htmlFormId = 'ff_form' . $formId;

        if ($runMode === self::RUNMODE_FRONTEND) {
            $homepage = $siteUrl;
        } elseif ($inFrame) {
            $homepage = $siteUrl . '/administrator/index.php?tmpl=component';
            $template = $formRunMode === self::FORM_RUNMODE_BACKEND ? 1 : 0;
        } else {
            $homepage = 'index.php?tmpl=component';
            $template = 1;

            if ($runMode === self::RUNMODE_PREVIEW) {
                $inline = 1;
                $htmlFormId = 'adminForm';
            }
        }

        // The site always runs in frontend mode: it shows every published form
        // but one saved as "Backend only". A form saved as "Frontend &
        // Backend" (2) used to be blocked here.
        $canRun = $published && match ($runMode) {
            self::RUNMODE_FRONTEND => $formRunMode !== self::FORM_OPTION_BACKEND_ONLY,
            self::RUNMODE_BACKEND => $formRunMode === self::FORM_RUNMODE_ALL
                || $formRunMode === self::FORM_RUNMODE_BACKEND,
            default => true,
        };

        return new FormDisplayContext(
            $inline,
            $template,
            $htmlFormId,
            $homepage,
            $runMode === self::RUNMODE_PREVIEW && $previewMode > 0 && $gridEnabled && $gridSize > 1,
            $canRun
        );
    }
}
