<?php
/**
 * BreezingForms - A Joomla Forms Application
 * @version 5.0.0
 * @package BreezingForms
 * @copyright   Copyright (C) 2024 by XDA+GIL | Until 1.4.4 - 2004-2005 by Peter Koch
 * @license Released under the terms of the GNU General Public License
 **/

defined('_JEXEC') or die('Direct Access to this location is not allowed.');

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

if (isset($_POST['value']) && is_numeric($_POST['value'])) {

	if (ob_get_level())
		ob_end_clean();
	while (ob_get_level()) {
		ob_end_clean();
	}

	$db = Factory::getContainer()->get(DatabaseInterface::class);
	$db->setQuery("Select s.value From #__facileforms_records As r, #__facileforms_subrecords As s Where r.form = " . intval($_POST['form_id']) . " And r.id = s.record And s.value = " . $db->quote(trim($_POST['value'])) . " Limit 1");

	$res = $db->loadObject();
	$result = new stdClass;
	$result->exists = isset($res->value) && $res->value != '' ? true : false;
	$result->query = "Select s.value From #__facileforms_records As r, #__facileforms_subrecords As s Where r.form = " . intval($_POST['form_id']) . " And r.id = s.record And s.value = " . $db->quote(trim($_POST['value'])) . " Limit 1";

	header('Content-Type: application/json');
	echo json_encode($result);
	exit;
}
/**
 * BreezingForms - A Joomla Forms Application
 * @version 1.9
 * @package BreezingForms
 * @copyright (C) 2008-2020 by Markus Bopp
 * @license Released under the terms of the GNU General Public License
 **/
defined('_JEXEC') or die('Direct Access to this location is not allowed.');

require_once ($ff_compath . '/facileforms.xml.php');

class ff_importPackage extends ff_xmlPackage
{
	public $xscripts = NULL;     // script id crossreference
	public $xpieces = NULL;     // piece id crossreference
	public $oldscripts = NULL;     // old scripts relink xref
	public $oldpieces = NULL;     // old pieces relink xref
	public $scripts = NULL;     // ids of scripts imported
	public $pieces = NULL;     // ids of pieces imported
	public $forms = NULL;     // ids of forms imported
	public $elements = NULL;     // ids of elements imported
	public $menus = NULL;     // ids of menus imported
	public $pubmenus = NULL;     // ids of publisched menus imported
	public $warnings = NULL;     // warnings emitted
	public $saveErrors = NULL;

