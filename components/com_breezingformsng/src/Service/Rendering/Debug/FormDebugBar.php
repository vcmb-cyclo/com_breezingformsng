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

namespace Vcmb\Component\BreezingformsNG\Site\Service\Rendering\Debug;

use HTML_facileFormsProcessor;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Vcmb\Component\BreezingformsNG\Site\Service\Runtime\RuntimeAssetLoader;

/**
 * Debug bar of one form rendering: marks its place above the form, then fills it
 * once the form is rendered so that the render time is known.
 */
final class FormDebugBar
{
    /**
     * @param array{
     *     formId: int,
     *     formName: string,
     *     account: string,
     *     renderer: string,
     *     configuredTheme: string,
     *     appliedTheme: string,
     *     themeOptions: list<array{theme: string, url: string}>,
     *     colorMode: string,
     *     colorOptions: list<array{mode: string, url: string}>,
     *     fields: list<array{
     *         name: string,
     *         type: string,
     *         label: string,
     *         required: bool,
     *         validation: string,
     *         customValidation: bool
     *     }>
     * } $data
     */
    private function __construct(
        public readonly ?string $themeOverride,
        public readonly ?string $classicTheme,
        private readonly bool $classicSwitch,
        private readonly string $marker,
        private readonly float $startedAt,
        private readonly array $data
    ) {
    }

    /**
     * Must be called inside the output buffer of the form.
     *
     * @param array<string, mixed> $properties Root properties of the QuickMode form.
     */
    public static function start(HTML_facileFormsProcessor $processor, array $properties): ?self
    {
        $app = $processor->app;
        $identity = $app->getIdentity();

        if (
            $identity === null
            || !FormDebugContext::isActive(
                $processor->formrow->debug_mode ?? 0,
                $app->isClient('site'),
                $identity->authorise('core.admin')
            )
        ) {
            return null;
        }

        $classic = ($properties['themebootstrapThemeEngine'] ?? '') !== 'bootstrap';
        $themes = $classic
            ? FormDebugContext::installedThemes(JPATH_SITE . '/media/breezingforms/themes')
            : [
                ...FormDebugContext::bootstrapThemes(JPATH_SITE . '/media/breezingforms/themes-bootstrap5'),
                ...FormDebugContext::classicChoices(
                    FormDebugContext::installedThemes(JPATH_SITE . '/media/breezingforms/themes')
                ),
            ];
        $override = FormDebugContext::requestedTheme(
            $app->getInput()->getCmd(FormDebugContext::THEME_PARAM, ''),
            $themes
        );
        $stored = (string) ($properties[$classic ? 'theme' : 'themebootstrap'] ?? '');
        $options = [];

        foreach ($themes as $theme) {
            // The configured theme needs no parameter.
            $options[] = [
                'theme' => $theme,
                'url' => self::url(FormDebugContext::THEME_PARAM, $theme === $stored ? null : $theme),
            ];
        }

        // A Classic theme picked on another form renders it with the Classic engine, for this request only.
        $classicSwitch = $override !== null && FormDebugContext::isClassicChoice($override);
        $themeName = $override === null ? null : FormDebugContext::themeName($override);

        $colorMode = FormDebugContext::colorMode($app->getInput()->getCmd(FormDebugContext::COLOR_PARAM, ''));
        $colorOptions = [];

        foreach (FormDebugContext::COLOR_MODES as $mode) {
            $colorOptions[] = [
                'mode' => $mode,
                'url' => self::url(FormDebugContext::COLOR_PARAM, $mode === 'auto' ? null : $mode),
            ];
        }

        $tree = json_decode(bf_b64dec($processor->formrow->template_code), true);
        $username = (string) $identity->username;
        $formId = (int) $processor->form;

        $media = Uri::root(true) . '/media/com_breezingformsng';
        RuntimeAssetLoader::fontAwesome($app);
        RuntimeAssetLoader::style($app, $media . '/css/site/quickmode-debug-bar.css');
        RuntimeAssetLoader::script($app, $media . '/js/site/quickmode-debug-bar.js');

        $marker = '<!-- bfng-debug-bar-' . $formId . ' -->';
        echo $marker;

        $classicTheme = $classic || $classicSwitch ? $themeName : null;

        return new self($themeName, $classicTheme, $classicSwitch, $marker, microtime(true), [
            'formId' => $formId,
            'formName' => (string) ($processor->formrow->title ?? ''),
            'account' => $identity->name . ' (' . $username . ', #' . (int) $identity->id . ')',
            'renderer' => $classic || $classicSwitch
                ? 'Classic'
                : (empty($properties['themebootstrapMode']) ? 'Bootstrap' : 'One page'),
            'configuredTheme' => $stored,
            'appliedTheme' => FormDebugContext::appliedTheme($override, $stored, $themes),
            'themeOptions' => $options,
            'colorMode' => $colorMode,
            'colorOptions' => $colorOptions,
            'fields' => is_array($tree) ? FormDebugContext::fields($tree) : [],
        ]);
    }

    /**
     * @param array<string, mixed> $properties Root properties of the QuickMode form.
     *
     * @return array<string, mixed> The properties that select the renderer of this request.
     */
    public function rendererMetadata(array $properties): array
    {
        if ($this->classicSwitch) {
            $properties['themebootstrapThemeEngine'] = '';
        }

        return $properties;
    }

    /** Address of the current page with one parameter set, or removed when null. */
    private static function url(string $parameter, ?string $value): string
    {
        $uri = clone Uri::getInstance();

        if ($value === null) {
            $uri->delVar($parameter);
        } else {
            $uri->setVar($parameter, $value);
        }

        return $uri->toString();
    }

    /** Replace the marker in the current output buffer with the bar. */
    public function insert(): void
    {
        $output = ob_get_contents();

        if ($output === false) {
            return;
        }

        $builder = new FormDebugBarBuilder(static fn (string $key): string => Text::_($key));
        $data = $this->data;
        $data['renderTime'] = (int) round((microtime(true) - $this->startedAt) * 1000);
        $bar = $builder->build($data);

        ob_clean();
        echo str_replace($this->marker, $bar, $output);
    }
}
