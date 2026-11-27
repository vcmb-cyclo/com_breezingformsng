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

use Closure;

/** Builds the markup of the frontend debug bar. */
final class FormDebugBarBuilder
{
    private const COLOR_ICONS = ['auto' => 'circle-half-stroke', 'light' => 'sun', 'dark' => 'moon'];

    /**
     * @param Closure(string): string $translate
     */
    public function __construct(private readonly Closure $translate)
    {
    }

    /**
     * @param array{
     *     formId: int,
     *     formName: string,
     *     account: string,
     *     renderer: string,
     *     configuredTheme: string,
     *     appliedTheme: string,
     *     renderTime: int,
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
    public function build(array $data): string
    {
        $id = 'bfng-debug-' . $data['formId'];

        $html = '<div class="bfng-debug" id="' . $id . '" data-bfng-debug-color="'
            . self::escape($data['colorMode']) . '" data-bfng-debug-names="' . (int) $data['fieldNames']
            . '"' . $this->saveAttributes($data['formId'], $data['save']) . '><div class="bfng-debug__bar">'
            . '<strong class="bfng-debug__badge">' . self::icon('eye') . ' ' . $this->label('BADGE') . '</strong>'
            . '<span class="bfng-debug__modes" role="group" aria-label="' . $this->label('COLOR_MODE') . '">';

        foreach ($data['colorOptions'] as $option) {
            $name = $this->label('COLOR_' . strtoupper($option['mode']));
            $html .= '<a href="' . self::escape($option['url']) . '" title="' . $name . '" aria-label="' . $name . '"'
                . ($option['mode'] === $data['colorMode'] ? ' aria-current="true"' : '') . '>'
                . self::icon(self::COLOR_ICONS[$option['mode']] ?? 'circle-half-stroke') . '</a>';
        }

        $html .= '</span>';

        if ($data['themeOptions'] !== []) {
            $html .= '<label class="bfng-debug__theme" for="' . $id . '-theme">' . $this->label('THEME')
                . ' <select id="' . $id . '-theme" title="* ' . $this->label('THEME_CONFIGURED')
                . '" data-bfng-debug-theme>';

            if (!in_array($data['appliedTheme'], array_column($data['themeOptions'], 'theme'), true)) {
                $html .= '<option value="" selected disabled>' . self::escape($data['appliedTheme']) . '</option>';
            }

            $classicGroup = false;

            foreach ($data['themeOptions'] as $option) {
                $name = self::escape(FormDebugContext::themeName($option['theme']));

                // Classic choices come last, in their own group.
                if (!$classicGroup && FormDebugContext::isClassicChoice($option['theme'])) {
                    $html .= '<optgroup label="Classic">';
                    $classicGroup = true;
                }

                if ($option['theme'] === $data['configuredTheme']) {
                    $name .= ' *';
                }

                $html .= '<option value="' . self::escape($option['url']) . '"'
                    . ($option['theme'] === $data['appliedTheme'] ? ' selected' : '') . '>' . $name . '</option>';
            }

            $html .= ($classicGroup ? '</optgroup>' : '') . '</select></label>';
        }

        $html .= '<label class="bfng-debug__names"><input type="checkbox" data-bfng-debug-names-url="'
            . self::escape($data['fieldNamesUrl']) . '"' . ($data['fieldNames'] ? ' checked' : '') . '> '
            . $this->label('SHOW_FIELD_NAMES') . '</label>';

        $help = $this->label('HELP');
        $html .= '<span class="bfng-debug__form">' . $this->label('FORM') . ' : '
            . self::escape($data['formName']) . '</span>'
            . '<span class="bfng-debug__tag">' . $this->label('ACCOUNT') . ' : '
            . self::escape($data['account']) . '</span>'
            . '<span class="bfng-debug__tag">' . $this->label('RENDER_TIME') . ' '
            . $data['renderTime'] . '&nbsp;ms</span>'
            // Own tooltip: the one of a title attribute is slow and absent on click, keyboard and touch.
            . '<span class="bfng-debug__help-box">'
            . '<button type="button" class="bfng-debug__help" aria-label="' . $help . '">'
            . self::icon('circle-question') . '</button>'
            . '<span class="bfng-debug__tooltip" aria-hidden="true">' . $help . '</span></span>';

        $html .= '</div><details class="bfng-debug__details"><summary>' . $this->label('DETAILS') . '</summary><dl>';

        foreach (
            [
                'ACCOUNT' => $data['account'],
                'FORM' => $data['formName'] . ' (#' . $data['formId'] . ')',
                'RENDERER' => $data['renderer'],
                'THEME_APPLIED' => FormDebugContext::themeName($data['appliedTheme']),
                'THEME_STORED' => $data['configuredTheme'],
            ] as $key => $value
        ) {
            $html .= '<dt>' . $this->label($key) . '</dt><dd>' . self::escape($value) . '</dd>';
        }

        $html .= '</dl><div class="bfng-debug__table"><table><caption>' . $this->label('FIELDS')
            . ' (' . count($data['fields']) . ')</caption><thead><tr>';

        foreach (['FIELD_NAME', 'FIELD_LABEL', 'FIELD_TYPE', 'FIELD_REQUIRED', 'FIELD_VALIDATION'] as $key) {
            $html .= '<th scope="col">' . $this->label($key) . '</th>';
        }

        $html .= '</tr></thead><tbody>';

        foreach ($data['fields'] as $field) {
            $validation = $field['validation'] === ''
                ? ''
                : '<code>' . self::escape($field['validation']) . '</code>';

            if ($field['customValidation']) {
                $validation .= ' (' . $this->label('VALIDATION_CUSTOM') . ')';
            }

            $name = self::escape($field['name']);
            // The tag next to the field takes its icon and its tooltip from this row.
            $html .= '<tr data-bfng-field="' . $name . '" data-bfng-id="' . $field['id']
                . '" data-bfng-parent="' . self::escape($field['parent'])
                . '" data-bfng-type="' . self::escape($field['editorType'])
                . '" data-bfng-type-name="' . self::escape($field['typeName'])
                . '" data-bfng-edit="' . self::escape((string) json_encode($field['edit']))
                . '"><td><code>' . $name . '</code></td>'
                . '<td>' . self::escape(strip_tags($field['label'])) . '</td>'
                . '<td><code>' . self::escape($field['type']) . '</code></td>'
                . '<td>' . self::escape(($this->translate)($field['required'] ? 'JYES' : 'JNO')) . '</td>'
                . '<td>' . $validation . '</td></tr>';
        }

        return $html . '</tbody></table></div></details></div>';
    }

    /**
     * What the script needs to save moved and edited fields, and its texts.
     *
     * @param array{url: string, token: string, revision: string, language: string} $save
     */
    private function saveAttributes(int $formId, array $save): string
    {
        return ' data-bfng-form="' . $formId . '" data-bfng-save-url="' . self::escape($save['url'])
            . '" data-bfng-token="' . self::escape($save['token'])
            . '" data-bfng-revision="' . self::escape($save['revision'])
            . '" data-bfng-language="' . self::escape($save['language'])
            . '" data-bfng-move-hint="' . $this->label('MOVE_HINT')
            . '" data-bfng-pending-text="' . $this->label('PENDING')
            . '" data-bfng-note-label="' . $this->label('NOTE_LABEL')
            . '" data-bfng-save-label="' . self::escape(($this->translate)('JSAVE'))
            . '" data-bfng-cancel-label="' . self::escape(($this->translate)('JCANCEL'))
            . '" data-bfng-save-failed="' . $this->label('SAVE_FAILED')
            . '" data-bfng-edit-texts="' . self::escape($this->editTexts($save['language'])) . '"';
    }

