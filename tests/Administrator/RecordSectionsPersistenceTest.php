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

/** The sections of the record screen stay closed from one record to the next. */
final class RecordSectionsPersistenceTest extends TestCase
{
    public function testSectionsStateIsKeptInTheSessionStorageOfTheTab(): void
    {
        $js = (string) file_get_contents(__DIR__ . '/../../media/com_breezingformsng/js/admin/record-sections.js');

        self::assertStringContainsString('window.sessionStorage', $js);
        self::assertStringContainsString("'[id^=\"record-section-\"].collapse'", $js);
        self::assertStringContainsString("name + '.bs.collapse'", $js);
        // Storage can be blocked: every access is guarded.
        self::assertGreaterThanOrEqual(2, substr_count($js, '} catch {'));
    }

    public function testTheRecordViewLoadsTheScriptAndEverySectionHasAStableId(): void
    {
        $base = __DIR__ . '/../../administrator/components/com_breezingformsng';
        $view = (string) file_get_contents($base . '/src/View/Record/HtmlView.php');
        $template = (string) file_get_contents($base . '/tmpl/record/edit.php');

        self::assertStringContainsString('record-sections.js', $view);
        foreach (['values', 'subinfo', 'recordinfo', 'payment', 'forminfo', 'auditinfo'] as $section) {
            self::assertStringContainsString('record-section-' . $section, $template);
        }
    }
}
