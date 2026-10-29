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

namespace Vcmb\Component\BreezingformsNG\Tests\Site\Service\Callback;

use PHPUnit\Framework\TestCase;

final class RelocatedRuntimeTemplateTest extends TestCase
{
    public function testPaymentCallbacksUsePackagedDownloadTemplates(): void
    {
        foreach ([
            'PayPalCallback.php',
            'StripeCallback.php',
        ] as $callback) {
            $source = file_get_contents(
                __DIR__ . '/../../../../components/com_breezingformsng/src/Service/Callback/' . $callback
            );

            self::assertIsString($source);
            self::assertStringNotContainsString('/media/breezingforms/downloadtpl/', $source);
            self::assertStringContainsString('/components/com_breezingformsng/downloadtpl/', $source);
        }
    }

    public function testPdfAttachmentExportFallsBackToThePackagedTemplate(): void
    {
        $source = file_get_contents(
            __DIR__ . '/../../../../components/com_breezingformsng/src/Service/Export/ExportEngine.php'
        );

        self::assertIsString($source);
        self::assertStringNotContainsString("/media/breezingforms/pdftpl/pdf_attachment.php", $source);
        self::assertStringContainsString(
            "JPATH_ADMINISTRATOR . '/components/com_breezingformsng/pdftpl/pdf_attachment.php'",
            $source
        );
        self::assertStringContainsString('require($file);', $source);
    }

    public function testAdminPdfExportDoesNotUseTheRemovedGlobalTemplate(): void
    {
        $source = file_get_contents(
            __DIR__ . '/../../../../administrator/components/com_breezingformsng/src/Service/RecordExporter.php'
        );

        self::assertIsString($source);
        self::assertStringNotContainsString("/media/breezingforms/pdftpl/export_pdf.php", $source);
        self::assertStringContainsString(
            "JPATH_ADMINISTRATOR . '/components/com_breezingformsng/pdftpl/export_pdf.php'",
            $source
        );
    }
}
