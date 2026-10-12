<?php
/**
 * BreezingForms NG - A Joomla Forms Application
 * 
 * @version 6.0.0
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2008-2020 by Markus Bopp
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 * 
 * SPDX-License-Identifier: GPL-2.0-or-later
 **/

namespace Vcmb\Component\BreezingformsNG\Administrator\Service;

defined('_JEXEC') or die('Direct Access to this location is not allowed.');

use Exception;
use Vcmb\Component\BreezingformsNG\Site\Table\PieceTable;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Application\CMSApplication;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use RuntimeException;
use Throwable;
use Vcmb\Component\BreezingformsNG\Administrator\Model\PieceModel;
use Vcmb\Component\BreezingformsNG\Administrator\View\Pieces\Renderer;
use Joomla\CMS\Language\Text;

class BFAdminPieceTestContext
{
	private DatabaseInterface $db;
	public $formrow;
	public $form_id;
	public bool $dying = false;

	public function __construct(DatabaseInterface $db)
	{
		$this->db = $db;
		$this->formrow = (object) array('id' => 0, 'name' => '');
		$this->form_id = 0;
	}

	public function execPieceByName($name, ...$args): mixed
	{
		if ($name === '') {
			return null;
		}

		$query = $this->db->getQuery(true)
			->select($this->db->quoteName('code'))
			->from($this->db->quoteName('#__facileforms_pieces'))
			->where($this->db->quoteName('name') . ' = :name')
			->bind(':name', $name)
			->setLimit(1);
		$this->db->setQuery($query);
		$code = (string) $this->db->loadResult();
		if ($code === '') {
			return null;
		}

		$code = trim($code);
		$code = preg_replace('/^<\\?php\\s*/', '', $code);
		$code = preg_replace('/\\?>\\s*$/', '', $code);

		$runner = \Closure::bind(function (...$__bfPieceArgs) use ($code) {
			if ($code !== '') {
				return eval($code);
			}
			return null;
		}, $this, static::class);
		$runner(...$args);

		return true;
	}
}

class PieceManager
{
	public function __construct(
		private readonly CMSApplication $app,
		private readonly DatabaseInterface $database,
		private readonly PieceModel $model,
	) {
	}

	private static function buildIsolatedNamespace(): string
	{
		try {
			return 'BFPieceTest_' . bin2hex(random_bytes(8));
		} catch (Throwable $e) {
			return 'BFPieceTest_' . str_replace('.', '_', uniqid('', true));
		}
	}

	private static function normalizePieceCode(mixed $code): string
	{
		$code = trim((string) $code);
		$code = preg_replace('/^<\\?php\\s*/', '', $code);
		$code = preg_replace('/\\?>\\s*$/', '', $code);
		return $code;
	}

	private static function executePieceCode($row, $functionName, array $args, $database): array
	{
		$context = new BFAdminPieceTestContext($database);
		$result = null;
		$output = '';
		$error = '';
		$errorDetails = array();
		$hadProcessor = array_key_exists('ff_processor', $GLOBALS);
		$previousProcessor = $GLOBALS['ff_processor'] ?? null;
		$GLOBALS['ff_processor'] = $context;

		ob_start();
		try {
			$code = self::normalizePieceCode($row->code);
			$namespace = self::buildIsolatedNamespace();
			$runner = \Closure::bind(function ($__bfCode, $__bfNamespace) {
				if ($__bfCode === '') {
					return null;
				}
				return eval("namespace {$__bfNamespace};\n" . $__bfCode);
			}, $context, $context::class);
			$evalResult = $runner($code, $namespace);
			if ($functionName !== '') {
				$callable = '\\' . $namespace . '\\' . $functionName;
				if (is_callable($callable)) {
					$result = call_user_func_array($callable, $args);
				} else {
					$error = Text::_('COM_BREEZINGFORMSNG_TEST_INVALID_FUNCTION_NAME');
				}
			} else {
				$result = $evalResult;
			}
		} catch (Throwable $e) {
			$error = $e->getMessage();
			$errorDetails = array(
				'message' => $e->getMessage(),
				'file' => $e->getFile(),
				'line' => $e->getLine(),
				'trace' => $e->getTraceAsString(),
			);
		} finally {
			if ($hadProcessor) {
				$GLOBALS['ff_processor'] = $previousProcessor;
			} else {
				unset($GLOBALS['ff_processor']);
			}
		}
		$output = ob_get_clean();

		return array(
			'result' => $result,
			'output' => $output,
			'error' => $error,
			'errorDetails' => $errorDetails,
		);
	}

