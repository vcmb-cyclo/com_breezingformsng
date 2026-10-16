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

namespace Vcmb\Component\BreezingformsNG\Administrator\Service;

defined('_JEXEC') or die('Direct Access to this location is not allowed.');

use Joomla\Database\DatabaseInterface;
use Throwable;
use Joomla\CMS\Language\Text;

class BFAdminPieceTestContext
{
	private DatabaseInterface $db;
	/** @var object */
	public $formrow;
	/** @var int */
	public $form_id;
	public bool $dying = false;

	public function __construct(DatabaseInterface $db)
	{
		$this->db = $db;
		$this->formrow = (object) array('id' => 0, 'name' => '');
		$this->form_id = 0;
	}

	/**
	 * @param string $name
	 * @param mixed ...$args
	 */
	public function execPieceByName($name, ...$args): mixed
	{
		if ($name === '') {
			return null;
		}

		$query = $this->db->createQuery()
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

/**
 * Runs the PHP code of a piece for the test page of the administration.
 */
class PieceTestRunner
{
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

	/**
	 * @param array<int|string, mixed> $args
	 * @return array{result: mixed, output: string|false, error: string, errorDetails: array<string, mixed>}
	 */
	public static function executePieceCode(object $row, string $functionName, array $args, DatabaseInterface $database): array
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

	/**
	 * The first function declared by the code, with the names and default
	 * values of its parameters.
	 *
	 * @return array{string, list<string>, list<string>}
	 */
	public static function parseSignature(string $code): array
	{
		$functionName = '';
		$params = array();
		$paramDefaults = array();
		if (preg_match('/function\\s+([a-zA-Z0-9_]+)\\s*\\(([^)]*)\\)/', $code, $matches)) {
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
		return array($functionName, $params, $paramDefaults);
	}

	/**
	 * Converts the values typed on the test page into PHP arguments.
	 *
	 * @param array<int, mixed> $paramNames
	 * @param array<int, mixed> $paramValues
	 *
	 * @return list<mixed>
	 */
	public static function parseArguments(array $paramNames, array $paramValues): array
	{
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
		return $args;
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

	/** @return array<string, mixed> */
	public static function runUnitTests(object $row, string $functionName, DatabaseInterface $database): array
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
