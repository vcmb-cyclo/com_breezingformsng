<?php

/**
 * BreezingForms - A Joomla Forms Application
 * @version 1.9
 * @package BreezingForms
 * @copyright (C) 2008-2020 by Markus Bopp
 * @license Released under the terms of the GNU General Public License
 **/
defined('_JEXEC') or die('Direct Access to this location is not allowed.');

use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

require_once($ff_admpath . '/admin/script.html.php');

class facileFormsScript
{
	static function edit($option, $pkg, $ids)
	{
		$database = Factory::getContainer()->get(DatabaseInterface::class);
		ArrayHelper::toInteger($ids);
		$typelist = array();
		$typelist[] = array('Untyped', BFText::_('COM_BREEZINGFORMS_SCRIPTS_UNTYPED'));
		$typelist[] = array('Element Init', BFText::_('COM_BREEZINGFORMS_SCRIPTS_ELEMENTINIT'));
		$typelist[] = array('Element Action', BFText::_('COM_BREEZINGFORMS_SCRIPTS_ELEMENTACTION'));
		$typelist[] = array('Element Validation', BFText::_('COM_BREEZINGFORMS_SCRIPTS_ELEMENTVALID'));
		$typelist[] = array('Form Init', BFText::_('COM_BREEZINGFORMS_SCRIPTS_FORMINIT'));
		$typelist[] = array('Form Submitted', BFText::_('COM_BREEZINGFORMS_SCRIPTS_FORMSUBMIT'));
		$row = new facileFormsScripts($database);
		if (count($ids)) {
			$row->load($ids[0]);
		} else {
			$row->type = $typelist[0];
			$row->package = $pkg;
			$row->published = 1;
		} // if
		HTML_facileFormsScript::edit($option, $pkg, $row, $typelist);
	} // edit


	// ✅ FORCER le champ code en RAW (conserve < et >)
	static function save($option, $pkg)
	{
		$app = Factory::getApplication();

		// Lire le body brut
		$rawBody = file_get_contents('php://input');

		// Parser comme application/x-www-form-urlencoded
		$post = [];
		parse_str($rawBody, $post);

		// Récupérer code tel qu'envoyé
		$code = $post['code'] ?? '';

		$database = Factory::getContainer()->get(DatabaseInterface::class);
		$row      = new facileFormsScripts($database);

		// bind du reste
		if (!$row->bind($_POST)) {
			echo "<script> alert('" . $row->getError() . "'); window.history.go(-1); </script>\n";
			exit();
		}

		// Forcer code non filtré
		$row->code = $code;

		$now = Factory::getDate()->toSql();
		$userId = (string) Factory::getApplication()->getIdentity()->username;

		if (empty($row->id)) {
			if (empty($row->created)) {
				$row->created = $now;
			}
			if (empty($row->created_by)) {
				$row->created_by = $userId;
			}
		}

		$row->modified = $now;
		$row->modified_by = $userId;

		if (!$row->store()) {
			echo "<script> alert('" . $row->getError() . "'); window.history.go(-1); </script>\n";
			exit();
		}

		$app->enqueueMessage(BFText::_('COM_BREEZINGFORMS_SCRIPTS_SAVED'));
		$app->redirect("index.php?option=$option&act=managescripts&pkg=$pkg");
	}


	static function cancel($option, $pkg)
	{
		Factory::getApplication()->redirect("index.php?option=$option&act=managescripts&pkg=$pkg");
	} // cancel

	static function copy($option, $pkg, $ids)
	{
		$database = Factory::getContainer()->get(DatabaseInterface::class);
		$total = count($ids);
		$row = new facileFormsScripts($database);
		if (count($ids)) foreach ($ids as $id) {
			$row->load(intval($id));
			$row->id       = NULL;
			$row->created = Factory::getDate()->toSql();
			$row->created_by = (string) Factory::getApplication()->getIdentity()->username;
			$row->modified = $row->created;
			$row->modified_by = $row->created_by;
			$row->store();
		} // foreach
		$msg = $total . ' ' . BFText::_('COM_BREEZINGFORMS_SCRIPTS_SUCCOPIED');
		Factory::getApplication()->redirect("index.php?option=$option&act=managescripts&pkg=$pkg&mosmsg=$msg");
	} // copy

