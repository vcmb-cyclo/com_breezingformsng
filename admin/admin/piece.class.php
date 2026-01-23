<?php
/**
 * BreezingForms - A Joomla Forms Application
 * @version 5.0.0
 * @package BreezingForms
 * @copyright   Copyright (C) 2024 by XDA+GIL | 2008-2020 by Markus Bopp
 * @license Released under the terms of the GNU General Public License
 **/
defined('_JEXEC') or die('Direct Access to this location is not allowed.');

use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

require_once ($ff_admpath . '/admin/piece.html.php');

class BFAdminPieceTestContext
{
	private $db;
	public $formrow;
	public $form_id;

	public function __construct($db)
	{
		$this->db = $db;
		$this->formrow = (object) array('id' => 0, 'name' => '');
		$this->form_id = 0;
	}

	public function execPieceByName($name)
	{
		if ($name === '') {
			return null;
		}

		$this->db->setQuery(
			"SELECT code FROM #__facileforms_pieces WHERE name = " . $this->db->Quote($name) . " LIMIT 1"
		);
		$code = (string) $this->db->loadResult();
		if ($code === '') {
			return null;
		}

		$code = trim($code);
		$code = preg_replace('/^<\\?php\\s*/', '', $code);
		$code = preg_replace('/\\?>\\s*$/', '', $code);

		$runner = \Closure::bind(function () use ($code) {
			if ($code !== '') {
				eval($code);
			}
		}, $this, static::class);
		$runner();

		return true;
	}
}

class facileFormsPiece
{
	static function edit($option, $pkg, $ids)
	{
		$database = Factory::getContainer()->get(DatabaseInterface::class);
		ArrayHelper::toInteger($ids);
		$typelist = array();
		$typelist[] = array('Untyped', BFText::_('COM_BREEZINGFORMS_PIECES_UNTYPED'));
		$typelist[] = array('Before Form', BFText::_('COM_BREEZINGFORMS_PIECES_BEFOREFORM'));
		$typelist[] = array('After Form', BFText::_('COM_BREEZINGFORMS_PIECES_AFTERFORM'));
		$typelist[] = array('Begin Submit', BFText::_('COM_BREEZINGFORMS_PIECES_BEGINSUBMIT'));
		$typelist[] = array('End Submit', BFText::_('COM_BREEZINGFORMS_PIECES_ENDSUBMIT'));
		$row = new facileFormsPieces($database);
		if (count($ids)) {
			$row->load($ids[0]);
		} else {
			$row->type = $typelist[0];
			$row->package = $pkg;
			$row->published = 1;
		} // if
		HTML_facileFormsPiece::edit($option, $pkg, $row, $typelist);
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
		$row      = new facileFormsPieces($database);

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

		$app->enqueueMessage(BFText::_('COM_BREEZINGFORMS_PIECES_SAVED'));
		$app->redirect("index.php?option=$option&act=managepieces&pkg=$pkg");
	}

	static function cancel($option, $pkg)
	{
		Factory::getApplication()->redirect("index.php?option=$option&act=managepieces&pkg=$pkg");
	} // cancel


	static function copy($option, $pkg, $ids)
	{
		$database = Factory::getContainer()->get(DatabaseInterface::class);
		ArrayHelper::toInteger($ids);
		$total = count($ids);
		$row = new facileFormsPieces($database);
		if (count($ids))
			foreach ($ids as $id) {
				$row->load(intval($id));
				$row->id = NULL;
				$row->created = Factory::getDate()->toSql();
				$row->created_by = (string) Factory::getApplication()->getIdentity()->username;
				$row->modified = $row->created;
				$row->modified_by = $row->created_by;
				$row->store();
			} // foreach
		$msg = $total . ' ' . BFText::_('COM_BREEZINGFORMS_PIECES_SUCCOPIED');
		Factory::getApplication()->redirect("index.php?option=$option&act=managepieces&pkg=$pkg&mosmsg=$msg");
	} // copy


	static function del($option, $pkg, $ids)
	{
		$database = Factory::getContainer()->get(DatabaseInterface::class);
		ArrayHelper::toInteger($ids);
		if (count($ids)) {
			$ids = implode(',', $ids);
			$database->setQuery("delete from #__facileforms_pieces where id in ($ids)");
			try {
				$database->execute();
			} catch (RuntimeException $e) {
				echo "<script> alert('" . $e->getMessage() . "'); window.history.go(-1); </script>\n";
			}
		} // if
		Factory::getApplication()->redirect("index.php?option=$option&act=managepieces&pkg=$pkg");
	} // del


	static function publish($option, $pkg, $ids, $publish)
	{
		$database = Factory::getContainer()->get(DatabaseInterface::class);
		ArrayHelper::toInteger($ids);
		$ids = implode(',', $ids);
		$database->setQuery(
			"update #__facileforms_pieces set published='$publish' where id in ($ids)"
		);
		try {
			$database->execute();
		} catch (RuntimeException $e) {
			echo "<script> alert('" . $e->getMessage() . "'); window.history.go(-1); </script>\n";
			return;
		}

		Factory::getApplication()->redirect("index.php?option=$option&act=managepieces&pkg=$pkg");
	} // publish