	public function edit($option, $pkg, $ids)
	{
		$database = $this->database;
		ArrayHelper::toInteger($ids);
		$typelist = array();
		$typelist[] = array('Untyped', Text::_('COM_BREEZINGFORMSNG_PIECES_UNTYPED'));
		$typelist[] = array('Before Form', Text::_('COM_BREEZINGFORMSNG_PIECES_BEFOREFORM'));
		$typelist[] = array('After Form', Text::_('COM_BREEZINGFORMSNG_PIECES_AFTERFORM'));
		$typelist[] = array('Begin Submit', Text::_('COM_BREEZINGFORMSNG_PIECES_BEGINSUBMIT'));
		$typelist[] = array('End Submit', Text::_('COM_BREEZINGFORMSNG_PIECES_ENDSUBMIT'));
		$row = new PieceTable($database);
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
		$row      = new PieceTable($database);

		try {
			if (!$row->bind($data)) {
				throw new RuntimeException(Text::_('COM_BREEZINGFORMSNG_PIECES_SAVE_FAILED'));
			}

			// Forcer code non filtré
			$row->code = $code;
			$row->unit_tests = $unitTests;
			$row->description = $app->getInput()->post->get('description', '', 'raw');

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
				throw new RuntimeException(Text::_('COM_BREEZINGFORMSNG_PIECES_SAVE_FAILED'));
			}
		} catch (Throwable $exception) {
			$app->enqueueMessage($exception->getMessage(), 'error');
			$app->redirect("index.php?option=$option&view=pieces&pkg=$pkg");
			return;
		}

