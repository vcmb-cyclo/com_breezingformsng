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

final class FormsMailbackOptionsTest extends TestCase
{
    private const COMPONENT = __DIR__ . '/../../administrator/components/com_breezingformsng';

    private function layout(): string
    {
        $layout = file_get_contents(self::COMPONENT . '/layouts/forms/advanced_options.php');
        self::assertIsString($layout);

        return $layout;
    }

    /**
     * FormOptionsModel::saveForm() writes every column, so a column the tab
     * does not post is reset on each save.
     */
    public function testEveryMailbackColumnSavedIsPosted(): void
    {
        $layout = $this->layout();

        foreach (['mb_emailntf', 'mb_emaillog', 'mb_emailxml', 'mb_custom_mail_subject', 'mb_alt_mailfrom', 'mb_alt_fromname', 'mb_email_type', 'mb_email_custom_html'] as $column) {
            self::assertStringContainsString('name="' . $column . '"', $layout, $column);
        }

        self::assertStringContainsString("\$mailEditor->display('mb_email_custom_template'", $layout);
    }

    public function testReportAndAttachmentKeepEveryValueTheEngineReads(): void
    {
        $layout = $this->layout();

        self::assertStringContainsString("[0 => 'COM_BREEZINGFORMSNG_FORMS_HDRONLY', 1 => 'COM_BREEZINGFORMSNG_FORMS_NONEMPTY', 2 => 'COM_BREEZINGFORMSNG_FORMS_ALLVALS']", $layout);
        self::assertStringContainsString("[0 => Text::_('JNO'), 1 => 'XML', 3 => 'CSV', 4 => 'PDF']", $layout);
    }

    public function testNewStringsAreTranslated(): void
    {
        foreach (['en-GB', 'fr-FR', 'de-DE', 'it-IT', 'es-ES', 'hu-HU', 'nl-NL', 'tr-TR'] as $language) {
            $ini = file_get_contents(self::COMPONENT . '/language/' . $language . '/com_breezingformsng.ini');
            self::assertIsString($ini);

            foreach (['REPORT_TIP', 'EMAILXML_TIP', 'EMAIL_TYPE_TIP', 'EMAIL_HTML_TIP', 'EMAIL_FIELDS_HINT', 'EMAIL_SOURCE_HINT'] as $key) {
                self::assertMatchesRegularExpression('/^COM_BREEZINGFORMSNG_FORMS_MB_' . $key . '="[^"]+"\s*$/m', $ini, $language . ': ' . $key);
            }
        }
    }
}
