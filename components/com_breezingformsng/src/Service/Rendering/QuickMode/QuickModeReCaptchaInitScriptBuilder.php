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

namespace Vcmb\Component\BreezingformsNG\Site\Service\Rendering\QuickMode;

/** Builds the shared visible ReCaptcha initialization script. */
final class QuickModeReCaptchaInitScriptBuilder
{
    /** @param array<string, mixed> $field */
    public function visibleConfiguration(array $field, bool $resetOnRerender): array
    {
        return [
            'sitekey' => $field['pubkey'],
            'theme' => trim((string) $field['theme']) === '' ? 'light' : trim((string) $field['theme']),
            'size' => ($field['size'] ?? '') !== '' ? $field['size'] : 'normal',
            'resetOnRerender' => $resetOnRerender,
        ];
    }

    /** @param array<string, mixed> $field */
    public function invisibleConfiguration(
        array $field,
        bool $hasFlashUpload,
        bool $resetFlagOnCallback,
        string $submitFunction = 'ff_validate_submit'
    ): array
    {
        $badge = str_replace('invisible_', '', trim((string) $field['theme']));

        $configuration = [
            'sitekey' => $field['pubkey'],
            'badge' => $badge === 'red' ? '' : $badge,
            'hasFlashUpload' => $hasFlashUpload,
            'resetFlagOnCallback' => $resetFlagOnCallback,
        ];

        if ($submitFunction !== 'ff_validate_submit') {
            $configuration['submitFunction'] = $submitFunction;
        }

        return $configuration;
    }

    public function visibleApiUrl(string $languageTag): string
    {
        $language = explode('-', $languageTag)[0];

        return 'https://www.google.com/recaptcha/api.js?hl=' . $language
            . '&onload=onloadBFNewRecaptchaCallback&render=explicit';
    }

    public function invisibleApiUrl(): string
    {
        return 'https://www.google.com/recaptcha/api.js?onload=onloadBFNewRecaptchaCallback&render=explicit';
    }

    /** @param array<string, mixed> $configuration */
    public function visible(array $configuration): string
    {
        return '<script data-usercentrics="reCAPTCHA" type="text/javascript">bfInitVisibleReCaptcha('
            . json_encode($configuration)
            . ');</script>';
    }

    /** @param array<string, mixed> $configuration */
    public function encode(array $configuration): string
    {
        return (string) json_encode($configuration);
    }
}