		(new ActionLogger($app))->log(ActionLogger::SAVED, ActionLogger::PIECE, [(int) $row->id], (string) $row->name);
		$app->enqueueMessage(Text::_('COM_BREEZINGFORMSNG_PIECES_SAVED'));
		$app->redirect("index.php?option=$option&task=pieces.edit&pkg=$pkg&ids[]=" . (int) $row->id);
	}

	public function cancel($option, $pkg)
	{
		$this->app->redirect("index.php?option=$option&view=pieces&pkg=$pkg");
	} // cancel


	public function copy($option, $pkg, $ids)
	{
		$database = $this->database;
		ArrayHelper::toInteger($ids);
		$total = count($ids);
		$row = new PieceTable($database);
		if (count($ids))
			foreach ($ids as $id) {
				$row->load(intval($id));
				$row->id = NULL;
				$row->created = (new \Joomla\CMS\Date\Date())->toSql();
				$row->created_by = (string) $this->app->getIdentity()->username;
				$row->modified = $row->created;
				$row->modified_by = $row->created_by;
				$row->store();
			} // foreach
		if ($ids !== []) {
			(new ActionLogger($this->app))->log(ActionLogger::COPIED, ActionLogger::PIECE, $ids);
		}
		$msg = Text::plural('COM_BREEZINGFORMSNG_PIECES_N_COPIED', $total);
		$this->app->enqueueMessage($msg);
		$this->app->redirect("index.php?option=$option&view=pieces&pkg=$pkg");
	} // copy


	public function del($option, $pkg, $ids)
	{
		try {
			$total = $this->model->deleteByIds($ids);
		} catch (RuntimeException $e) {
			$this->app->enqueueMessage($e->getMessage(), 'error');
			$this->app->redirect("index.php?option=$option&view=pieces&pkg=$pkg");
			return;
		}

		if ($total) {
			(new ActionLogger($this->app))->log(ActionLogger::DELETED, ActionLogger::PIECE, (array) $ids);
			$msg = Text::plural('COM_BREEZINGFORMSNG_PIECES_N_DELETED', $total);
			$this->app->enqueueMessage($msg);
			$this->app->redirect("index.php?option=$option&view=pieces&pkg=$pkg");
			return;
		}
		$this->app->redirect("index.php?option=$option&view=pieces&pkg=$pkg");
	} // del


	public function publish($option, $pkg, $ids, $publish)
	{
		try {
			$this->model->publishByIds($ids, (bool) $publish);
			(new ActionLogger($this->app))->log(
				$publish ? ActionLogger::PUBLISHED : ActionLogger::UNPUBLISHED,
				ActionLogger::PIECE,
				(array) $ids
			);
		} catch (RuntimeException $e) {
			$this->app->enqueueMessage($e->getMessage(), 'error');
			$this->app->redirect("index.php?option=$option&view=pieces&pkg=$pkg");
		}

		$this->app->redirect("index.php?option=$option&view=pieces&pkg=$pkg");
	} // publish


	public function listitems($option, $pkg)
	{
		try {
			$pkgs = $this->model->getPackages();
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

		$app = $this->app;
		$input = $app->getInput();
		$session = $app->getSession();
		$searchReq = $input->get('search', null, 'string');
		if ($searchReq === null) {
			$search = (string) $session->get('bf.pieces_search', '');
		} else {
			$search = trim((string) $searchReq);
			$session->set('bf.pieces_search', $search);
		}
		$sortReq = $input->get('sort', null, 'string');
		$dirReq = $input->get('dir', null, 'cmd');
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
		$dir = $dir === 'DESC' ? 'DESC' : 'ASC';

		$pageSizes = array(10, 25, 50, 100, 250, 500, 1000, 5000, 10000, 100000);
		$limitReq = $input->getInt('limit', -1);
		if ($limitReq > 0 && in_array($limitReq, $pageSizes, true)) {
			$limit = $limitReq;
			$session->set('bf.pieces_limit', $limit);
		} else {
			$limit = (int) $session->get('bf.pieces_limit', 10);
			if (!in_array($limit, $pageSizes, true)) {
				$limit = 10;
			}
		}

		$limitstartReq = $input->getInt('limitstart', -1);
		if ($limitstartReq >= 0) {
			$limitstart = $limitstartReq;
		} else {
			$limitstart = (int) $session->get('bf.pieces_limitstart', 0);
		}
		if ($limitstart < 0) {
			$limitstart = 0;
		}

		try {
			$listData = $this->model->getListData($pkg, $search, $sort, $dir, $limit, $limitstart);
		} catch (Exception $e) {
			echo $e->getCode() . ' : ' . $e->getMessage();
			return false;
		}
		$total = $listData['total'];
		$limitstart = $listData['limitstart'];
		$session->set('bf.pieces_limitstart', $limitstart);
		$rows = $listData['rows'];

		Renderer::listitems($option, $rows, $pkglist, $pkg, $search, $total, $limit, $limitstart, $pageSizes);
	} // listitems

	public function test($option, $pkg, $ids)
	{
		$database = $this->database;
		ArrayHelper::toInteger($ids);
		if (!count($ids)) {
			$id = $this->app->getInput()->getInt('id', 0);
			if ($id) {
				$ids = array($id);
			}
		}
		if (!count($ids)) {
			$this->app->redirect("index.php?option=$option&view=pieces&pkg=$pkg");
			return;
		}

		$row = new PieceTable($database);
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
		$testMode = $this->app->getInput()->getCmd('test_mode', '');
		Renderer::test($option, $pkg, $row, $functionName, $params, $paramDefaults, array(), null, '', '', 0, $autoRun, array(), $testMode, array());
	}

	public function testrun($option, $pkg, $ids)
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
			$app->redirect("index.php?option=$option&view=pieces&pkg=$pkg");
			return;
		}

		$row = new PieceTable($database);
		$row->load($ids[0]);

		$functionName = $app->getInput()->post->getString('test_function', '');
		$paramNames = $app->getInput()->post->get('test_param_names', array(), 'array');
		$paramDefaults = $app->getInput()->post->get('test_param_defaults', array(), 'array');
		$paramValues = $app->getInput()->post->get('test_param_values', array(), 'array');
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

		$testMode = $app->getInput()->getCmd('test_mode', '');
		$autoOpened = $app->getInput()->getInt('auto_open_tests', 0);
		$result = null;
		$output = '';
		$error = '';
		$errorDetails = array();
		if ($testMode !== 'unit') {
			$execution = self::executePieceCode($row, $functionName, $args, $database);
			$result = $execution['result'];
			$output = $execution['output'];
			$error = $execution['error'];
			$errorDetails = $execution['errorDetails'];
		}

		$unitTestResult = array();
		if ($testMode === 'unit' || trim((string) $row->unit_tests) !== '') {
			$unitTestResult = self::runUnitTests($row, $functionName, $database);
		}
		Renderer::test($option, $pkg, $row, $functionName, $paramNames, $paramDefaults, $paramValues, $result, $output, $error, $safeMode, false, $errorDetails, $testMode, $unitTestResult, $autoOpened);
	}

	public function testrunajax($option, $pkg, $ids)
	{
		$app = $this->app;
		$app->setHeader('Content-Type', 'application/json; charset=utf-8', true);
		$post = $app->getInput()->post;

		$database = $this->database;
		$row = new PieceTable($database);
		$row->id = $post->getInt('id', 0);
		$row->code = $post->get('code', '', 'raw');
		$row->unit_tests = $post->get('unit_tests', '', 'raw');
		$functionName = $post->getString('test_function', '');

		$result = self::runUnitTests($row, $functionName, $database);
		$app->setBody(json_encode($result, JSON_THROW_ON_ERROR));
		$app->close();
	}

	public function prev($option, $pkg, $ids)
	{
		$this->navigate($option, $pkg, $ids, 'prev');
	}

	public function next($option, $pkg, $ids)
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
			$app->redirect("index.php?option=$option&view=pieces&pkg=$pkg");
			return;
		}

		$currentId = (int) $ids[0];
		$query = $database->getQuery(true)
			->select($database->quoteName('id'))
			->from($database->quoteName('#__facileforms_pieces'))
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
				->from($database->quoteName('#__facileforms_pieces'))
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
			$app->redirect("index.php?option=$option&task=pieces.test&pkg=$pkg&ids[]=" . $targetId . $testModeQuery);
		} else {
			$app->redirect("index.php?option=$option&task=pieces.edit&pkg=$pkg&ids[]=" . $targetId);
		}
	}

	private static function parseTestValue(mixed $value): mixed
	{
		$value = trim((string) $value);
		$lower = strtolower($value);

		if ($lower === 'null') {
			return null;
		}
		if ($lower === 'true') {
			return true;
		}
		if ($lower === 'false') {
			return false;
		}
		if ($value !== '' && (($value[0] === '[' && substr($value, -1) === ']') || preg_match('/^array\\s*\\(.*\\)$/s', $value))) {
			try {
				return eval('return ' . $value . ';');
			} catch (Throwable $e) {
			}
		}
		if ($value !== '' && (($value[0] === '{' && substr($value, -1) === '}') || ($value[0] === '[' && substr($value, -1) === ']'))) {
			$decoded = json_decode($value, true);
			if (json_last_error() === JSON_ERROR_NONE) {
				return $decoded;
			}
		}
		if (preg_match('/^([\"\']).*\\1$/', $value)) {
			return stripcslashes(substr($value, 1, -1));
		}
		if (is_numeric($value)) {
			return str_contains($value, '.') ? (float) $value : (int) $value;
		}
		return $value;
	}

	private static function valuesEqual(mixed $actual, mixed $expected): bool
	{
		if ($actual === $expected) {
			return true;
		}
		return json_encode($actual) === json_encode($expected);
	}

	private static function runUnitTests($row, $functionName, $database): array
	{
		$lines = preg_split('/\\r?\\n/', (string) $row->unit_tests);
		$tests = array();
		$failures = array();
		$passedCount = 0;

		foreach ($lines as $index => $line) {
			$lineNumber = $index + 1;
			$trimmedLine = trim((string) $line);
			if ($trimmedLine === '' || strpos($trimmedLine, '//') === 0 || strpos($trimmedLine, '#') === 0) {
				continue;
			}

			$arrowPos = strpos($trimmedLine, '->');
			if ($arrowPos === false) {
				return array(
					'error' => Text::sprintf('COM_BREEZINGFORMSNG_TEST_MISSING_SEPARATOR', $lineNumber),
				);
			}

			$inputText = trim(substr($trimmedLine, 0, $arrowPos));
			$expectedText = trim(substr($trimmedLine, $arrowPos + 2));
			if ($inputText === '' || $expectedText === '') {
				return array(
					'error' => Text::sprintf('COM_BREEZINGFORMSNG_TEST_MISSING_INPUT_EXPECTED', $lineNumber),
				);
			}

			$inputValue = self::parseTestValue($inputText);
			$tests[] = array(
				'line' => $lineNumber,
				'input_text' => $inputText,
				'args' => is_array($inputValue) ? $inputValue : array($inputValue),
				'expected' => self::parseTestValue($expectedText),
			);
		}

		if (!count($tests)) {
			return array(
				'warning' => Text::_('COM_BREEZINGFORMSNG_TEST_NO_UNIT_TEST_DEFINED'),
			);
		}

		foreach ($tests as $test) {
			$execution = self::executePieceCode($row, $functionName, $test['args'], $database);
			if ($execution['error'] === '') {
				if (self::valuesEqual($execution['result'], $test['expected'])) {
					$passedCount++;
				} else {
					$failures[] = Text::sprintf(
						'COM_BREEZINGFORMSNG_TEST_FAILURE_MISMATCH',
						$test['line'],
						$test['input_text'],
						var_export($test['expected'], true),
						var_export($execution['result'], true)
					);
				}
			} else {
				$failures[] = Text::sprintf(
					'COM_BREEZINGFORMSNG_TEST_FAILURE_ERROR',
					$test['line'],
					$test['input_text'],
					$execution['error']
				);
			}
			$output = trim((string) $execution['output']);
			if ($output !== '') {
				$failures[] = Text::sprintf('COM_BREEZINGFORMSNG_TEST_FAILURE_OUTPUT', $test['line'], $output);
			}
		}

		return array(
			'total' => count($tests),
			'passed' => $passedCount,
			'failures' => $failures,
		);
	}

}
?>
