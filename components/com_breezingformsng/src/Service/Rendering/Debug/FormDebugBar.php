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
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\LanguageFactoryInterface;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Factory\MVCFactoryServiceInterface;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;
use stdClass;
use Vcmb\Component\BreezingformsNG\Administrator\Model\FormOptionsModel;
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
     *     fieldNames: bool,
     *     fieldNamesUrl: string,
     *     save: array{url: string, token: string, revision: string, language: string},
     *     fields: list<array{
     *         id: int,
     *         name: string,
     *         type: string,
     *         editorType: string,
     *         typeName: string,
     *         label: string,
     *         required: bool,
     *         validation: string,
     *         customValidation: bool,
     *         parent: string,
     *         edit: array<string, mixed>
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

        $fieldNames = FormDebugContext::showFieldNames(
            $app->getInput()->getCmd(FormDebugContext::NAMES_PARAM, '')
        );

        $code = (string) bf_b64dec($processor->formrow->template_code);
        $tree = json_decode($code, true);
        $objects = json_decode($code);
        $default = ComponentHelper::getParams('com_languages')->get('site');
        $tag = $app->getLanguage()?->getTag() ?? 'en-GB';
        $language = $tag === $default ? FieldEdit::DEFAULT_LANGUAGE : $tag;
        $edits = $objects instanceof stdClass ? FieldEdit::values($objects, $language) : [];
        $formId = (int) $processor->form;

        $media = Uri::root(true) . '/media/com_breezingformsng';
        RuntimeAssetLoader::fontAwesome($app);
        // The icons of the field types and their colours, as in the editor.
        RuntimeAssetLoader::style($app, $media . '/css/bfng-tokens.css');
        RuntimeAssetLoader::style($app, $media . '/css/field-type-icons.css');
        RuntimeAssetLoader::style($app, $media . '/css/site/quickmode-debug-bar.css');
        RuntimeAssetLoader::script($app, $media . '/js/site/quickmode-debug-bar.js');

        $marker = '<!-- bfng-debug-bar-' . $formId . ' -->';
        echo $marker;

        $classicTheme = $classic || $classicSwitch ? $themeName : null;

        return new self($themeName, $classicTheme, $classicSwitch, $marker, microtime(true), [
            'formId' => $formId,
            'formName' => $processor->formrow->title ?? '',
            'account' => $identity->name . ' (' . $identity->username . ', #' . $identity->id . ')',
            'renderer' => $classic || $classicSwitch
                ? 'Classic'
                : (empty($properties['themebootstrapMode']) ? 'Bootstrap' : 'One page'),
            'configuredTheme' => $stored,
            'appliedTheme' => FormDebugContext::appliedTheme($override, $stored, $themes),
            'themeOptions' => $options,
            'colorMode' => $colorMode,
            'colorOptions' => $colorOptions,
            'fieldNames' => $fieldNames,
            // The address the checkbox goes to: it reverses the current choice.
            'fieldNamesUrl' => self::url(FormDebugContext::NAMES_PARAM, $fieldNames ? '0' : null),
            'save' => [
                'url' => self::saveUrl($tag),
                'token' => Session::getFormToken(),
                'revision' => self::revision($app, $formId),
                'language' => $language,
            ],
            'fields' => array_map(
                static fn (array $field): array => $field + ['edit' => $edits[$field['name']] ?? []],
                is_array($tree) ? self::withTypeNames(FormDebugContext::fields($tree), $tag) : []
            ),
        ]);
    }

    private static function saveUrl(string $tag): string
    {
        return Route::link(
            'site',
            'index.php?option=com_breezingformsng&task=fielddebug.save&format=json&lang=' . rawurlencode($tag),
            false
        );
    }

    /** The state of the form this page shows: a move starting from another one is refused. */
    private static function revision(CMSApplication $app, int $formId): string
    {
        $component = $app->bootComponent('com_breezingformsng');
        $options = $component instanceof MVCFactoryServiceInterface
            ? $component->getMVCFactory()->createModel('FormOptions', 'Administrator', ['ignore_request' => true])
            : null;
        $form = $options instanceof FormOptionsModel ? $options->getForm($formId) : null;

        return $form === null ? '' : FormOptionsModel::quickmodeRevision($form);
    }

    /**
     * Adds the name of each type, read from the administrator strings in a language object of its
     * own: they must not replace strings of the site.
     *
     * @param list<array{
     *     id: int,
     *     name: string,
     *     type: string,
     *     editorType: string,
     *     label: string,
     *     required: bool,
     *     validation: string,
     *     customValidation: bool,
     *     parent: string
     * }> $fields
     *
     * @return list<array{
     *     id: int,
     *     name: string,
     *     type: string,
     *     editorType: string,
     *     label: string,
     *     required: bool,
     *     validation: string,
     *     customValidation: bool,
     *     parent: string,
     *     typeName: string
     * }>
     */
    private static function withTypeNames(array $fields, string $tag): array
    {
        $language = Factory::getContainer()->get(LanguageFactoryInterface::class)->createLanguage($tag, false);

        if (!$language->load('com_breezingformsng', JPATH_ADMINISTRATOR . '/components/com_breezingformsng')) {
            $language->load('com_breezingformsng', JPATH_ADMINISTRATOR);
        }

        return array_map(static function (array $field) use ($language): array {
            $key = FormDebugContext::typeLabelKey($field['editorType']);

            return $field + ['typeName' => $key === '' ? $field['editorType'] : $language->_($key)];
        }, $fields);
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
        $data['renderTime'] = (int) round((microtime(true) - $this->startedAt) * 1000.0);
        $bar = $builder->build($data);

        ob_clean();
        echo str_replace($this->marker, $bar, $output);
    }
}