	function __construct()
	{
		parent::__construct();

		$this->doc = array(
			'elem' => array(
				'FacileFormsPackage' => array(
					'begin' => '$pkg->params[0] = array(); ',
					'attr' => array(
						'id' => '$pkg->saveParams(0, \'pkgid\', $value);',
						'type' => '$pkg->saveParams(0, \'pkgtype\', $value);',
						'version' => '$pkg->saveParams(0, \'pkgversion\', $value);'
					),
					'elem' => array(
						'name' => '$pkg->saveParams(0, $key, $value);',
						'title' => '$pkg->saveParams(0, $key, $value);',
						'version' => '$pkg->saveParams(0, $key, $value);',
						'creationDate' => '$pkg->saveParams(0, $key, $value);',
						'author' => '$pkg->saveParams(0, $key, $value);',
						'authorEmail' => '$pkg->saveParams(0, $key, $value);',
						'authorUrl' => '$pkg->saveParams(0, $key, $value);',
						'description' => '$pkg->saveParams(0, $key, $value);',
						'copyright' => '$pkg->saveParams(0, $key, $value);',
						'script' => array(
							'begin' => '$pkg->params[1] = array();',
							'attr' => array(
								'id' => '$pkg->saveParams(1, $key, $value);'
							),
							'elem' => array(
								'published' => '$pkg->saveParams(1, $key, $value);',
								'package' => '$pkg->saveParams(1, $key, $value);',
								'name' => '$pkg->saveParams(1, $key, $value);',
								'title' => '$pkg->saveParams(1, $key, $value);',
								'description' => '$pkg->saveParams(1, $key, $value);',
								'type' => '$pkg->saveParams(1, $key, $value);',
								'code' => '$pkg->saveParams(1, $key, $value);'
							),
							'end' => '$pkg->emitScript();'
						), // script
						'piece' => array(
							'begin' => '$pkg->params[1] = array();',
							'attr' => array(
								'id' => '$pkg->saveParams(1, $key, $value);'
							),
							'elem' => array(
								'published' => '$pkg->saveParams(1, $key, $value);',
								'package' => '$pkg->saveParams(1, $key, $value);',
								'name' => '$pkg->saveParams(1, $key, $value);',
								'title' => '$pkg->saveParams(1, $key, $value);',
								'description' => '$pkg->saveParams(1, $key, $value);',
								'type' => '$pkg->saveParams(1, $key, $value);',
								'code' => '$pkg->saveParams(1, $key, $value);'
							),
							'end' => '$pkg->emitPiece();'
						), // piece
						'form' => array(
							'begin' => '$pkg->params[1] = array();',
							'attr' => array(
								'id' => '$pkg->saveParams(1, $key, $value);'
							),
							'elem' => array(
								'published' => '$pkg->saveParams(1, $key, $value);',
								'runmode' => '$pkg->saveParams(1, $key, $value);',
								'package' => '$pkg->saveParams(1, $key, $value);',
								'name' => '$pkg->saveParams(1, $key, $value);',
								'title' => '$pkg->saveParams(1, $key, $value);',
								'description' => '$pkg->saveParams(1, $key, $value);',
								'class1' => '$pkg->saveParams(1, $key, $value);',
								'class2' => '$pkg->saveParams(1, $key, $value);',
								'width' => '$pkg->saveParams(1, $key, $value);',
								'widthmode' => '$pkg->saveParams(1, $key, $value);',
								'height' => '$pkg->saveParams(1, $key, $value);',
								'heightmode' => '$pkg->saveParams(1, $key, $value);',
								'pages' => '$pkg->saveParams(1, $key, $value);',
								'emailntf' => '$pkg->saveParams(1, $key, $value);',
								'emaillog' => '$pkg->saveParams(1, $key, $value);',
								'emailxml' => '$pkg->saveParams(1, $key, $value);',
								'emailadr' => '$pkg->saveParams(1, $key, $value);',
								'dblog' => '$pkg->saveParams(1, $key, $value);',
								'prevmode' => '$pkg->saveParams(1, $key, $value);',
								'prevwidth' => '$pkg->saveParams(1, $key, $value);',
								'script1cond' => '$pkg->saveParams(1, $key, $value);',
								'script1id' => '$pkg->saveParams(1, $key, $value);',
								'script1name' => '$pkg->saveParams(1, $key, $value);',
								'script1code' => '$pkg->saveParams(1, $key, $value);',
								'script2cond' => '$pkg->saveParams(1, $key, $value);',
								'script2id' => '$pkg->saveParams(1, $key, $value);',
								'script2name' => '$pkg->saveParams(1, $key, $value);',
								'script2code' => '$pkg->saveParams(1, $key, $value);',
								'piece1cond' => '$pkg->saveParams(1, $key, $value);',
								'piece1id' => '$pkg->saveParams(1, $key, $value);',
								'piece1name' => '$pkg->saveParams(1, $key, $value);',
								'piece1code' => '$pkg->saveParams(1, $key, $value);',
								'piece2cond' => '$pkg->saveParams(1, $key, $value);',
								'piece2id' => '$pkg->saveParams(1, $key, $value);',
								'piece2name' => '$pkg->saveParams(1, $key, $value);',
								'piece2code' => '$pkg->saveParams(1, $key, $value);',
								'piece3cond' => '$pkg->saveParams(1, $key, $value);',
								'piece3id' => '$pkg->saveParams(1, $key, $value);',
								'piece3name' => '$pkg->saveParams(1, $key, $value);',
								'piece3code' => '$pkg->saveParams(1, $key, $value);',
								'piece4cond' => '$pkg->saveParams(1, $key, $value);',
								'piece4id' => '$pkg->saveParams(1, $key, $value);',
								'piece4name' => '$pkg->saveParams(1, $key, $value);',
								'piece4code' => '$pkg->saveParams(1, $key, $value);',
								'opt_mail' => '$pkg->saveParams(1, $key, $value);',
								'double_opt' => '$pkg->saveParams(1, $key, $value);',
								'template_code' => '$pkg->saveParams(1, $key, $value);',
								'template_code_processed' => '$pkg->saveParams(1, $key, $value);',
								'template_areas' => '$pkg->saveParams(1, $key, $value);',
								'element' => array(
									'begin' => '$pkg->emitForm(); $pkg->params[2] = array();',
									'attr' => array(
										'id' => '$pkg->saveParams(2, $key, $value);'
									),
									'elem' => array(
										'form' => '$pkg->saveParams(2, $key, $value);',
										'page' => '$pkg->saveParams(2, $key, $value);',
										'published' => '$pkg->saveParams(2, $key, $value);',
										'name' => '$pkg->saveParams(2, $key, $value);',
										'title' => '$pkg->saveParams(2, $key, $value);',
										'type' => '$pkg->saveParams(2, $key, $value);',
										'class1' => '$pkg->saveParams(2, $key, $value);',
										'class2' => '$pkg->saveParams(2, $key, $value);',
										'logging' => '$pkg->saveParams(2, $key, $value);',
										'posx' => '$pkg->saveParams(2, $key, $value);',
										'posxmode' => '$pkg->saveParams(2, $key, $value);',
										'posy' => '$pkg->saveParams(2, $key, $value);',
										'posymode' => '$pkg->saveParams(2, $key, $value);',
										'width' => '$pkg->saveParams(2, $key, $value);',
										'widthmode' => '$pkg->saveParams(2, $key, $value);',
										'height' => '$pkg->saveParams(2, $key, $value);',
										'heightmode' => '$pkg->saveParams(2, $key, $value);',
										'flag1' => '$pkg->saveParams(2, $key, $value);',
										'flag2' => '$pkg->saveParams(2, $key, $value);',
										'data1' => '$pkg->saveParams(2, $key, $value);',
										'data2' => '$pkg->saveParams(2, $key, $value);',
										'data3' => '$pkg->saveParams(2, $key, $value);',
										'script1cond' => '$pkg->saveParams(2, $key, $value);',
										'script1id' => '$pkg->saveParams(2, $key, $value);',
										'script1name' => '$pkg->saveParams(2, $key, $value);',
										'script1code' => '$pkg->saveParams(2, $key, $value);',
										'script1flag1' => '$pkg->saveParams(2, $key, $value);',
										'script1flag2' => '$pkg->saveParams(2, $key, $value);',
										'script2cond' => '$pkg->saveParams(2, $key, $value);',
										'script2id' => '$pkg->saveParams(2, $key, $value);',
										'script2name' => '$pkg->saveParams(2, $key, $value);',
										'script2code' => '$pkg->saveParams(2, $key, $value);',
										'script2flag1' => '$pkg->saveParams(2, $key, $value);',
										'script2flag2' => '$pkg->saveParams(2, $key, $value);',
										'script2flag3' => '$pkg->saveParams(2, $key, $value);',
										'script2flag4' => '$pkg->saveParams(2, $key, $value);',
										'script2flag5' => '$pkg->saveParams(2, $key, $value);',
										'script3cond' => '$pkg->saveParams(2, $key, $value);',
										'script3id' => '$pkg->saveParams(2, $key, $value);',
										'script3name' => '$pkg->saveParams(2, $key, $value);',
										'script3code' => '$pkg->saveParams(2, $key, $value);',
										'script3msg' => '$pkg->saveParams(2, $key, $value);',
										'mailback' => '$pkg->saveParams(2, $key, $value);',
										'mailbackfile' => '$pkg->saveParams(2, $key, $value);'
									),
									'end' => '$pkg->emitElement();'
								), // element
							), // elem
							'end' => '$pkg->emitForm();'
						), // form
						'compmenu' => array(
							'begin' => '$pkg->params[1] = array();',
							'attr' => array(
								'id' => '$pkg->saveParams(1, $key, $value);'
							),
							'elem' => array(
								'published' => '$pkg->saveParams(1, $key, $value);',
								'package' => '$pkg->saveParams(1, $key, $value);',
								'img' => '$pkg->saveParams(1, $key, $value);',
								'title' => '$pkg->saveParams(1, $key, $value);',
								'name' => '$pkg->saveParams(1, $key, $value);',
								'page' => '$pkg->saveParams(1, $key, $value);',
								'frame' => '$pkg->saveParams(1, $key, $value);',
								'border' => '$pkg->saveParams(1, $key, $value);',
								'params' => '$pkg->saveParams(1, $key, $value);',
								'compmenu' => array(
									'begin' => '$pkg->params[2] = array();',
									'attr' => array(
										'id' => '$pkg->saveParams(2, $key, $value);'
									),
									'elem' => array(
										'published' => '$pkg->saveParams(2, $key, $value);',
										'package' => '$pkg->saveParams(2, $key, $value);',
										'img' => '$pkg->saveParams(2, $key, $value);',
										'title' => '$pkg->saveParams(2, $key, $value);',
										'name' => '$pkg->saveParams(2, $key, $value);',
										'page' => '$pkg->saveParams(2, $key, $value);',
										'frame' => '$pkg->saveParams(2, $key, $value);',
										'border' => '$pkg->saveParams(2, $key, $value);',
										'params' => '$pkg->saveParams(2, $key, $value);'
									),
									'end' => '$pkg->emitCompsubmenu();'
								), // element
							), // elem
							'end' => '$pkg->emitCompmenu();'
						) // form

					) // elem
				) // FacileFormsPackage
			)
		); // $this->doc

	} // ff_importPackage


