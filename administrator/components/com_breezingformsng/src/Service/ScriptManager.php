<?php

/**
 * BreezingForms NG - A Joomla Forms Application
 * 
 * @version 6.0.0
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2008-2020 by Markus Bopp
 * @copyright (C) 2024 - 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 * 
 * SPDX-License-Identifier: GPL-2.0-or-later
 **/

namespace Vcmb\Component\BreezingformsNG\Administrator\Service;

defined('_JEXEC') or die('Direct Access to this location is not allowed.');

use Vcmb\Component\BreezingformsNG\Site\Table\ScriptTable;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Application\CMSApplication;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use RuntimeException;
use Throwable;
use Vcmb\Component\BreezingformsNG\Administrator\Model\ScriptModel;
use Vcmb\Component\BreezingformsNG\Administrator\View\Scripts\Renderer;
use Joomla\CMS\Language\Text;

class ScriptManager
{
	public function __construct(
		private readonly CMSApplication $app,
		private readonly DatabaseInterface $database,
		private readonly ScriptModel $model,
		private readonly ScriptSignatureParser $signatureParser,
	) {
	}

	public function edit($option, $pkg, $ids)
	{
		$database = $this->database;
		ArrayHelper::toInteger($ids);
		$typelist = array();
		$typelist[] = array('Untyped', Text::_('COM_BREEZINGFORMSNG_SCRIPTS_UNTYPED'));
		$typelist[] = array('Element Init', Text::_('COM_BREEZINGFORMSNG_SCRIPTS_ELEMENTINIT'));
		$typelist[] = array('Element Action', Text::_('COM_BREEZINGFORMSNG_SCRIPTS_ELEMENTACTION'));
		$typelist[] = array('Element Validation', Text::_('COM_BREEZINGFORMSNG_SCRIPTS_ELEMENTVALID'));
		$typelist[] = array('Form Init', Text::_('COM_BREEZINGFORMSNG_SCRIPTS_FORMINIT'));
		$typelist[] = array('Form Submitted', Text::_('COM_BREEZINGFORMSNG_SCRIPTS_FORMSUBMIT'));
		$row = new ScriptTable($database);
		if (count($ids)) {
			$row->load($ids[0]);
		} else {
			$row->type = $typelist[0];
			$row->package = $pkg;
			$row->published = 1;
		} // if
		Renderer::edit($option, $pkg, $row, $typelist);
	} // edit


	// ✅ FORCER le champ code en RAW (conserve < et >)
	public function save($option, $pkg)
	{
		$app = $this->app;
		$post = $app->getInput()->post;
		$data = $post->getArray();
		$code = $post->get('code', '', 'raw');
		$unitTests = $post->get('unit_tests', '', 'raw');

		$database = $this->database;
		$row      = new ScriptTable($database);

		try {
			if (!$row->bind($data)) {
				throw new RuntimeException(Text::_('COM_BREEZINGFORMSNG_SCRIPTS_SAVE_FAILED'));
			}

			// Forcer code non filtré
			$row->code = $code;
			$row->unit_tests = $unitTests;

			$now = (new \Joomla\CMS\Date\Date())->toSql();
			$userId = (string) $app->getIdentity()->username;

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
				throw new RuntimeException(Text::_('COM_BREEZINGFORMSNG_SCRIPTS_SAVE_FAILED'));
			}
		} catch (Throwable $exception) {
			$app->enqueueMessage($exception->getMessage(), 'error');
			$app->redirect("index.php?option=$option&view=scripts&pkg=$pkg");
			return;
		}

