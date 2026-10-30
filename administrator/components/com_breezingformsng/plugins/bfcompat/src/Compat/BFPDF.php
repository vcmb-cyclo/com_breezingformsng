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

declare(strict_types=1);

defined('_JEXEC') or die('Direct Access to this location is not allowed.');

use Vcmb\Component\BreezingformsNG\Administrator\Service\PdfDocument;

/**
 * Not a Joomla-version compatibility shim: kept as the BreezingForms legacy
 * API surface. PHP stored in the database (facileforms_pieces.code,
 * forms.piece*code) and external callers may reference this class by name
 * to build a custom PDF (e.g. a custom export or mailback attachment). The
 * implementation now lives in Administrator\Service\PdfDocument (the same
 * class the component's own export/notification code uses), so this facade
 * never drifts out of sync with it - see BFQuickModeBootstrap/Mobile/OnePage
 * for the same pattern applied to the Phase 9c renderers.
 *
 * PdfDocument extends the native tc-lib-pdf API. This facade keeps the
 * former TCPDF entry points used to turn HTML into a PDF: the constructor,
 * AddPage(), SetFont(), writeHTML(), writeHTMLCell(), lastPage() and
 * Output(), whatever their case, since PHP method names are
 * case-insensitive. tc-lib-pdf has no TCPDF-style cursor, so the cursor-based
 * methods (Cell(), MultiCell(), Write(), Ln(), SetY()...) are not provided:
 * stored code calling them must be adapted. The installer reports stored
 * code that references this class.
 */
class BFPDF extends PdfDocument
{
    private string $orientation;
    private ?float $cursorY = null;

    /**
     * Former TCPDF constructor arguments. The following format, unicode,
     * encoding, disk cache and PDF/A arguments are ignored: pages are A4
     * and UTF-8.
     */
    public function __construct(string $orientation = 'P', string $unit = 'mm')
    {
        parent::__construct($unit);
        $this->orientation = strtoupper($orientation) === 'L' ? 'L' : 'P';
    }

    /**
     * Native page data, or the former TCPDF orientation ('P' or 'L'); the
     * following former arguments are ignored. PHP method names are
     * case-insensitive, so this is also AddPage().
     *
     * @param array<string, mixed>|string $data
     *
     * @return array<string, mixed>
     */
    public function addPage(array|string $data = []): array
    {
        $this->cursorY = null;

        if (is_array($data) && $data !== []) {
            return parent::addPage($data);
        }

        $orientation = is_string($data) && $data !== '' ? strtoupper($data) : $this->orientation;
        $this->addDocumentPage($orientation === 'L' ? 'L' : 'P');

        return $this->page->getPage();
    }

    public function setFont(string $family, string $style = '', ?float $size = null, string $fontfile = ''): void
    {
        $this->selectFont($family, $fontfile, $style, $size ?? 10);
    }

    /**
     * Writes the HTML below the previous block, as TCPDF did. The following
     * former arguments are ignored.
     */
    public function writeHTML(string $html): void
    {
        if ($this->page->getPages() === []) {
            $this->addPage();
        }

        $page = $this->page->getPage();
        $this->writeHtmlBlock(
            $html,
            $page['margin']['PL'],
            $this->cursorY ?? $page['margin']['CT'],
            $page['width'] - $page['margin']['PL'] - $page['margin']['PR'],
            0
        );
    }

    /**
     * Former TCPDF argument order. An empty abscissa or ordinate keeps the
     * current position; the following border, fill and alignment arguments
     * are ignored.
     */
    public function writeHTMLCell(
        float|int $w,
        float|int $h,
        float|int|string|null $x,
        float|int|string|null $y,
        string $html = ''
    ): void {
        if ($this->page->getPages() === []) {
            $this->addPage();
        }

        $page = $this->page->getPage();
        $posx = is_numeric($x) ? (float) $x : $page['margin']['PL'];
        $posy = is_numeric($y) ? (float) $y : ($this->cursorY ?? $page['margin']['CT']);
        $width = $w > 0 ? (float) $w : $page['width'] - $posx - $page['margin']['PR'];

        $this->writeHtmlBlock($html, $posx, $posy, $width, (float) $h);
    }

    public function lastPage(): void
    {
        $pages = $this->page->getPages();
        if ($pages !== []) {
            $this->setCurrentPage((int) array_key_last($pages));
        }
    }

    /**
     * Former TCPDF destinations: I (inline), D (download), F (file),
     * S (string), FI and FD (file, then inline or download).
     */
    public function output(string $name = 'doc.pdf', string $dest = 'I'): string
    {
        $dest = strtoupper($dest);
        if (!in_array($dest, ['I', 'D', 'F', 'S', 'FI', 'FD'], true)) {
            throw new \InvalidArgumentException('Unsupported PDF output destination: ' . $dest);
        }

        if ($dest === 'S') {
            return $this->getOutPDFString();
        }

        if (str_starts_with($dest, 'F')) {
            $this->saveToFile($name);
        }

        if (str_ends_with($dest, 'I')) {
            $this->setPDFFilename(basename($name));
            $this->renderPDF($this->getOutPDFString());
        } elseif (str_ends_with($dest, 'D')) {
            $this->download(basename($name));
        }

        return '';
    }

    private function writeHtmlBlock(string $html, float $posx, float $posy, float $width, float $height): void
    {
        $this->addHTMLCell($html, posx: $posx, posy: $posy, width: $width, height: $height);

        $box = $this->getLastBBox();
        $this->cursorY = $box['y'] + $box['h'];
    }
}