	// Import package file.
	function import($filename)
	{
		global $errors, $errmode;
		$db = Factory::getContainer()->get(DatabaseInterface::class);

		// Import crossreferences
		$this->xscripts =
			$this->xpieces =
			// old package backlinks
			$this->oldscripts =
			$this->oldpieces =
			// insert trace
			$this->scripts =
			$this->pieces =
			$this->forms =
			$this->elements =
			$this->menus =
			// misc
			$this->warnings = array();
		$this->pubmenus = 0;

		if ($errmode == 'log') {
			$this->saveErrors = $errors;
			$errors = array();
		} // if

		$ok = parent::import($filename);

		if (!$ok) {
			// Fail case
			$this->rollback();
			if ($errmode == 'log') {
				if (count($this->saveErrors))
					$errors = array_merge($this->saveErrors, $errors);
				$errors[] = BFText::_('COM_BREEZINGFORMS_INSTALLER') . ': ' . $this->error;
			} // if
		} else {
			// Success case
			if ($this->pubmenus > 0)
				updateComponentMenus();


			$id = $this->getText(0, 'pkgid');
			if ($id != '') {
				relinkScripts($this->oldscripts);
				relinkPieces($this->oldpieces);
				savePackage(
					$id,
					$this->getText(0, 'name'),
					$this->getText(0, 'title'),
					$this->getText(0, 'version'),
					$this->getText(0, 'creationDate'),
					$this->getText(0, 'author'),
					$this->getText(0, 'authorEmail'),
					$this->getText(0, 'authorUrl'),
					$this->getText(0, 'description'),
					$this->getText(0, 'copyright')
				);
			} // if

			require_once(JPATH_SITE . '/administrator/components/com_breezingforms/admin/quickmode.class.php');
			require_once(JPATH_SITE . '/administrator/components/com_breezingforms/libraries/Zend/Json/Decoder.php');
			require_once(JPATH_SITE . '/administrator/components/com_breezingforms/libraries/Zend/Json/Encoder.php');

			foreach ($this->forms as $form_id) {
				$db->setQuery("Select template_areas, template_code_processed, template_code From #__facileforms_forms Where id = " . intval($form_id));
				$row = $db->loadObject();
				if (trim($row->template_code) != '') {
					$areas = Zend_Json::decode(bf_b64dec($row->template_areas));
					$i = 0;
					foreach ($areas as $area) {
						$j = 0;
						foreach ($area['elements'] as $element) {
							$areas[$i]['elements'][$j]['dbId'] = 0;
							$j++;
						}
						$i++;
					}

					$template_areas = Zend_Json::encode($areas);
					$template_code = $row->template_code;

					if ($row->template_code_processed == 'QuickMode') {
						$dataObject = Zend_Json::decode(bf_b64dec($row->template_code));
						$this->resetQuickModeDbId($dataObject);
						$template_code = bf_b64enc(Zend_Json::encode($dataObject));
					}

					$db->setQuery("Update #__facileforms_forms Set template_code = " . $db->Quote($template_code) . ", template_areas = " . $db->Quote($template_areas) . " Where id = " . intval($form_id));
					$db->execute();

					if ($row && $row->template_code_processed == 'QuickMode') {
						$quickMode = new QuickMode();
						$quickMode->save(
							$form_id,
							Zend_Json::decode(bf_b64dec($template_code))
						);
					}
				}
			}
		} // if
		return $ok;
	} // import


