<?php
/**
 * BreezingForms - A Joomla Forms Application
 * @version 1.9
 * @package BreezingForms
 * @copyright (C) 2008-2020 by Markus Bopp
 * @license Released under the terms of the GNU General Public License
 * */
defined('_JEXEC') or die('Direct Access to this location is not allowed.');

if(!class_exists('TCPDF')) {
    require_once(JPATH_SITE . '/administrator/components/com_breezingforms/libraries/tcpdf/tcpdf.php');
}

class BFPDF extends TCPDF{

    public $form_name = '';
    public $mailback = false;
    public $which = 'attachment';

    public function __construct($orientation='P', $unit='mm', $format='A4', $unicode=true, $encoding='UTF-8', $diskcache=false, $pdfa=false){

        parent::__construct($orientation, $unit, $format, $unicode, $encoding, $diskcache, $pdfa);

    }

    function setFormName($name){

        $this->form_name = $name;
    }

    function setMailback($mailback){

        $this->mailback = $mailback;
    }

    function setWhich($which = 'attachment'){
        $this->which = $which;
    }

    function Header(){

        $pdf = $this;

        $active_found = '';

        if( is_dir(JPATH_SITE.'/media/breezingforms/pdftpl/fonts/') ){

            $sourcePath = JPATH_SITE.'/media/breezingforms/pdftpl/fonts/';
            if (@file_exists($sourcePath) && @is_readable($sourcePath) && @is_dir($sourcePath) && $handle = @opendir($sourcePath)) {
                while (false !== ($file = @readdir($handle))) {
                    if($file!="." && $file!=".." && $this->endsWith(strtolower($file), '.php')) {
                        $file_sep = explode('.', $file);
                        if(count($file_sep) > 1){
                            unset($file_sep[count($file_sep)-1]);
                            $pdf->AddFont(implode('_',$file_sep), '', $sourcePath.$file);
                            $font_loaded = true;
                        }
                    }
                    if($file!="." && $file!=".." && $this->endsWith(strtolower($file), '.ttf')) {
                        $file_sep = explode('.', $file);
                        if(count($file_sep) > 1){
                            unset($file_sep[count($file_sep)-1]);
                            $ttf_name = TCPDF_FONTS::addTTFfont($sourcePath.$file, 'TrueTypeUnicode');
                            $font_loaded = true;
                        }
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
                @closedir($handle);
            }
        }

        if(!$active_found){
            TCPDF_FONTS::addTTFfont(JPATH_SITE.'/administrator/components/com_breezingforms/libraries/tcpdf/fonts/verdana.ttf', 'TrueTypeUnicode');
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

    function Footer(){

        $pdf = $this;

        $active_found = '';

        if( is_dir(JPATH_SITE.'/media/breezingforms/pdftpl/fonts/') ){

            $sourcePath = JPATH_SITE.'/media/breezingforms/pdftpl/fonts/';
            if (@file_exists($sourcePath) && @is_readable($sourcePath) && @is_dir($sourcePath) && $handle = @opendir($sourcePath)) {
                while (false !== ($file = @readdir($handle))) {
                    if($file!="." && $file!=".." && $this->endsWith(strtolower($file), '.php')) {
                        $file_sep = explode('.', $file);
                        if(count($file_sep) > 1){
                            unset($file_sep[count($file_sep)-1]);
                            $pdf->AddFont(implode('_',$file_sep), '', $sourcePath.$file);
                            $font_loaded = true;
                        }
                    }
                    if($file!="." && $file!=".." && $this->endsWith(strtolower($file), '.ttf')) {
                        $file_sep = explode('.', $file);
                        if(count($file_sep) > 1){
                            unset($file_sep[count($file_sep)-1]);
                            $ttf_name = TCPDF_FONTS::addTTFfont($sourcePath.$file, 'TrueTypeUnicode');
                            $font_loaded = true;
                        }
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
                @closedir($handle);
            }
        }

        if(!$active_found){
            TCPDF_FONTS::addTTFfont(JPATH_SITE.'/administrator/components/com_breezingforms/libraries/tcpdf/fonts/verdana.ttf', 'TrueTypeUnicode');
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

    function getHeaderTemplate(){

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

    function getFooterTemplate(){

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

    function endsWith($haystack, $needle)
    {
        return $needle === "" || substr($haystack, -strlen($needle)) === $needle;
    }
}