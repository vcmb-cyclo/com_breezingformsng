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

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator\Service;

use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Administrator\Service\PdfDocument;

final class PdfDocumentTest extends TestCase
{
    private const ROOT = __DIR__ . '/../../..';

    private static string $fontDirectory;

    public static function setUpBeforeClass(): void
    {
        self::$fontDirectory = sys_get_temp_dir() . '/bfng-pdf-font-' . bin2hex(random_bytes(8));
        self::assertTrue(mkdir(self::$fontDirectory));
        self::assertNotFalse(file_put_contents(self::$fontDirectory . '/myfontb.json', '{}'));

        if (!defined('JPATH_ADMINISTRATOR')) {
            define('JPATH_ADMINISTRATOR', self::ROOT . '/administrator');
        }

        if (!defined('K_PATH_FONTS')) {
            define('K_PATH_FONTS', self::$fontDirectory . '/');
        }

        if (!class_exists('TCPDF', false)) {
            class_alias(\stdClass::class, 'TCPDF');
        }

        require_once self::ROOT . '/administrator/components/com_breezingformsng/src/Service/PdfDocument.php';
    }

    public static function tearDownAfterClass(): void
    {
        unlink(self::$fontDirectory . '/myfontb.json');
        rmdir(self::$fontDirectory);
    }

    public function testImportUsesTcpdfNormalizationForMixedCaseFontNames(): void
    {
        self::assertSame('myfontb', PdfDocument::importTtfFont('/fonts/MyFont-Bold.ttf'));
    }

    public function testTypedStateSettersAndSuffixHelper(): void
    {
        $pdf = (new \ReflectionClass(PdfDocument::class))->newInstanceWithoutConstructor();

        $pdf->setFormName('registration');
        $pdf->setMailback(true);
        $pdf->setWhich('export');

        self::assertSame('registration', $pdf->form_name);
        self::assertTrue($pdf->mailback);
        self::assertSame('export', $pdf->which);
        self::assertTrue($pdf->endsWith('report.pdf', '.pdf'));
        self::assertTrue($pdf->endsWith('report.pdf', ''));
        self::assertFalse($pdf->endsWith('report.pdf', '.xml'));
    }
}