	function rollback()
	{
		global $errors, $errmode;

		$saveErrors = $errors;
		$saveErrmode = $errmode;
		$errmode = 'log';

		if (count($this->menus))
			_ff_query(
				"delete from `#__facileforms_compmenus` " .
				"where id in(" . implode(',', $this->menus) . ")"
			);
		if (count($this->elements))
			_ff_query(
				"delete from `#__facileforms_elements` " .
				"where id in(" . implode(',', $this->elements) . ")"
			);
		if (count($this->forms))
			_ff_query(
				"delete from `#__facileforms_forms` " .
				"where id in(" . implode(',', $this->forms) . ")"
			);
		if (count($this->pieces))
			_ff_query(
				"delete from `#__facileforms_forms` " .
				"where id in(" . implode(',', $this->pieces) . ")"
			);
		if (count($this->scripts))
			_ff_query(
				"delete from `#__facileforms_forms` " .
				"where id in(" . implode(',', $this->scripts) . ")"
			);
		$errors = $saveErrors;
		$errmode = $saveErrmode;
	} // rollback

	function dropPackage($id)
	{
		$db = Factory::getContainer()->get(DatabaseInterface::class);
		if ($this->hasErrors())
			return;
		
		$this->oldscripts = _ff_select("select id, name from #__facileforms_scripts where package =  " . $db->Quote($id) . "");
		$this->oldpieces = _ff_select("select id, name from #__facileforms_pieces where package =  " . $db->Quote($id) . "");
		dropPackage($id); // the one in admin.facileforms.php
	} // dropPackage

