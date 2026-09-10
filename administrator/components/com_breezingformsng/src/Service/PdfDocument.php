<?php
/**
 * BreezingForms NG - A Joomla Forms Application
 * 
 * @version 6.0.0
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2008-2020 by Markus Bopp
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 * */
namespace Vcmb\Component\BreezingformsNG\Administrator\Service;

defined('_JEXEC') or die('Direct Access to this location is not allowed.');

use Vcmb\Component\BreezingformsNG\Administrator\Helper\VendorHelper;

// TCPDF's own tcpdf_autoconfig.php assumes tc-lib-pdf-font is nested inside
// its own vendor/ dir (a standalone, non-Composer install layout). Under a
// flat top-level Composer install the package is a sibling instead, so the
// built-in default resolves to a directory that never exists. Point it at
// the real location before class_exists() below can trigger TCPDF's
// classmapped autoload (which would otherwise lock in the wrong default).
if (!defined('K_PATH_FONTS')) {
    define(
        'K_PATH_FONTS',
        JPATH_ADMINISTRATOR . '/components/com_breezingformsng/vendor/tecnickcom/tc-lib-pdf-font/target/fonts/core/'
    );
}

if (!class_exists(\TCPDF::class)) {
    VendorHelper::load();
}

class PdfDocument extends \TCPDF
{
    private ?PdfFontDirectoryScanner $fontDirectoryScannerService = null;

    public string $form_name = '';
    public bool $mailback = false;
    public string $which = 'attachment';

    public function __construct($orientation='P', $unit='mm', $format='A4', $unicode=true, $encoding='UTF-8', $diskcache=false, $pdfa=false){

        parent::__construct($orientation, $unit, $format, $unicode, $encoding, $diskcache, $pdfa);

    }

    public function setFormName(string $name): void{

        $this->form_name = $name;
    }

    public function setMailback(bool $mailback): void{

        $this->mailback = $mailback;
    }

    public function setWhich(string $which = 'attachment'): void{
        $this->which = $which;
    }

