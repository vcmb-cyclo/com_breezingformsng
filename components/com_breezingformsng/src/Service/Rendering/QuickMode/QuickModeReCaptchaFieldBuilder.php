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

\defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Uri\Uri;
use Vcmb\Component\BreezingformsNG\Site\Service\Runtime\RuntimeAssetLoader;

/** Builds the shared ReCaptcha field markup and runtime assets. */
final class QuickModeReCaptchaFieldBuilder
{
    private QuickModeReCaptchaInitScriptBuilder $initScriptBuilder;

    public function __construct(?QuickModeReCaptchaInitScriptBuilder $initScriptBuilder = null)
    {
        $this->initScriptBuilder = $initScriptBuilder ?? new QuickModeReCaptchaInitScriptBuilder();
    }

    /**
     * @param array<string, mixed> $field
     */
    public function build(
        array $field,
        CMSApplication $application,
        bool $hasFlashUpload,
        bool $resetOnRerender,
        bool $resetFlagOnCallback,
        ?string $controlGroupClass = null,
        ?string $controlsClass = null,
        bool $includeVisibleWidget = false,
        string $submitFunction = 'ff_validate_submit'
    ): string {
        if (!isset($field['pubkey']) || $field['pubkey'] === '') {
            return '<span class="bfCaptcha">' . "\n"
                . 'WARNING: No public key given for ReCaptcha element!'
                . '</span>' . "\n";
        }

        if (!isset($field['invisibleCaptcha']) || !$field['invisibleCaptcha']) {
            return $this->buildVisible(
                $field,
                $application,
                $resetOnRerender,
                $controlGroupClass,
                $controlsClass,
                $includeVisibleWidget
            );
        }

        return $this->buildInvisible(
            $field,
            $application,
            $hasFlashUpload,
            $resetFlagOnCallback,
            $controlGroupClass,
            $controlsClass,
            $submitFunction
        );
    }

    /**
     * @param array<string, mixed> $field
     */
    private function buildVisible(
        array $field,
        CMSApplication $application,
        bool $resetOnRerender,
        ?string $controlGroupClass,
        ?string $controlsClass,
        bool $includeVisibleWidget
    ): string {
        $language = $application->getLanguage() ?? throw new \RuntimeException('The language is not loaded.');
        $languageTag = $language->getTag();
        RuntimeAssetLoader::script(
            $application,
            $this->initScriptBuilder->visibleApiUrl($languageTag),
            ['data-usercentrics' => 'reCAPTCHA']
        );
        RuntimeAssetLoader::script(
            $application,
            Uri::root(true) . '/media/com_breezingformsng/js/site/quickmode-recaptcha-visible.js'
        );

        if ($controlGroupClass === null || $controlsClass === null) {
            $markup = '
                                                    <div style="display: inline-block !important; vertical-align: middle;">
                                                        <div id="newrecaptcha"></div>
                                                    </div>
                                                    ';
        } else {
            $markup = '
                                                    <div style="display: inline-block !important; vertical-align: middle;">
                                                        <div class="' . $controlGroupClass . '">
                                                            <div class="' . $controlsClass . '">
                                                                    <div id="newrecaptcha"></div>
                                                                </div>
                                                        </div>
';
            if ($includeVisibleWidget) {
                $markup .= str_repeat(' ', 56) . '<div class="g-recaptcha" data-sitekey="' . $field['pubkey'] . '"></div>
                                                    </div>
                                                    ';
            } else {
                $markup .= str_repeat(' ', 52) . '</div>
                                                    ';
            }
        }

        return $markup . $this->initScriptBuilder->visible(
            $this->initScriptBuilder->visibleConfiguration($field, $resetOnRerender)
        );
    }

    /**
     * @param array<string, mixed> $field
     */
    private function buildInvisible(
        array $field,
        CMSApplication $application,
        bool $hasFlashUpload,
        bool $resetFlagOnCallback,
        ?string $controlGroupClass,
        ?string $controlsClass,
        string $submitFunction
    ): string {
        $badge = str_replace('invisible_', '', trim((string) $field['theme']));

        if ($controlGroupClass === null || $controlsClass === null) {
            if ($badge === 'inline') {
                $markup = '
                                    <div style="display: inline-block !important; vertical-align: middle;">
                                    <div id="bfInvisibleReCaptchaContainer"></div>
                                    <div id="bfInvisibleReCaptcha"></div>
                                    </div>
                                    ';
            } else {
                $markup = '
                                    <div id="bfInvisibleReCaptchaContainer"></div>
                                    <div id="bfInvisibleReCaptcha"></div>
                                ';
            }
        } elseif ($badge === 'inline') {
            $markup = '
                                        <div style="display: inline-block !important; vertical-align: middle;">
                                            <div class="' . $controlGroupClass . '">
                                                <div class="' . $controlsClass . '">
                                                    <div id="bfInvisibleReCaptchaContainer"></div>
                                                    <div id="bfInvisibleReCaptcha"></div>
                                                </div>
                                            </div>
                                        </div>
                                        ';
        } else {
            $markup = '
                                        <div id="bfInvisibleReCaptchaContainer"></div>
                                        <div id="bfInvisibleReCaptcha"></div>
                                        ';
        }

        RuntimeAssetLoader::script(
            $application,
            Uri::root(true) . '/media/com_breezingformsng/js/site/quickmode-recaptcha-invisible.js'
        );

        $configuration = $this->initScriptBuilder->invisibleConfiguration(
            $field,
            $hasFlashUpload,
            $resetFlagOnCallback,
            $submitFunction
        );

        return $markup
            . '<script data-usercentrics="reCAPTCHA" type="text/javascript">bfInitInvisibleReCaptcha('
            . $this->initScriptBuilder->encode($configuration)
            . ');</script>' . "\n"
            . '<script data-usercentrics="reCAPTCHA" src="'
            . $this->initScriptBuilder->invisibleApiUrl()
            . '" async defer></script>' . "\n";
    }
}