		(new ActionLogger($app))->log(ActionLogger::SAVED, ActionLogger::SCRIPT, [(int) $row->id], (string) $row->name);
		$app->enqueueMessage(Text::_('COM_BREEZINGFORMSNG_SCRIPTS_SAVED'));
		$app->redirect("index.php?option=$option&task=scripts.edit&pkg=$pkg&ids[]=" . (int) $row->id);
	}


	public function cancel($option, $pkg)
	{
		$this->app->redirect("index.php?option=$option&view=scripts&pkg=$pkg");
	} // cancel

	public function copy($option, $pkg, $ids)
	{
		$database = $this->database;
		$total = count($ids);
		$row = new ScriptTable($database);
		if (count($ids)) foreach ($ids as $id) {
			$row->load(intval($id));
			$row->id       = null;
			$row->created = (new \Joomla\CMS\Date\Date())->toSql();
			$row->created_by = (string) $this->app->getIdentity()->username;
			$row->modified = $row->created;
			$row->modified_by = $row->created_by;
			$row->store();
		} // foreach
		if ($ids !== []) {
			(new ActionLogger($this->app))->log(ActionLogger::COPIED, ActionLogger::SCRIPT, $ids);
		}
		$msg = Text::plural('COM_BREEZINGFORMSNG_SCRIPTS_N_COPIED', $total);
		$this->app->enqueueMessage($msg);
		$this->app->redirect("index.php?option=$option&view=scripts&pkg=$pkg");
	} // copy

	public function del($option, $pkg, $ids)
	{
		try {
			$total = $this->model->deleteByIds($ids);
		} catch (RuntimeException $e) {
			$this->app->enqueueMessage($e->getMessage(), 'error');
			$this->app->redirect("index.php?option=$option&view=scripts&pkg=$pkg");
			return;
		}

		if ($total) {
			(new ActionLogger($this->app))->log(ActionLogger::DELETED, ActionLogger::SCRIPT, (array) $ids);
			$this->app->enqueueMessage(
				Text::plural('COM_BREEZINGFORMSNG_SCRIPTS_N_DELETED', $total),
				'message'
			);
		}
		$this->app->redirect("index.php?option=$option&view=scripts&pkg=$pkg");
	} // del

	public function publish($option, $pkg, $ids, $publish)
	{
		try {
			$this->model->publishByIds($ids, (bool) $publish);
			(new ActionLogger($this->app))->log(
				$publish ? ActionLogger::PUBLISHED : ActionLogger::UNPUBLISHED,
				ActionLogger::SCRIPT,
				(array) $ids
			);
		} catch (RuntimeException $e) {
			$this->app->enqueueMessage($e->getMessage(), 'error');
			$this->app->redirect("index.php?option=$option&view=scripts&pkg=$pkg");
		}

		$this->app->redirect("index.php?option=$option&view=scripts&pkg=$pkg");
	} // publish

	public function listitems($option, $pkg)
	{
		$app = $this->app;
		$input = $app->getInput();
		$session = $app->getSession();

		try {
			$pkgs = $this->model->getPackages();
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
		$searchReq = $input->get('search', null, 'string');
		if ($searchReq === null) {
			$search = (string) $session->get('bf.scripts_search', '');
		} else {
			$search = trim((string) $searchReq);
			$session->set('bf.scripts_search', $search);
		}

		$sortReq = $input->get('sort', null, 'string');
		$dirReq = $input->get('dir', null, 'cmd');

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

		$dir = $dir === 'DESC' ? 'DESC' : 'ASC';
		$pageSizes = array(10, 25, 50, 100, 250, 500, 1000, 5000, 10000, 100000);
		$limitReq = $input->getInt('limit', -1);

		if ($limitReq > 0 && in_array($limitReq, $pageSizes, true)) {
			$limit = $limitReq;
			$session->set('bf.scripts_limit', $limit);
		} else {
			$limit = (int) $session->get('bf.scripts_limit', 10);

			if (!in_array($limit, $pageSizes, true)) {
				$limit = 10;
			}
		}

		$limitstartReq = $input->getInt('limitstart', -1);

		if ($limitstartReq >= 0) {
			$limitstart = $limitstartReq;
		} else {
			$limitstart = (int) $session->get('bf.scripts_limitstart', 0);
		}

		if ($limitstart < 0) {
			$limitstart = 0;
		}

		try {
			$listData = $this->model->getListData($pkg, $search, $sort, $dir, $limit, $limitstart);
		} catch (\Exception $e) {
			echo $e->getMessage();
			return false;
		} // try

		$total = $listData['total'];
		$limitstart = $listData['limitstart'];
		$session->set('bf.scripts_limitstart', $limitstart);
		$rows = $listData['rows'];

		Renderer::listitems($option, $rows, $pkglist, $pkg, $search, $total, $limit, $limitstart, $pageSizes);
	} // listitems

	public function test($option, $pkg, $ids)
	{
		$app = $this->app;
		$database = $this->database;
		ArrayHelper::toInteger($ids);
		if (!count($ids)) {
			$id = $app->getInput()->getInt('id', 0);
			if ($id) {
				$ids = array($id);
			}
		}
		if (!count($ids)) {
			$app->redirect("index.php?option=$option&view=scripts&pkg=$pkg");
			return;
		}

		$row = new ScriptTable($database);
		$row->load($ids[0]);
		if (!(int) $row->id) {
			$app->redirect("index.php?option=$option&view=scripts&pkg=$pkg");
			return;
		}

		[$functionName, $params, $paramDefaults] = $this->signatureParser->parse(
			(string) $row->code,
			(string) $row->name
		);
		$autoRun = false;
		if (count($params) === 0) {
			$autoRun = true;
		} else {
			$allDefaults = true;
			for ($i = 0; $i < count($params); $i++) {
				$default = isset($paramDefaults[$i]) ? trim((string) $paramDefaults[$i]) : '';
				if ($default === '') {
					$allDefaults = false;
					break;
				}
			}
			$autoRun = $allDefaults;
		}
		$testMode = $app->getInput()->getCmd('test_mode', '');
		Renderer::test($option, $pkg, $row, $functionName, $params, $paramDefaults, $autoRun, $testMode);
	}

	public function prev($option, $pkg, $ids): void
	{
		$this->navigate($option, $pkg, $ids, 'prev');
	}

	public function next($option, $pkg, $ids): void
	{
		$this->navigate($option, $pkg, $ids, 'next');
	}

	private function navigate($option, $pkg, $ids, $direction): void
	{
		$app = $this->app;
		$database = $this->database;
		ArrayHelper::toInteger($ids);
		if (!count($ids)) {
			$id = $app->getInput()->getInt('id', 0);
			if ($id) {
				$ids = array($id);
			}
		}
		if (!count($ids)) {
			$app->redirect("index.php?option=$option&view=scripts&pkg=$pkg");
			return;
		}

		$currentId = (int) $ids[0];
		$query = $database->getQuery(true)
			->select($database->quoteName('id'))
			->from($database->quoteName('#__facileforms_scripts'))
			->where($database->quoteName('id') . ($direction === 'prev' ? ' < :currentId' : ' > :currentId'))
			->order($database->quoteName('id') . ($direction === 'prev' ? ' DESC' : ' ASC'))
			->bind(':currentId', $currentId, ParameterType::INTEGER)
			->setLimit(1);
		if ($pkg !== '') {
			$query->where($database->quoteName('package') . ' = :package')
				->bind(':package', $pkg);
		}
		$database->setQuery($query);
		$targetId = (int) $database->loadResult();
		if (!$targetId) {
			$query = $database->getQuery(true)
				->select($database->quoteName('id'))
				->from($database->quoteName('#__facileforms_scripts'))
				->order($database->quoteName('id') . ($direction === 'prev' ? ' DESC' : ' ASC'))
				->setLimit(1);
			if ($pkg !== '') {
				$query->where($database->quoteName('package') . ' = :package')
					->bind(':package', $pkg);
			}
			$database->setQuery($query);
			$targetId = (int) $database->loadResult();
			if (!$targetId) {
				$targetId = $currentId;
			}
		}

		$testContext = $app->getInput()->getInt('test_context', 0);
		$testMode = $app->getInput()->getCmd('test_mode', '');
		if ($testContext) {
			$testModeQuery = $testMode !== '' ? '&test_mode=' . urlencode($testMode) : '';
			$app->redirect("index.php?option=$option&task=scripts.test&pkg=$pkg&ids[]=" . $targetId . $testModeQuery);
		} else {
			$app->redirect("index.php?option=$option&task=scripts.edit&pkg=$pkg&ids[]=" . $targetId);
		}
	}

}