	function emitScript()
	{
		global $database;
		$database = Factory::getContainer()->get(DatabaseInterface::class);

		// Sanity check
		if ($this->hasErrors())
			return;
		// save new row
		$row = new facileFormsScripts($database);
		$row->published = $this->getInt(1, 'published', 1);
		$row->package = $this->getText(1, 'package');
		$row->name = $this->getText(1, 'name', BFText::_('COM_BREEZINGFORMS_INSTALLER_UNKNOWN'));
		$row->title = $this->getText(1, 'title', BFText::_('COM_BREEZINGFORMS_INSTALLER_UNKNOWN'));
		$row->description = $this->getText(1, 'description');
		$row->type = $this->getText(1, 'type', 'Untyped');
		$row->code = $this->getText(1, 'code');
		if (!$row->store()) {
			$this->setError($row->getError(), true);
			return;
		} // if
		// remember me
		$this->scripts[] = $row->id;
		// add to crossreference
		if (array_key_exists('id', $this->params[1]))
			$this->xscripts[] = array($this->params[1]['id'], $row->id);
	} // emitScript

	function emitPiece()
	{
		global $database;
		$database = Factory::getContainer()->get(DatabaseInterface::class);

		// Sanity check
		if ($this->hasErrors())
			return;
		
		// Save new row
		$row = new facileFormsPieces($database);
		$row->published = $this->getInt(1, 'published', 1);
		$row->package = $this->getText(1, 'package');
		$row->name = $this->getText(1, 'name', BFText::_('COM_BREEZINGFORMS_INSTALLER_UNKNOWN'));
		$row->title = $this->getText(1, 'title', BFText::_('COM_BREEZINGFORMS_INSTALLER_UNKNOWN'));
		$row->description = $this->getText(1, 'description');
		$row->type = $this->getText(1, 'type', 'Untyped');
		$row->code = $this->getText(1, 'code');

		if (!$row->store()) {
			$this->setError($row->getError(), true);
			return;
		} // if
		
		// Remember me
		$this->pieces[] = $row->id;
		
		// Add to crossreference
		if (array_key_exists('id', $this->params[1]))
			$this->xpieces[] = array($this->params[1]['id'], $row->id);
	} // emitPiece

