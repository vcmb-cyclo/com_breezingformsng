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

use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Administrator\Service\PdfDocument;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class PdfDocumentTest extends TestCase
{
    private const ROOT = __DIR__ . '/../../..';

    private static string $siteDirectory;

    public static function setUpBeforeClass(): void
    {
        self::$siteDirectory = sys_get_temp_dir() . '/bfng-pdf-site-' . bin2hex(random_bytes(8));
        self::assertTrue(mkdir(self::$siteDirectory));

        if (!defined('JPATH_ADMINISTRATOR')) {
            define('JPATH_ADMINISTRATOR', self::ROOT . '/administrator');
        }

        if (!defined('JPATH_SITE')) {
            define('JPATH_SITE', self::$siteDirectory);
        }

        require_once self::ROOT . '/administrator/components/com_breezingformsng/vendor/autoload.php';
        require_once self::ROOT . '/administrator/components/com_breezingformsng/src/Service/PdfDocument.php';
    }

    public static function tearDownAfterClass(): void
    {
        rmdir(self::$siteDirectory);
    }

    public function testNativePdfBytes(): void
    {
        $pdf = new PdfDocument();

        self::assertInstanceOf(\Com\Tecnick\Pdf\Tcpdf::class, $pdf);

        $pdf->addDocumentPage();
        $pdf->renderHtml('<p>PDF migration smoke test</p>');

        self::assertStringStartsWith('%PDF-', $pdf->getOutPDFString());
    }

    public function testStyledTableRendersWithNativeFont(): void
    {
        $pdf = new PdfDocument();
        $pdf->addDocumentPage();
        $pdf->renderHtml('<table><tr><td><b>Test</b></td></tr></table>');

        self::assertStringStartsWith('%PDF-', $pdf->getOutPDFString());
    }

    public function testInstallerReportsTemplateCallsThePdfDocumentLacks(): void
    {
        $source = file_get_contents(self::ROOT . '/script.php');
        self::assertIsString($source);
        $start = strpos($source, '    private static function undefinedPdfTemplateCalls(');
        self::assertNotFalse($start);
        $method = substr($source, $start, strpos($source, "\n    }\n", $start) + 7 - $start);
        $installer = eval('return new class { '
            . str_replace('private static function', 'public static function', $method) . ' };');
        $methods = array_map(
            static fn (\ReflectionMethod $method): string => $method->getName(),
            (new \ReflectionClass(PdfDocument::class))->getMethods()
        );

        // Former TCPDF calls, whatever their case, and BFPDF-only methods,
        // in a header or footer template, where $this is the document.
        $legacy = <<<'PHP'
<?php
$this->SetY(-25);
$this->sety(-25);
echo $this->getAliasNbPages();
$pdf->setCellPaddings(1, 1, 1, 1);
$pdf->output('report.pdf', 'F');
$pdf -> Output('report.pdf', 'F');
PHP;
        self::assertSame(
            ['SetY', 'getAliasNbPages', 'setCellPaddings', 'output'],
            $installer::undefinedPdfTemplateCalls($legacy, $methods, true)
        );

        // In a body template $this is the exporter: only $pdf is checked.
        $body = <<<'PHP'
<?php
foreach ($this->getSubrecords((int) $rec->id) as $sub) {
}
$pdf->SetY(-25);
PHP;
        self::assertSame(['SetY'], $installer::undefinedPdfTemplateCalls($body, $methods, false));

        $native = <<<'PHP'
<?php
$pdf->selectFont('helvetica');
$this->AddPage(['format' => 'A4']);
$this->setPrintHeader(false);
echo $this->getHTMLCell('<p>Header</p>', 15, 5, 180, 20);
echo $this->form_name, (int) $pdfPageNumber;
$this->page->getPage();
PHP;
        self::assertSame([], $installer::undefinedPdfTemplateCalls($native, $methods, true));

        foreach (glob(self::ROOT . '/administrator/components/com_breezingformsng/pdftpl/*.php') ?: [] as $template) {
            $packaged = (string) file_get_contents($template);
            $insideDocument = preg_match('/_(?:header|footer)(?:_pdf)?\.php$/', basename($template)) === 1;
            self::assertSame(
                [],
                $installer::undefinedPdfTemplateCalls($packaged, $methods, $insideDocument),
                basename($template)
            );
        }
    }

    public function testTypedStateSettersAndSuffixHelper(): void
    {
        $pdf = new PdfDocument();

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

    public function testPackagedFootersUsePageNumbersFromTemplateScope(): void
    {
        $pdfPageNumber = 2;
        $pdfPageCount = 3;

        foreach (['pdf_attachment_footer.php', 'export_footer_pdf.php'] as $template) {
            ob_start();
            require self::ROOT . '/administrator/components/com_breezingformsng/pdftpl/' . $template;
            $html = ob_get_clean();

            self::assertIsString($html);
            self::assertStringContainsString('2 / 3', $html);
        }
    }

    public function testLegacyGenericMediaTemplatesDoNotOverridePackagedTemplates(): void
    {
        $templateDirectory = JPATH_SITE . '/media/breezingforms/pdftpl';
        self::assertTrue(mkdir($templateDirectory, recursive: true));

        $names = [
            'export_header_pdf.php',
            'export_footer_pdf.php',
            'pdf_attachment_header.php',
            'pdf_attachment_footer.php',
        ];

        foreach ($names as $name) {
            self::assertNotFalse(file_put_contents($templateDirectory . '/' . $name, '<?php $this->SetY(-25);'));
        }

        try {
            foreach (['export', 'attachment'] as $which) {
                $pdf = new PdfDocument();
                $pdf->setWhich($which);
                $pdf->setPrintHeader(true);
                $pdf->setPrintFooter(true);
                $pdf->addDocumentPage();
                $pdf->renderHtml('<p>Test</p>');

                self::assertStringStartsWith('%PDF-', $pdf->getOutPDFString());
                self::assertStringStartsWith(
                    self::ROOT . '/administrator/components/com_breezingformsng/pdftpl/',
                    $pdf->getFooterTemplate()
                );
            }
        } finally {
            foreach ($names as $name) {
                unlink($templateDirectory . '/' . $name);
            }
            rmdir($templateDirectory);
            rmdir(dirname($templateDirectory));
            rmdir(dirname($templateDirectory, 2));
        }
    }

    public function testEnabledHeaderAndFooterTemplatesRenderAcrossPages(): void
    {
        $templateDirectory = JPATH_SITE . '/media/breezingforms/pdftpl';
        self::assertTrue(mkdir($templateDirectory, recursive: true));

        $header = $templateDirectory . '/registration_pdf_attachment_header.php';
        $footer = $templateDirectory . '/registration_pdf_attachment_footer.php';
        $calls = JPATH_SITE . '/template-calls.log';

        self::assertNotFalse(file_put_contents($header, <<<'PHP'
<?php
if (!isset($pdf) || $pdf !== $this) {
    throw new \LogicException('PDF header template requires the document alias.');
}
$call = 'header:' . $pdfPageNumber . '/' . $pdfPageCount . "\n";
file_put_contents(JPATH_SITE . '/template-calls.log', $call, FILE_APPEND);
echo '<p>Header</p>';
PHP));
        self::assertNotFalse(file_put_contents($footer, <<<'PHP'
<?php
if (!isset($pdf) || $pdf !== $this) {
    throw new \LogicException('PDF footer template requires the document alias.');
}
$call = 'footer:' . $pdfPageNumber . '/' . $pdfPageCount . "\n";
file_put_contents(JPATH_SITE . '/template-calls.log', $call, FILE_APPEND);
echo '<p>Footer</p>';
PHP));

        try {
            $pdf = new PdfDocument();
            $pdf->setFormName('registration');
            $pdf->setPrintHeader(true);
            $pdf->setPrintFooter(true);
            $pdf->addDocumentPage();
            $pdf->renderHtml('<p>First page</p>');
            $pdf->addDocumentPage();
            $pdf->renderHtml('<p>Second page</p>');

            $bytes = $pdf->getOutPDFString();

            self::assertStringStartsWith('%PDF-', $bytes);
            self::assertCount(2, $pdf->page->getPages());
            self::assertSame(
                "header:1/2\nfooter:1/2\nheader:2/2\nfooter:2/2\n",
                file_get_contents($calls)
            );
        } finally {
            unlink($header);
            unlink($footer);
            if (file_exists($calls)) {
                unlink($calls);
            }
            rmdir($templateDirectory);
            rmdir(dirname($templateDirectory));
            rmdir(dirname($templateDirectory, 2));
        }
    }
}
