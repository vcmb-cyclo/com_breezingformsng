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

/** Orchestrates the form-level CAPTCHA and ReCaptcha validation callback. */
final class CaptchaValidationScriptBuilder
{
    public function __construct(
        private readonly CaptchaSupportBuilder $support,
        private readonly CaptchaValidationRowSelector $rowSelector,
        private readonly CaptchaLegacyValidationScriptBuilder $legacyBuilder,
        private readonly CaptchaReCaptchaValidationScriptBuilder $reCaptchaBuilder
    ) {
    }

    /**
     * @param array<int, object> $rows
     */
    public function build(
        string $root,
        bool $administrator,
        int $form,
        array $rows,
        int $rowCount,
        string $errorMessage
    ): string {
        [$captchaError, $defaultCallback] = $this->support->validationDefaults($errorMessage);
        $endpoints = $this->support->endpoints($root, $administrator, $form);
        $row = $this->rowSelector->select($rows, $rowCount);

        if ($row === null) {
            return $defaultCallback;
        }

        if ($row->type === 'Captcha') {
            return $this->legacyBuilder->build(
                $captchaError,
                $endpoints['image'],
                $endpoints['check'],
                (int) $row->page
            );
        }

        return $this->reCaptchaBuilder->build(
            $captchaError,
            $endpoints['recaptcha'],
            (int) $row->page
        );
    }
}