    /**
     * Imports a TTF file into TCPDF's font cache and returns its family name.
     * Com\Tecnick\Pdf\Font\Import throws if the definition file it would
     * write already exists (e.g. Header() and Footer() importing the same
     * font in one request), so skip the import once the cached definition
     * is on disk and return the deterministic name TCPDF derives from the
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

    public function Header(){

        $pdf = $this;

        $active_found = '';
        $font_loaded = false;
        $ttf_name = '';

        if( is_dir(JPATH_SITE.'/media/breezingforms/pdftpl/fonts/') ){

            $sourcePath = JPATH_SITE.'/media/breezingforms/pdftpl/fonts/';
            foreach ($this->fontDirectoryScanner()->scan($sourcePath) as $file) {
                    if($file!="." && $file!=".." && $this->endsWith(strtolower($file), '.php')) {
                        $file_sep = explode('.', $file);
                        if(count($file_sep) > 1){
                            unset($file_sep[count($file_sep)-1]);
                            $pdf->AddFont(implode('_',$file_sep), '', $sourcePath.$file);
                            $font_loaded = true;
                        }
                    }
                    if($file!="." && $file!=".." && $this->endsWith(strtolower($file), '.ttf')) {
                        $ttf_name = self::importTtfFont($sourcePath.$file);
                        $font_loaded = true;
                    }
                    if($this->endsWith(strtolower($file), '_active')){
                        $active = explode('_', $file);
                        if(count($active) > 1){
                            unset($active[count($active)-1]);
                            $font_name = '';
                            if( $ttf_name != '' ){
                                $font_name = $ttf_name;
                            }else{
                                $font_name = implode('_',$active);
                            }
                            $pdf->SetFont($font_name);
                            if($font_loaded){
                                $active_found = true;
                            }
                        }
                    }
            }
        }

        if(!$active_found){
            self::importTtfFont(JPATH_SITE . '/media/com_breezingformsng/fonts/verdana.ttf');
            $pdf->SetFont('verdana');
        }

        $file = $this->getHeaderTemplate();

        if($file != '') {

            ob_start();
            require($file);
            $contents = ob_get_contents();
            ob_end_clean();

            $this->writeHTML($contents, true, true, true, false, '');
        }
    }

    public function Footer(){

        $pdf = $this;

        $active_found = '';
        $font_loaded = false;
        $ttf_name = '';

        if( is_dir(JPATH_SITE.'/media/breezingforms/pdftpl/fonts/') ){

            $sourcePath = JPATH_SITE.'/media/breezingforms/pdftpl/fonts/';
            foreach ($this->fontDirectoryScanner()->scan($sourcePath) as $file) {
                    if($file!="." && $file!=".." && $this->endsWith(strtolower($file), '.php')) {
                        $file_sep = explode('.', $file);
                        if(count($file_sep) > 1){
                            unset($file_sep[count($file_sep)-1]);
                            $pdf->AddFont(implode('_',$file_sep), '', $sourcePath.$file);
                            $font_loaded = true;
                        }
                    }
                    if($file!="." && $file!=".." && $this->endsWith(strtolower($file), '.ttf')) {
                        $ttf_name = self::importTtfFont($sourcePath.$file);
                        $font_loaded = true;
                    }
                    if($this->endsWith(strtolower($file), '_active')){
                        $active = explode('_', $file);
                        if(count($active) > 1){
                            unset($active[count($active)-1]);
                            $font_name = '';
                            if( $ttf_name != '' ){
                                $font_name = $ttf_name;
                            }else{
                                $font_name = implode('_',$active);
                            }
                            $pdf->SetFont($font_name);
                            if($font_loaded){
                                $active_found = true;
                            }
                        }
                    }
            }
        }

        if(!$active_found){
            self::importTtfFont(JPATH_SITE . '/media/com_breezingformsng/fonts/verdana.ttf');
            $pdf->SetFont('verdana');
        }

        $file = $this->getFooterTemplate();

        if($file != '') {

            ob_start();
            require($file);
            $contents = ob_get_contents();
            ob_end_clean();

            $this->writeHTML($contents, true, true, true, false, '');
        }
    }

    public function getHeaderTemplate(){

        $file = '';

        if($this->which == 'attachment') {

            $file = JPATH_SITE . '/media/breezingforms/pdftpl/' . $this->form_name . '_pdf_attachment_header.php';

            if (!file_exists($file)) {
                $file = JPATH_SITE . '/media/breezingforms/pdftpl/pdf_attachment_header.php';
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
        }
        else if($this->which == 'export'){

            $file = JPATH_SITE . '/media/breezingforms/pdftpl/export_custom_header_pdf.php';
            if (!file_exists($file)) {
                $file = JPATH_SITE . '/media/breezingforms/pdftpl/export_header_pdf.php';
            }

            if($this->form_name != ''){

                $file2 = JPATH_SITE . '/media/breezingforms/pdftpl/'.$this->form_name.'_export_header_pdf.php';
                if (file_exists($file2)) {
                    $file = JPATH_SITE . '/media/breezingforms/pdftpl/'.$this->form_name.'_export_header_pdf.php';
                }
            }
        }

        if($file == '' || !file_exists($file)){

            return '';
        }

        return $file;
    }

    public function getFooterTemplate(){

        $file = '';

        if($this->which == 'attachment') {

            $file = JPATH_SITE . '/media/breezingforms/pdftpl/' . $this->form_name . '_pdf_attachment_footer.php';

            if (!file_exists($file)) {
                $file = JPATH_SITE . '/media/breezingforms/pdftpl/pdf_attachment_footer.php';
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
        }else if($this->which == 'export'){

            $file = JPATH_SITE . '/media/breezingforms/pdftpl/export_custom_footer_pdf.php';
            if (!file_exists($file)) {
                $file = JPATH_SITE . '/media/breezingforms/pdftpl/export_footer_pdf.php';
            }

            if($this->form_name != ''){

                $file2 = JPATH_SITE . '/media/breezingforms/pdftpl/'.$this->form_name.'_export_footer_pdf.php';
                if (file_exists($file2)) {
                    $file = JPATH_SITE . '/media/breezingforms/pdftpl/'.$this->form_name.'_export_footer_pdf.php';
                }
            }
        }

        if($file == '' || !file_exists($file)){

            return '';
        }

        return $file;
    }

    public function endsWith(string $haystack, string $needle): bool
    {
        return str_ends_with($haystack, $needle);
    }

    private function fontDirectoryScanner(): PdfFontDirectoryScanner
    {
        return $this->fontDirectoryScannerService ??= new PdfFontDirectoryScanner();
    }
}
