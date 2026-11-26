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
     *     fields: list<array{name: string, type: string, label: string, required: bool}>
     * } $data Theme options are empty when the renderer does not support a theme change.
     */
    public function build(array $data): string
    {
        $id = 'bfng-debug-' . $data['formId'];

        $html = '<div class="bfng-debug" id="' . $id . '">'
            . '<div class="bfng-debug__bar">'
            . '<strong class="bfng-debug__badge">' . $this->label('BADGE') . '</strong>'
            . '<span class="bfng-debug__time">' . $this->label('RENDER_TIME') . ' '
            . $data['renderTime'] . '&nbsp;ms</span>';

        if ($data['themeOptions'] !== []) {
            $html .= '<label class="bfng-debug__theme" for="' . $id . '-theme">' . $this->label('THEME')
                . ' <select id="' . $id . '-theme" data-bfng-debug-theme>';

            if (!in_array($data['appliedTheme'], array_column($data['themeOptions'], 'theme'), true)) {
                $html .= '<option value="" selected disabled>' . self::escape($data['appliedTheme']) . '</option>';
            }

            foreach ($data['themeOptions'] as $option) {
                $name = self::escape($option['theme']);

                if ($option['theme'] === $data['configuredTheme']) {
                    $name .= ' (' . $this->label('THEME_CONFIGURED') . ')';
                }

                $html .= '<option value="' . self::escape($option['url']) . '"'
                    . ($option['theme'] === $data['appliedTheme'] ? ' selected' : '') . '>' . $name . '</option>';
            }

            $html .= '</select></label>';
        }

        $html .= '</div><details class="bfng-debug__details"><summary>' . $this->label('DETAILS') . '</summary><dl>';

        foreach (
            [
                'ACCOUNT' => $data['account'],
                'FORM' => $data['formName'] . ' (#' . $data['formId'] . ')',
                'RENDERER' => $data['renderer'],
                'THEME_APPLIED' => $data['appliedTheme'],
                'THEME_STORED' => $data['configuredTheme'],
            ] as $key => $value
        ) {
            $html .= '<dt>' . $this->label($key) . '</dt><dd>' . self::escape($value) . '</dd>';
        }

        $html .= '</dl><div class="bfng-debug__table"><table><caption>' . $this->label('FIELDS')
            . ' (' . count($data['fields']) . ')</caption><thead><tr>';

        foreach (['FIELD_NAME', 'FIELD_LABEL', 'FIELD_TYPE', 'FIELD_REQUIRED'] as $key) {
            $html .= '<th scope="col">' . $this->label($key) . '</th>';
        }

        $html .= '</tr></thead><tbody>';

        foreach ($data['fields'] as $field) {
            $html .= '<tr><td><code>' . self::escape($field['name']) . '</code></td>'
                . '<td>' . self::escape(strip_tags($field['label'])) . '</td>'
                . '<td><code>' . self::escape($field['type']) . '</code></td>'
                . '<td>' . self::escape(($this->translate)($field['required'] ? 'JYES' : 'JNO')) . '</td></tr>';
        }

        return $html . '</tbody></table></div></details></div>';
    }

    private function label(string $key): string
    {
        return self::escape(($this->translate)('COM_BREEZINGFORMSNG_DEBUG_BAR_' . $key));
    }

    private static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