	function emitForm()
	{
		global $database;
		$database = Factory::getContainer()->get(DatabaseInterface::class);

		// Sanity check
		if ($this->hasErrors())
			return;
		
		if (!array_key_exists('emitted', $this->params[1])) {
			// save new row
			$row = new facileFormsForms($database);
			$database->setQuery("select max(ordering)+1 from #__facileforms_forms");
			$row->ordering = $database->loadResult();
			$row->published = $this->getInt(1, 'published', 1);
			$row->runmode = $this->getInt(1, 'runmode');
			$row->package = $this->getText(1, 'package');
			$row->name = $this->getText(1, 'name', BFText::_('COM_BREEZINGFORMS_INSTALLER_UNKNOWN'));
			$row->title = $this->getText(1, 'title', BFText::_('COM_BREEZINGFORMS_INSTALLER_UNKNOWN'));
			$row->description = $this->getText(1, 'description');

			$row->template_code = '';
			$row->template_code_processed = '';
			$row->template_areas = '';
			if ($this->getText(1, 'template_code') != '') {
				$row->template_code = bf_b64dec($this->getText(1, 'template_code'));
				$row->template_code_processed = bf_b64dec($this->getText(1, 'template_code_processed'));
				$row->template_areas = $this->getText(1, 'template_areas');
			}

			$row->class1 = $this->getText(1, 'class1');
			$row->class2 = $this->getText(1, 'class2');
			$row->width = $this->getInt(1, 'width');
			$row->widthmode = $this->getInt(1, 'widthmode');
			$row->height = $this->getInt(1, 'height');
			$row->heightmode = $this->getInt(1, 'heightmode');
			$row->pages = $this->getInt(1, 'pages', 1);
			$row->emailntf = $this->getInt(1, 'emailntf', 1);
			$row->emaillog = $this->getInt(1, 'emaillog', 1);
			$row->emailxml = $this->getInt(1, 'emailxml');
			$row->emailadr = $this->getText(1, 'emailadr');
			$row->dblog = $this->getInt(1, 'dblog', 1);
			$row->prevmode = $this->getInt(1, 'prevmode', 2);
			$row->prevwidth = $this->getInt(1, 'prevwidth', '');
			$row->double_opt = $this->getInt(1, 'double_opt', 0);
			$row->opt_mail = $this->getInt(1, 'opt_mail', '');
			$row->filter_state = '';
			$this->getScriptPiece(1, $row, '#__facileforms_scripts', 'script1', $this->xscripts);
			if ($this->hasErrors())
				return;
			$this->getScriptPiece(1, $row, '#__facileforms_scripts', 'script2', $this->xscripts);
			if ($this->hasErrors())
				return;
			$this->getScriptPiece(1, $row, '#__facileforms_pieces', 'piece1', $this->xpieces);
			if ($this->hasErrors())
				return;
			$this->getScriptPiece(1, $row, '#__facileforms_pieces', 'piece2', $this->xpieces);
			if ($this->hasErrors())
				return;
			$this->getScriptPiece(1, $row, '#__facileforms_pieces', 'piece3', $this->xpieces);
			if ($this->hasErrors())
				return;
			$this->getScriptPiece(1, $row, '#__facileforms_pieces', 'piece4', $this->xpieces);
			if ($this->hasErrors())
				return;
			if (!$row->store()) {
				$this->setError($row->getError(), true);
				return;
			} // if
			// final tasks
			$this->forms[] = $row->id;
			$this->params[1]['form_id'] = $row->id;
			$this->params[1]['elem_ord'] = 0;
			$this->params[1]['emitted'] = true;
		} // if
	} // emitForm

	public function resetQuickModeDbId(&$dataObject)
	{
		$db = Factory::getContainer()->get(DatabaseInterface::class);

		if (isset($dataObject['attributes']) && isset($dataObject['properties'])) {
			$mdata = $dataObject['properties'];
			if ($mdata['type'] == 'element') {
				//print_r($mdata);
				//exit;
				if (!isset($mdata['validationFunctionName'])) {
					$mdata['validationFunctionName'] = '';
				}
				$db->setQuery("Select id From #__facileforms_scripts Where `name` = " . $db->Quote($mdata['validationFunctionName']) . " Limit 1");
				$id = $db->loadResult();
				if ($id) {
					$mdata['validationId'] = $id;
				} else {
					$mdata['validationId'] = 0;
				}
				if (!isset($mdata['initScript'])) {
					$mdata['initScript'] = '';
				}
				$db->setQuery("Select id From #__facileforms_scripts Where `name` = " . $db->Quote($mdata['initScript']) . " Limit 1");
				$id = $db->loadResult();
				if ($id) {
					$mdata['initId'] = $id;
				} else {
					$mdata['initId'] = 0;
				}
				if (!isset($mdata['actionFunctionName'])) {
					$mdata['actionFunctionName'] = '';
				}
				$db->setQuery("Select id From #__facileforms_scripts Where `name` = " . $db->Quote($mdata['actionFunctionName']) . " Limit 1");
				$id = $db->loadResult();
				if ($id) {
					$mdata['actionId'] = $id;
				} else {
					$mdata['actionId'] = 0;
				}
				$mdata['dbId'] = 0;
				$dataObject['properties'] = $mdata;
			}
		}

		if (isset($dataObject['children']) && count($dataObject['children']) != 0) {
			$childrenAmount = count($dataObject['children']);
			for ($i = 0; $i < $childrenAmount; $i++) {
				$this->resetQuickModeDbId($dataObject['children'][$i]);
			}
		}
	}