	static function del($option, $pkg, $ids)
	{
		$database = Factory::getContainer()->get(DatabaseInterface::class);
		if (count($ids)) {
			$ids = implode(',', $ids);
			$database->setQuery("delete from #__facileforms_scripts where id in ($ids)");
			try {
				$database->execute();
			} catch (RuntimeException $e) {
				echo "<script> alert('" . $e->getMessage() . "'); window.history.go(-1); </script>\n";
			}
		} // if
		Factory::getApplication()->redirect("index.php?option=$option&act=managescripts&pkg=$pkg");
	} // del

	static function publish($option, $pkg, $ids, $publish)
	{
		$database = Factory::getContainer()->get(DatabaseInterface::class);
		ArrayHelper::toInteger($ids);
		$ids = implode(',', $ids);
		$database->setQuery(
			"update #__facileforms_scripts set published=" . $database->Quote($publish) . " where id in ($ids)"
		);
		try {
			$database->execute();
		} catch (RuntimeException $e) {
			echo "<script> alert('" . $e->getMessage() . "'); window.history.go(-1); </script>\n";
			exit();
		}

		Factory::getApplication()->redirect("index.php?option=$option&act=managescripts&pkg=$pkg");
	} // publish

	static function listitems($option, $pkg)
	{
		$app = Factory::getApplication();
		$session = $app->getSession();
		$database = Factory::getContainer()->get(DatabaseInterface::class);

		$database->setQuery(
			"select distinct  package as name " .
				"from #__facileforms_scripts " .
				"where package is not null and package!='' " .
				"order by name"
		);

		try {
			$pkgs = $database->loadObjectList();
		} catch (\Exception $e) {
			echo $e->getMessage();
			return false;
		} // try

		$pkgok = $pkg == '';
		if (!$pkgok && count($pkgs)) foreach ($pkgs as $p) if ($p->name == $pkg) {
			$pkgok = true;
			break;
		}
		if (!$pkgok) $pkg = '';
		$pkglist = array();
		$pkglist[] = array($pkg == '', '');
		if (count($pkgs)) foreach ($pkgs as $p) $pkglist[] = array($p->name == $pkg, $p->name);

		$sortReq = BFRequest::getVar('sort', null);
		$dirReq = BFRequest::getVar('dir', null);
		if ($sortReq === null) {
			$sort = (string) $session->get('bf.scripts_sort', 'name');
		} else {
			$sort = (string) $sortReq;
			$session->set('bf.scripts_sort', $sort);
		}
		if ($dirReq === null) {
			$dir = strtoupper((string) $session->get('bf.scripts_dir', 'ASC'));
		} else {
			$dir = strtoupper((string) $dirReq);
			$session->set('bf.scripts_dir', $dir);
		}
		$allowedSorts = array(
			'id' => 'id',
			'title' => 'title',
			'name' => 'name',
			'type' => 'type',
			'description' => 'description',
			'modified' => 'modified',
			'published' => 'published',
		);
		$sortField = isset($allowedSorts[$sort]) ? $allowedSorts[$sort] : 'name';
		$dir = $dir === 'DESC' ? 'DESC' : 'ASC';
		$orderBy = "order by {$sortField} {$dir}, id desc";

		$database->setQuery(
			"select * from #__facileforms_scripts " .
				"where package =  " . $database->Quote($pkg) . " " .
				$orderBy
		);

		try {
			$rows = $database->loadObjectList();
		} catch (\Exception $e) {
			echo $e->getMessage();
			return false;
		} // try


		HTML_facileFormsScript::listitems($option, $rows, $pkglist, $pkg);
	} // listitems

} // class facileFormsScript