    /** Texts of the field window, as JSON. */
    private function editTexts(string $language): string
    {
        $key = static fn (string $name): string => 'COM_BREEZINGFORMSNG_DEBUG_BAR_' . $name;

        return (string) json_encode([
            'open' => ($this->translate)($key('EDIT_OPEN')),
            'title' => ($this->translate)($key('EDIT_TITLE')),
            'label' => ($this->translate)($key('FIELD_LABEL')),
            'hint' => ($this->translate)($key('EDIT_HELP')),
            'required' => ($this->translate)($key('FIELD_REQUIRED')),
            'locked' => ($this->translate)($key('EDIT_REQUIRED_LOCKED')),
            'length' => ($this->translate)($key('LIMIT_LENGTH')),
            'min' => ($this->translate)($key('LIMIT_MIN')),
            'max' => ($this->translate)($key('LIMIT_MAX')),
            'date' => ($this->translate)($key('LIMIT_DATE')),
            'language' => $language === FieldEdit::DEFAULT_LANGUAGE
                ? ''
                : sprintf(($this->translate)($key('EDIT_LANGUAGE')), $language),
            'apply' => ($this->translate)($key('APPLY')),
            'cancel' => ($this->translate)('JCANCEL'),
        ]);
    }

    private function label(string $key): string
    {
        return self::escape(($this->translate)('COM_BREEZINGFORMSNG_DEBUG_BAR_' . $key));
    }

    private static function icon(string $name): string
    {
        return '<span class="fa-solid fa-' . $name . '" aria-hidden="true"></span>';
    }

    private static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
