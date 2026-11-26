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

namespace Vcmb\Component\BreezingformsNG\Site\Service\Widget;

use Vcmb\Component\BreezingformsNG\Site\Service\Security\RobotCheckStore;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Vcmb\Component\BreezingformsNG\Site\Service\Runtime\RuntimeAssetLoader;

/** Registers the widget runtime (script, style, options, runtime strings) once the page holds a widget. */
final class WidgetAssets
{
    private const SUPPORTED_LANGUAGES = ['en', 'fr', 'de', 'it', 'es', 'hu', 'nl', 'tr'];

    private const RUNTIME_STRINGS = [
        'COM_BREEZINGFORMSNG_WIDGET_DROPZONE_PROMPT',
        'COM_BREEZINGFORMSNG_WIDGET_DROPZONE_BROWSE',
        'COM_BREEZINGFORMSNG_WIDGET_DROPZONE_FILES',
        'COM_BREEZINGFORMSNG_WIDGET_ADDRESS_NO_RESULT',
        'COM_BREEZINGFORMSNG_WIDGET_ADDRESS_LISTBOX_LABEL',
        'COM_BREEZINGFORMSNG_WIDGET_PHONE_INVALID',
    ];

    public static function register(CMSApplication $application): void
    {
        $base = Uri::root(true) . '/media/com_breezingformsng/';

        RuntimeAssetLoader::style($application, $base . 'css/bfng-widgets.css');
        RuntimeAssetLoader::script($application, $base . 'js/site/bfng-widgets.js', ['defer' => true]);

        $language = $application->getLanguage();
        $language?->load('com_breezingformsng', JPATH_SITE . '/components/com_breezingformsng');
        $tag = str_replace('_', '-', (string) $language?->getTag());
        $lang = strtolower(substr($tag, 0, 2));
        $lang = \in_array($lang, self::SUPPORTED_LANGUAGES, true) ? $lang : 'en';

        $application->getDocument()?->addScriptOptions('com_breezingformsng.widgets', [
            'base' => $base,
            'lang' => $lang,
            'dateLocale' => preg_match('/^[a-z]{2}-[A-Z]{2}$/', $tag) === 1 ? $tag : 'en-GB',
        ]);

        foreach (self::RUNTIME_STRINGS as $key) {
            Text::script($key);
        }
    }

    /**
     * A robot check element gets a new challenge of this session, with the texts of its status line.
     *
     * @param array<string, mixed> $field
     *
     * @return array<string, mixed>
     */
    public static function withRobotChallenge(CMSApplication $application, array $field): array
    {
        if (WidgetOptions::widget($field) !== 'robot') {
            return $field;
        }

        $field['robotChallenge'] = (new RobotCheckStore($application->getSession()))->issue() + [
            'working' => Text::_('COM_BREEZINGFORMSNG_ROBOT_WORKING'),
            'done' => Text::_('COM_BREEZINGFORMSNG_ROBOT_DONE'),
        ];

        return $field;
    }
}