	function getScriptPiece($pid, &$row, $table, $tag, &$xref)
	{
		$db = Factory::getContainer()->get(DatabaseInterface::class);
		$cond = $this->getInt($pid, $tag . 'cond');
		$id = NULL;
		$code = NULL;
		switch ($cond) {
			case 1:
				$idx = $this->getInt($pid, $tag . 'id');

				// first priority: xref
				if (count($xref))
					foreach ($xref as $x)
						if ($x[0] == $idx) {
							$id = $x[1];
							break;
						} // if
				if ($id == NULL) {
					$name = $this->getText($pid, $tag . 'name');

					if ($name != '') {
						// search published
						$id = _ff_selectValue(
							"select id from `" . $table . "`" .
							"where name=" . $db->Quote($name) . " and published=1 " .
							"order by type, title, id"
						);
						if ($this->hasErrors())
							return;
					} // if

					if ($id == NULL) {
						if ($name != '') {
							// search also unpublished
							$id = _ff_selectValue(
								"select id from `" . $table . "` " .
								"where name=" . $db->Quote($name) . " " .
								"order by type, title, id"
							);
							if ($this->hasErrors())
								return;
						} // if
						if ($id == NULL) {
							// finally change to type 2 and emit warning
							$cond = 2;
							$code = '// ' . $tag . ' ' . $idx . '/' . $name . ' ' . BFText::_('COM_BREEZINGFORMS_INSTALLER_TAGNOTFOUND');
							$nm = $this->getText($pid, 'name');
							$this->warnings[] = $nm . ': ' . $tag . ' ' . $idx . '/' . $name . ' ' . BFText::_('COM_BREEZINGFORMS_INSTALLER_TAGNOTFOUND');
						} // if
					} // if
				} // if
				break;
			case 2:
				$code = $this->getText($pid, $tag . 'code');
				break;
			default:
		} // if
		eval (
			'$row->' . $tag . 'cond = $cond; ' .
			'$row->' . $tag . 'id   = $id; ' .
			'$row->' . $tag . 'code = $code;'
			);
	} // getScriptPiece

