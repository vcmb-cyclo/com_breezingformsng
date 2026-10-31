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

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator;

use PHPUnit\Framework\TestCase;

final class FormsOptionsLabelTooltipsTest extends TestCase
{
    private const COMPONENT = __DIR__ . '/../../administrator/components/com_breezingformsng';
    private const LANGUAGES = ['en-GB', 'fr-FR', 'de-DE', 'it-IT', 'es-ES', 'hu-HU', 'nl-NL', 'tr-TR'];

    /** Labels rendered in loops, whose tooltip key is the label key + _TIP. */
    private const LOOP_LABELS = [
        'FORMS_PIECE_BEFORE', 'FORMS_PIECE_AFTER', 'FORMS_PIECE_BEGIN_SUBMIT', 'FORMS_PIECE_END_SUBMIT',
        'API_KEY', 'LIST_ID', 'EMAIL_FIELD', 'CHECKBOX_FIELD', 'UNSUBSCRIBE_FIELD', 'TEXT_HTML_MOBILE_FIELD',
        'MERGE_VARS', 'DOUBLE_OPTIN', 'UNSUBSCRIBE_DELETE_MEMBER', 'SEND_ERRORS',
        'SF_TOKEN', 'SF_USERNAME', 'SF_TYPE',
        'DROPBOX_ACCESS_TOKEN', 'DROPBOX_AUTH_CODE', 'DROPBOX_FOLDER',
    ];

    private function layout(): string
    {
        $layout = file_get_contents(self::COMPONENT . '/layouts/forms/advanced_options.php');
        self::assertIsString($layout);

        return $layout;
    }

    public function testEveryFieldLabelHasATooltip(): void
    {
        $layout = $this->layout();

        self::assertSame(
            substr_count($layout, 'col-form-label"'),
            substr_count($layout, '<?= $labelTip(')
        );
    }

    /** Like the Joomla lists: a title attribute, which the browser shows. */
    public function testTheAdministrationLeavesTooltipsToTheBrowser(): void
    {
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(self::COMPONENT, \FilesystemIterator::SKIP_DOTS));

        foreach ($files as $file) {
            $path = str_replace('\\', '/', $file->getPathname());

            if ($file->getExtension() === 'php' && preg_match('~/(src|tmpl|layouts)/~', $path)) {
                self::assertStringNotContainsString('hasTooltip', (string) file_get_contents($path), $path);
            }
        }
    }

    public function testEveryTooltipIsTranslated(): void
    {
        preg_match_all("/\\\$labelTip\\('([A-Z_]+)'\\)/", $this->layout(), $matches);
        $keys = array_merge(
            $matches[1],
            array_map(static fn (string $key): string => 'COM_BREEZINGFORMSNG_' . $key . '_TIP', self::LOOP_LABELS),
            [
                'COM_BREEZINGFORMSNG_QM_NEW_ITEM_NOT_IN_ELEMENT',
                'COM_BREEZINGFORMSNG_QM_NEW_ITEM_NOT_IN_FORM',
                'COM_BREEZINGFORMSNG_QM_NEW_ITEM_PAGE_ONLY_IN_FORM',
            ]
        );

        foreach (self::LANGUAGES as $language) {
            $ini = file_get_contents(self::COMPONENT . '/language/' . $language . '/com_breezingformsng.ini');
            self::assertIsString($ini);

            foreach ($keys as $key) {
                self::assertMatchesRegularExpression('/^' . $key . '\s*=\s*"[^"]+"\s*$/m', $ini, $language . ': ' . $key);
            }
        }
    }
}
