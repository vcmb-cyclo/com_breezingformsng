<?php
/**
* BreezingForms - A Joomla Forms Application
* @version 1.9
* @package BreezingForms
* @copyright (C) 2008-2020 by Markus Bopp
* @license Released under the terms of the GNU General Public License
**/
defined('_JEXEC') or die('Direct Access to this location is not allowed.');

require_once($ff_admpath.'/admin/recordmanagement.class.php');

$record = new bfRecordManagement();

switch($task)
{
	case 'edit':
		$record->editRecord();
		break;
	case 'csvimport':            
		$record->getCsvImport();
		break;
	case 'setcsvimport':            
		$record->setCsvImport();
		break;  
	case 'getListRecords':
		$record->getListRecords();
		break;
	case 'getAvailableFields':
		$record->getAvailableFields();
		break;
	case 'setFlag':
		$record->setFlag();
		break;
	case 'saveFilterState':
		$record->saveFilterState();
		break;
	case 'action':
		switch(BFRequest::getVar('action','')){
                    case 'delete':
                        $record->deleteRecord();
                        break;
                }
		break;
	case 'exportPdf': 
		$record->exportPdf();
		break;
	case 'exportCsv': 
		$record->exportCsv();
		break;
	case 'exportXml': 
		$record->exportXml();
		break;
	case 'viewed':
	case 'exported':
	case 'archived':
		$record->setFlags($task);
		$record->listRecords();
		break;
	default:
		$record->listRecords();
}