	function emitElement()
	{
		global $database;
		$database = Factory::getContainer()->get(DatabaseInterface::class);
		// sanity check
		if ($this->hasErrors())
			return;
		// save new row
		$row = new facileFormsElements($database);
		$row->form = $this->params[1]['form_id'];
		$row->ordering = ++$this->params[1]['elem_ord'];
		$row->page = $this->getInt(2, 'page', 1);
		$row->published = $this->getInt(2, 'published', 1);
		$row->title = $this->getText(2, 'title', BFText::_('COM_BREEZINGFORMS_INSTALLER_UNKNOWN'));
		$row->name = $this->getText(2, 'name', BFText::_('COM_BREEZINGFORMS_INSTALLER_UNKNOWN'));
		$row->type = $this->getText(2, 'type', 'Static Text/HTML');
		$row->class1 = $this->getText(2, 'class1');
		$row->class2 = $this->getText(2, 'class2');
		$row->logging = $this->getInt(2, 'logging', 1);
		$row->posx = $this->getInt(2, 'posx', NULL);
		$row->posxmode = $this->getInt(2, 'posxmode');
		$row->posy = $this->getInt(2, 'posy', NULL);
		$row->posymode = $this->getInt(2, 'posymode');
		$row->width = $this->getInt(2, 'width', NULL);
		$row->widthmode = $this->getInt(2, 'widthmode');
		$row->height = $this->getInt(2, 'height', NULL);
		$row->heightmode = $this->getInt(2, 'heightmode');
		$row->flag1 = $this->getInt(2, 'flag1');
		$row->flag2 = $this->getInt(2, 'flag2');
		$row->data1 = $this->getText(2, 'data1');
		$row->data2 = $this->getText(2, 'data2');
		$row->data3 = $this->getText(2, 'data3');

		$this->getScriptPiece(2, $row, '#__facileforms_scripts', 'script1', $this->xscripts);
		$row->script1flag1 = $this->getInt(2, 'script1flag1');
		$row->script1flag2 = $this->getInt(2, 'script1flag2');

		$this->getScriptPiece(2, $row, '#__facileforms_scripts', 'script2', $this->xscripts);
		$row->script2flag1 = $this->getInt(2, 'script2flag1');
		$row->script2flag2 = $this->getInt(2, 'script2flag2');
		$row->script2flag3 = $this->getInt(2, 'script2flag3');
		$row->script2flag4 = $this->getInt(2, 'script2flag4');
		$row->script2flag5 = $this->getInt(2, 'script2flag5');

		$this->getScriptPiece(2, $row, '#__facileforms_scripts', 'script3', $this->xscripts);
		$row->script3msg = $this->getText(2, 'script3msg');
		$row->mailback = $this->getInt(2, 'mailback');

		$row->mailbackfile = '';
		if ($this->getText(2, 'mailbackfile') != '') {
			$row->mailbackfile = $this->getText(2, 'mailbackfile');
		}

		if (!$row->store()) {
			$this->setError($row->getError(), true);
			return;
		} // if
		$this->elements[] = $row->id;
	} // emitElement

	function emitCompsubmenu()
	{
		global $database;
		$database = Factory::getContainer()->get(DatabaseInterface::class);
		$this->emitCompmenu();
		if ($this->hasErrors())
			return;
		// save new row
		$row = new facileFormsMenus($database);
		$row->parent = $this->params[1]['menu_id'];
		$row->ordering = ++$this->params[1]['submenu_ord'];
		$row->published = $this->getInt(2, 'published', 1);
		$row->package = $this->getText(2, 'package');
		$row->img = $this->getText(2, 'img');
		$row->title = $this->getText(2, 'title', BFText::_('COM_BREEZINGFORMS_INSTALLER_UNKNOWN'));
		$row->name = $this->getText(2, 'name');
		$row->page = $this->getInt(2, 'page', 1);
		$row->frame = $this->getInt(2, 'frame');
		$row->border = $this->getInt(2, 'border');
		$row->params = $this->getText(2, 'params');
		if (!$row->store()) {
			$this->setError($row->getError(), true);
			return;
		} // if
		$this->menus[] = $row->id;
	} // emitCompsubmenu

	function emitCompmenu()
	{
		global $database;
		$database = Factory::getContainer()->get(DatabaseInterface::class);
		// sanity check
		if ($this->hasErrors())
			return;
		if (!array_key_exists('emitted', $this->params[1])) {
			// save new row
			$row = new facileFormsMenus($database);
			$database->setQuery("select max(ordering)+1 from #__facileforms_compmenus");
			$row->ordering = $database->loadResult();
			$row->published = $this->getInt(1, 'published', 1);
			$row->package = $this->getText(1, 'package');
			$row->img = $this->getText(1, 'img');
			$row->title = $this->getText(1, 'title', BFText::_('COM_BREEZINGFORMS_INSTALLER_UNKNOWN'));
			$row->name = $this->getText(1, 'name');
			$row->page = $this->getInt(1, 'page', 1);
			$row->frame = $this->getInt(1, 'frame');
			$row->border = $this->getInt(1, 'border');
			$row->params = $this->getText(1, 'params');
			if (!$row->store()) {
				$this->setError($row->getError(), true);
				return;
			} // if
			$this->menus[] = $row->id;
			if ($row->published)
				$this->pubmenus++;
			// final tasks
			$this->params[1]['menu_id'] = $row->id;
			$this->params[1]['submenu_ord'] = 0;
			$this->params[1]['emitted'] = true;
		} // if
	} // emitCompmenu

	function hasErrors()
	{
		global $errors;
		return $this->error || count($errors);
	} // hasErrors

} // class ff_InstallPackage
?>