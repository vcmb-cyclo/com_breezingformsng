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

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator\Service;

use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class BfpdfFacadeTest extends TestCase
{
    private const ROOT = __DIR__ . '/../../..';

    private static string $siteDirectory;

    public static function setUpBeforeClass(): void
    {
        self::$siteDirectory = sys_get_temp_dir() . '/bfng-bfpdf-site-' . bin2hex(random_bytes(8));
        self::assertTrue(mkdir(self::$siteDirectory));

        if (!defined('JPATH_ADMINISTRATOR')) {
            define('JPATH_ADMINISTRATOR', self::ROOT . '/administrator');
        }

        if (!defined('JPATH_SITE')) {
            define('JPATH_SITE', self::$siteDirectory);
        }

        require_once self::ROOT . '/administrator/components/com_breezingformsng/vendor/autoload.php';
        require_once self::ROOT . '/administrator/components/com_breezingformsng/src/Service/PdfDocument.php';
        require_once self::ROOT . '/administrator/components/com_breezingformsng/plugins/bfcompat/src/Compat/BFPDF.php';
    }

    public static function tearDownAfterClass(): void
    {
        rmdir(self::$siteDirectory);
    }

    public function testFormerTcpdfHtmlWorkflowProducesAPdf(): void
    {
        $pdf = new \BFPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 12);
        $pdf->writeHTML('<p>First block</p>', true, false, true, false, '');
        $first = $pdf->getLastBBox();
        $pdf->writeHTML('<p>Second block</p>');
        $second = $pdf->getLastBBox();
        $pdf->writeHTMLCell(0, 0, '', '', '<p>Third block</p>', 0, 1);
        $third = $pdf->getLastBBox();
        $pdf->lastPage();

        self::assertGreaterThan($first['y'], $second['y']);
        self::assertGreaterThan($second['y'], $third['y']);
        self::assertStringStartsWith('%PDF-', $pdf->Output('report.pdf', 'S'));
    }

    public function testFileOutputAndLandscapePages(): void
    {
        $path = self::$siteDirectory . '/report.pdf';
        $pdf = new \BFPDF();
        $pdf->AddPage('L');
        $page = $pdf->page->getPage();
        $pdf->writeHTML('<p>Landscape</p>');

        try {
            self::assertSame('', $pdf->Output($path, 'F'));
            self::assertGreaterThan($page['height'], $page['width']);
            self::assertStringStartsWith('%PDF-', (string) file_get_contents($path));
        } finally {
            if (file_exists($path)) {
                unlink($path);
            }
        }
    }

    public function testUnsupportedOutputDestinationIsRejected(): void
    {
        $pdf = new \BFPDF();
        $pdf->AddPage();

        $this->expectException(\InvalidArgumentException::class);
        $pdf->Output('report.pdf', 'E');
    }
}
