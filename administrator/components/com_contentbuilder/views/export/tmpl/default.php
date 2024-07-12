<?php
/**
 * @package     ContentBuilder
 * @author      Markus Bopp
 * @link        https://www.crosstec.org
 * @copyright   Copyright (C) 2024 by XDA+GIL
 * @license     GNU/GPL
 */
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Language\Text;

@ob_end_clean();

require_once (JPATH_COMPONENT_ADMINISTRATOR . DS . 'classes' . DS . 'contentbuilder_helpers.php');
//require_once __DIR__ .'/../../../classes/PhpSpreadsheet/Spreadsheet.php';
require __DIR__ . '/../../../librairies/PhpSpreadsheet/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Joomla\CMS\Factory;

$spreadsheet = new Spreadsheet();
$spreadsheet->getProperties()->setCreator("ContentBuilder")->setLastModifiedBy("ContentBuilder");

// Freeze first line.
$spreadsheet->getActiveSheet()->freezePane('A2');

// First row in grey.
$spreadsheet
    ->getActiveSheet()
    ->getStyle('1:1')
    ->getFill()
    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
    ->getStartColor()
    ->setARGB('c0c0c0');


// 1 -- Labels.
$labels = $this->data->visible_labels;
$colreserved = 0;

// Case of show_id_column true -> First column reserved.
$col_id = 0;
$reserved_labels = [];
if ($this->data->show_id_column) {
    $col_id = ++$colreserved;
    array_push($reserved_labels, Text::_('COM_CONTENTBUILDER_ID'));
}

// Case of state true -> column reserved.
$col_state = 0;
if ($this->data->list_state) {
    $col_state = ++$colreserved;
    array_push($reserved_labels, Text::_('COM_CONTENTBUILDER_EDIT_STATE'));
}

// Case of publish true -> column reserved.
$col_publish = 0;
if ($this->data->list_publish) {
    $col_publish = ++$colreserved;
    array_push($reserved_labels, Text::_('PUBLISH'));
}

$labels = array_merge($reserved_labels, $labels);

$col = 1;
foreach ($labels as $label) {
    $cell = [$col++, 1];
    $spreadsheet->setActiveSheetIndex(0)->setCellValue($cell, $label);
    $spreadsheet->getActiveSheet()->getStyle($cell)->getFont()->setBold(true);
}

// 2 -- Data.
$raw = 2;
foreach ($this->data->items as $item) {
    $i = 1;
    if ($col_id > 0) {
        $spreadsheet->setActiveSheetIndex(0)->setCellValue([$i++, $raw], $item->colRecord);
    }

    if ($col_state > 0) {
        $database = Factory::getDBO();
        // Select data from DB
        $sql = "SELECT title, color FROM `#__contentbuilder_list_states` WHERE id = (SELECT state_id FROM `#__contentbuilder_list_records` WHERE record_id = $item->colRecord)";
        $database->setQuery($sql);
        $result = $database->loadRow();

        /*
        if ($result[1] < 1)
            $result[1] = 'FFFFFF';
        if ($result[1] != 'FFFFFF') {
            $sharedStyle1 = new PHPExcel_Style();
            $sharedStyle1->applyFromArray(
                array(
                    'fill' => array(
                        'type' => PHPExcel_Style_Fill::FILL_SOLID,
                        'color' => array('rgb' => $result[1])
                    )
                )
            );
            $objPHPExcel->getActiveSheet()->setSharedStyle($sharedStyle1, "$ch" . "$i");
        }*/

        $spreadsheet->setActiveSheetIndex(0)->setCellValue([$i++, $raw], $result[0]);
    }

    // Published
    if ($col_publish > 0) {
        $database = Factory::getDBO();
        // Select data from DB
        $sql = "SELECT `state` FROM `#__content` WHERE id = (SELECT article_id FROM `#__contentbuilder_articles` WHERE id = $item->colRecord)";
        $database->setQuery($sql);
        $result = $database->loadRow();
        $spreadsheet->setActiveSheetIndex(0)->setCellValue([$i++, $raw], ($result == 1) ? 'Yes' : 'No');
    }

    foreach ($item as $key => $value) {
        if ($key != 'colRecord' && in_array(str_replace('col', '', $key), $this->data->visible_cols)) {
            $spreadsheet->setActiveSheetIndex(0)->setCellValue([$i++, $raw], $value);
        }
    }
    $raw++;
}

$spreadsheet->getDefaultStyle()->getAlignment()->setWrapText(true);
$spreadsheet->getActiveSheet()->setTitle("export-" . date('Y-m-d_Hi') . ".xlsx");

// Name file.
$filename = "export-" . date('Y-m-d_Hi', null) . ".xlsx";
$spreadsheet->setActiveSheetIndex(0);

// Auto size columns for each worksheet
foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {
    $spreadsheet->setActiveSheetIndex($spreadsheet->getIndex($worksheet));

    $sheet = $spreadsheet->getActiveSheet();
    $cellIterator = $sheet->getRowIterator()->current()->getCellIterator();
    $cellIterator->setIterateOnlyExistingCells(true);
    /** @var PHPExcel_Cell $cell */
    foreach ($cellIterator as $cell) {
        $sheet->getColumnDimension($cell->getColumn())->setAutoSize(true);
    }
}


// Redirect output to a client’s web browser (Excel5)
//header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
//header('Content-Disposition: attachment; filename=' . $filename);
//header('Cache-Control: max-age=0');
/*header('Pragma: public'); // HTTP/1.0
header("Expires: 0");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");// HTTP/1.1
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
header("Content-Type: application/download");;
header('Content-Disposition: attachment; filename=' . $filename);
header("Content-Transfer-Encoding: binary ");*/



header("Pragma: public");
header("Expires: 0");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Type: application/force-download");
header("Content-Type: application/octet-stream");
header("Content-Type: application/download");
;
header('Cache-Control: max-age=0');
header('Content-Disposition: attachment; filename=' . $filename);
header("Content-Transfer-Encoding: binary ");

ob_end_clean();
ob_start();



$objWriter = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
$objWriter->save('php://output');

exit;