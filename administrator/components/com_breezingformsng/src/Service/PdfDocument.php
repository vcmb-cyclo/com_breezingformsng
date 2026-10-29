<?php

/**
 * BreezingForms NG - A Joomla Forms Application
 *
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2008-2020 by Markus Bopp
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 *
 * SPDX-License-Identifier: GPL-2.0-or-later
 **/

namespace Vcmb\Component\BreezingformsNG\Administrator\Service;

defined('_JEXEC') or die('Direct Access to this location is not allowed.');

use Vcmb\Component\BreezingformsNG\Administrator\Helper\VendorHelper;
use Com\Tecnick\Pdf\Tcpdf;

// Custom fonts are imported beside the compiled core fonts.
if (!defined('K_PATH_FONTS')) {
    define(
        'K_PATH_FONTS',
        JPATH_ADMINISTRATOR . '/components/com_breezingformsng/vendor/tecnickcom/tc-lib-pdf-font/target/fonts/core/'
    );
}

if (!class_exists(Tcpdf::class)) {
    VendorHelper::load();
}

class PdfDocument extends Tcpdf
{
    public string $form_name = '';
    public bool $mailback = false;
    public string $which = 'attachment';
    private bool $printHeader = false;
    private bool $printFooter = false;
    private bool $pageDecorated = false;

    public function setFormName(string $name): void
    {

        $this->form_name = $name;
    }

    public function setMailback(bool $mailback): void
    {

        $this->mailback = $mailback;
    }

    public function setWhich(string $which = 'attachment'): void
    {
        $this->which = $which;
    }

    /**
     * Imports a TTF file into the font cache and returns its family name.
     * Com\Tecnick\Pdf\Font\Import throws if the definition file it would
     * write already exists (e.g. Header() and Footer() importing the same
     * font in one request), so skip the import once the cached definition
     * is on disk and return the deterministic name the font importer derives from the
     * filename (see Com\Tecnick\Pdf\Font\Import::makeFontName).
     */
    public static function importTtfFont(string $path): string
    {
        $name = (string) preg_replace(
            '/[^a-z0-9_]/',
            '',
            strtolower(pathinfo($path, PATHINFO_FILENAME))
        );
        $name = str_replace(['bold', 'oblique', 'italic', 'regular'], ['b', 'i', 'i', ''], $name);

        if (!file_exists(K_PATH_FONTS . $name . '.json')) {
            new \Com\Tecnick\Pdf\Font\Import($path, '', 'TrueTypeUnicode');
        }

        return $name;
    }

    public function selectFont(string $family, string $definitionFile = ''): void
    {
        $font = $this->font->insert($this->pon, $family, '', 10, ifile: $definitionFile);
        if ($this->page->getPages() !== []) {
            $this->page->addContent($font['out']);
        }
    }

    public function setPrintHeader(bool $enabled): void
    {
        $this->printHeader = $enabled;
    }

    public function setPrintFooter(bool $enabled): void
    {
        $this->printFooter = $enabled;
    }

    public function addDocumentPage(): void
    {
        if (!$this->font->hasCurrentFont()) {
            $this->selectFont('helvetica');
        }

        $this->addPage([
            'format' => 'A4',
            'orientation' => 'P',
            'margin' => ['PL' => 15, 'PR' => 15, 'PT' => 20, 'PB' => 25],
        ]);
    }

    public function renderHtml(string $html): void
    {
        $page = $this->page->getPage();
        $this->addHTMLCell($html, posx: 15, posy: 20, width: $page['width'] - 30);
    }

    public function getOutPDFString(): string
    {
        if (!$this->pageDecorated) {
            $this->decoratePages();
            $this->pageDecorated = true;
        }

        return parent::getOutPDFString();
    }

    public function download(string $filename): void
    {
        $this->setPDFFilename($filename);
        $this->downloadPDF($this->getOutPDFString());
    }

    public function saveToFile(string $path): void
    {
        $bytes = $this->getOutPDFString();
        if (file_put_contents($path, $bytes) !== strlen($bytes)) {
            throw new \RuntimeException('Unable to write PDF file: ' . $path);
        }
    }