	static function listitems($option, $pkg)
	{
		$database = Factory::getContainer()->get(DatabaseInterface::class);

		$database->setQuery(
			"select distinct  package as name " .
			"from #__facileforms_pieces " .
			"where package is not null and package != '' " .
			"order by name"
		);


		try {
			$pkgs = $database->loadObjectList();
		} catch (Exception $e) {
			echo $e->getCode() . ' : ' . $e->getMessage();
			return false;
		}


		$pkgok = $pkg == '';
		if (!$pkgok && count($pkgs))
			foreach ($pkgs as $p)
				if ($p->name == $pkg) {
					$pkgok = true;
					break;
				}

		if (!$pkgok)
			$pkg = '';
		$pkglist = array();
		$pkglist[] = array($pkg == '', '');
		if (count($pkgs))
			foreach ($pkgs as $p)
				$pkglist[] = array($p->name == $pkg, $p->name);

		$app = Factory::getApplication();
		$session = $app->getSession();
		$showInternalReq = BFRequest::getVar('show_internal', null);
		if ($showInternalReq === null) {
			$showInternal = (int) $session->get('bf.show_internal_pieces', 0);
		} else {
			$showInternal = (int) $showInternalReq;
			$session->set('bf.show_internal_pieces', $showInternal);
		}
		$internalFilter = $showInternal ? '' : "and name NOT LIKE '\\_%' ";
		$sortReq = BFRequest::getVar('sort', null);
		$dirReq = BFRequest::getVar('dir', null);
		if ($sortReq === null) {
			$sort = (string) $session->get('bf.pieces_sort', 'name');
		} else {
			$sort = (string) $sortReq;
			$session->set('bf.pieces_sort', $sort);
		}
		if ($dirReq === null) {
			$dir = strtoupper((string) $session->get('bf.pieces_dir', 'ASC'));
		} else {
			$dir = strtoupper((string) $dirReq);
			$session->set('bf.pieces_dir', $dir);
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
			"select * from #__facileforms_pieces " .
			"where package =  " . $database->Quote($pkg) . " " .
			$internalFilter .
			$orderBy
		);
		$rows = $database->loadObjectList();

		try {
			$rows = $database->loadObjectList();
		} catch (Exception $e) {
			echo $e->getCode() . ' : ' . $e->getMessage();
			return false;
		}

		HTML_facileFormsPiece::listitems($option, $rows, $pkglist, $pkg, $showInternal);
	} // listitems

	static function test($option, $pkg, $ids)
	{
		$database = Factory::getContainer()->get(DatabaseInterface::class);
		ArrayHelper::toInteger($ids);
		if (!count($ids)) {
			$id = BFRequest::getInt('id', 0);
			if ($id) {
				$ids = array($id);
			}
		}
		if (!count($ids)) {
			Factory::getApplication()->redirect("index.php?option=$option&act=managepieces&pkg=$pkg");
			return;
		}

		$row = new facileFormsPieces($database);
		$row->load($ids[0]);

		$functionName = '';
		$params = array();
		$paramDefaults = array();
		if (preg_match('/function\\s+([a-zA-Z0-9_]+)\\s*\\(([^)]*)\\)/', $row->code, $matches)) {
			$functionName = $matches[1];
			$paramList = trim($matches[2]);
			if ($paramList !== '') {
				$parts = explode(',', $paramList);
				foreach ($parts as $part) {
					if (preg_match('/(\\$[a-zA-Z0-9_]+)(\\s*=\\s*([^,]+))?/', $part, $pMatch)) {
						$params[] = $pMatch[1];
						$paramDefaults[] = isset($pMatch[3]) ? trim($pMatch[3]) : '';
					}
				}
			}
		}

		$autoRun = false;
		if (count($params) === 0) {
			$autoRun = true;
		} else {
			$allDefaults = true;
			for ($i = 0; $i < count($params); $i++) {
				$default = isset($paramDefaults[$i]) ? trim($paramDefaults[$i]) : '';
				if ($default === '') {
					$allDefaults = false;
					break;
				}
			}
			$autoRun = $allDefaults;
		}
		HTML_facileFormsPiece::test($option, $pkg, $row, $functionName, $params, $paramDefaults, array(), null, '', '', 0, $autoRun, array());
	}