    private function decoratePages(): void
    {
        $pages = $this->page->getPages();
        $total = count($pages);
        $current = $this->page->getPageId();

        foreach ($pages as $pid => $page) {
            $this->setCurrentPage($pid);
            $width = $page['width'] - 30;
            $templates = [
                [$this->printHeader, $this->getHeaderTemplate(), 5.0],
                [$this->printFooter, $this->getFooterTemplate(), $page['height'] - 25],
            ];
            foreach ($templates as [$enabled, $file, $y]) {
                if (!$enabled || $file === '') {
                    continue;
                }

                $pdfPageNumber = $pid + 1;
                $pdfPageCount = $total;
                ob_start();
                require $file;
                $html = (string) ob_get_clean();
                $this->page->addContent($this->getHTMLCell($html, posx: 15, posy: $y, width: $width, height: 20), $pid);
            }
        }

        if ($total > 0) {
            $this->setCurrentPage($current);
        }
    }

    /** @return string */
    public function getHeaderTemplate()
    {

        $file = '';

        if ($this->which == 'attachment') {
            $file = JPATH_SITE . '/media/breezingforms/pdftpl/' . $this->form_name . '_pdf_attachment_header.php';

            if (!file_exists($file)) {
                $file = JPATH_ADMINISTRATOR . '/components/com_breezingformsng/pdftpl/pdf_attachment_header.php';
            }

            if ($this->mailback) {
                $mb_file = JPATH_SITE . '/media/breezingforms/pdftpl/' . $this->form_name . '_pdf_mailback_attachment_header.php';
                if (file_exists($mb_file)) {
                    $file = $mb_file;
                } else {
                    $mb_file = JPATH_SITE . '/media/breezingforms/pdftpl/pdf_mailback_attachment_header.php';
                    if (file_exists($mb_file)) {
                        $file = $mb_file;
                    }
                }
            }
        } elseif ($this->which == 'export') {
            $file = JPATH_SITE . '/media/breezingforms/pdftpl/export_custom_header_pdf.php';
            if (!file_exists($file)) {
                $file = JPATH_ADMINISTRATOR . '/components/com_breezingformsng/pdftpl/export_header_pdf.php';
            }

            if ($this->form_name != '') {
                $file2 = JPATH_SITE . '/media/breezingforms/pdftpl/' . $this->form_name . '_export_header_pdf.php';
                if (file_exists($file2)) {
                    $file = JPATH_SITE . '/media/breezingforms/pdftpl/' . $this->form_name . '_export_header_pdf.php';
                }
            }
        }

        if ($file == '' || !file_exists($file)) {
            return '';
        }

        return $file;
    }

    /** @return string */
    public function getFooterTemplate()
    {

        $file = '';

        if ($this->which == 'attachment') {
            $file = JPATH_SITE . '/media/breezingforms/pdftpl/' . $this->form_name . '_pdf_attachment_footer.php';

            if (!file_exists($file)) {
                $file = JPATH_ADMINISTRATOR . '/components/com_breezingformsng/pdftpl/pdf_attachment_footer.php';
            }

            if ($this->mailback) {
                $mb_file = JPATH_SITE . '/media/breezingforms/pdftpl/' . $this->form_name . '_pdf_mailback_attachment_footer.php';
                if (file_exists($mb_file)) {
                    $file = $mb_file;
                } else {
                    $mb_file = JPATH_SITE . '/media/breezingforms/pdftpl/pdf_mailback_attachment_footer.php';
                    if (file_exists($mb_file)) {
                        $file = $mb_file;
                    }
                }
            }
        } elseif ($this->which == 'export') {
            $file = JPATH_SITE . '/media/breezingforms/pdftpl/export_custom_footer_pdf.php';
            if (!file_exists($file)) {
                $file = JPATH_ADMINISTRATOR . '/components/com_breezingformsng/pdftpl/export_footer_pdf.php';
            }

            if ($this->form_name != '') {
                $file2 = JPATH_SITE . '/media/breezingforms/pdftpl/' . $this->form_name . '_export_footer_pdf.php';
                if (file_exists($file2)) {
                    $file = JPATH_SITE . '/media/breezingforms/pdftpl/' . $this->form_name . '_export_footer_pdf.php';
                }
            }
        }

        if ($file == '' || !file_exists($file)) {
            return '';
        }

        return $file;
    }

    public function endsWith(string $haystack, string $needle): bool
    {
        return str_ends_with($haystack, $needle);
    }
}