	static function testrun($option, $pkg, $ids)
	{
		$app = Factory::getApplication();
		$database = Factory::getContainer()->get(DatabaseInterface::class);
		ArrayHelper::toInteger($ids);
		if (!count($ids)) {
			$id = BFRequest::getInt('id', 0);
			if ($id) {
				$ids = array($id);
			}
		}
		if (!count($ids)) {
			$app->redirect("index.php?option=$option&act=managepieces&pkg=$pkg");
			return;
		}

		$row = new facileFormsPieces($database);
		$row->load($ids[0]);

		$functionName = BFRequest::getVar('test_function', '');
		$paramNames = BFRequest::getVar('test_param_names', array(), 'POST', 'array');
		$paramDefaults = BFRequest::getVar('test_param_defaults', array(), 'POST', 'array');
		$paramValues = BFRequest::getVar('test_param_values', array(), 'POST', 'array');
		$safeMode = 0;
		$args = array();

		foreach ($paramNames as $index => $name) {
			$value = isset($paramValues[$index]) ? $paramValues[$index] : '';
			$value = trim($value);
			$lower = strtolower($value);

			if ($lower === 'null') {
				$args[] = null;
				continue;
			} elseif ($lower === 'true') {
				$args[] = true;
				continue;
			} elseif ($lower === 'false') {
				$args[] = false;
				continue;
			}

			if ($value !== '' && (($value[0] === '{' && substr($value, -1) === '}') || ($value[0] === '[' && substr($value, -1) === ']'))) {
				$decoded = json_decode($value, true);
				if (json_last_error() === JSON_ERROR_NONE) {
					$args[] = $decoded;
					continue;
				}
			}

			if (preg_match('/^([\"\']).*\\1$/', $value)) {
				$args[] = stripcslashes(substr($value, 1, -1));
				continue;
			}

			if (is_numeric($value)) {
				$args[] = (strpos($value, '.') !== false) ? (float) $value : (int) $value;
				continue;
			}

			$args[] = $value;
		}

		$result = null;
		$output = '';
		$error = '';
		$errorDetails = array();

		$context = new BFAdminPieceTestContext($database);
		ob_start();
		try {
			$code = trim($row->code);
			$code = preg_replace('/^<\\?php\\s*/', '', $code);
			$code = preg_replace('/\\?>\\s*$/', '', $code);
			$runner = \Closure::bind(function () use ($code) {
				if ($code !== '') {
					eval($code);
				}
			}, $context, $context::class);
			$runner();
			if ($functionName !== '' && function_exists($functionName)) {
				$result = call_user_func_array($functionName, $args);
			} else {
				$error = 'Function not found in piece code.';
			}
		} catch (Throwable $e) {
			$error = $e->getMessage();
			$errorDetails = array(
				'message' => $e->getMessage(),
				'file' => $e->getFile(),
				'line' => $e->getLine(),
				'trace' => $e->getTraceAsString(),
			);
		}
		$output = ob_get_clean();

		HTML_facileFormsPiece::test($option, $pkg, $row, $functionName, $paramNames, $paramDefaults, $paramValues, $result, $output, $error, $safeMode, false, $errorDetails);
	}

	static function prev($option, $pkg, $ids)
	{
		self::navigate($option, $pkg, $ids, 'prev');
	}

	static function next($option, $pkg, $ids)
	{
		self::navigate($option, $pkg, $ids, 'next');
	}

	private static function navigate($option, $pkg, $ids, $direction)
	{
		$app = Factory::getApplication();
		$database = Factory::getContainer()->get(DatabaseInterface::class);
		ArrayHelper::toInteger($ids);
		if (!count($ids)) {
			$id = BFRequest::getInt('id', 0);
			if ($id) {
				$ids = array($id);
			}
		}
		if (!count($ids)) {
			$app->redirect("index.php?option=$option&act=managepieces&pkg=$pkg");
			return;
		}

		$currentId = (int) $ids[0];
		if ($direction === 'prev') {
			$database->setQuery(
				"SELECT id FROM #__facileforms_pieces WHERE package = " . $database->Quote($pkg) .
				" AND id < " . $currentId . " ORDER BY id DESC LIMIT 1"
			);
		} else {
			$database->setQuery(
				"SELECT id FROM #__facileforms_pieces WHERE package = " . $database->Quote($pkg) .
				" AND id > " . $currentId . " ORDER BY id ASC LIMIT 1"
			);
		}
		$targetId = (int) $database->loadResult();
		if (!$targetId) {
			if ($direction === 'prev') {
				$database->setQuery(
					"SELECT id FROM #__facileforms_pieces WHERE package = " . $database->Quote($pkg) .
					" ORDER BY id DESC LIMIT 1"
				);
			} else {
				$database->setQuery(
					"SELECT id FROM #__facileforms_pieces WHERE package = " . $database->Quote($pkg) .
					" ORDER BY id ASC LIMIT 1"
				);
			}
			$targetId = (int) $database->loadResult();
			if (!$targetId) {
				$targetId = $currentId;
			}
		}

		$testContext = BFRequest::getInt('test_context', 0);
		if ($testContext) {
			$app->redirect("index.php?option=$option&act=managepieces&task=test&pkg=$pkg&ids[]=" . $targetId);
		} else {
			$app->redirect("index.php?option=$option&act=managepieces&task=edit&pkg=$pkg&ids[]=" . $targetId);
		}
	}

} // class facileFormsPiece
?>
